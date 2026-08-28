<?php

declare(strict_types=1);

use App\Config\Database;
use App\Controllers\ContactsController;
use App\Controllers\PeopleController;
use App\Http\Request;
use App\Http\Response;
use App\Http\Router;
use App\Repositories\ContactRepository;
use App\Repositories\PersonRepository;

// Prefer Composer's autoloader if it has been generated (`composer dump-autoload`),
// but don't require it: this project has zero external runtime dependencies,
// so a hand-rolled PSR-4-ish autoloader works exactly as well and means the
// app runs with nothing but PHP installed - no Composer required.
if (is_file(dirname(__DIR__) . '/vendor/autoload.php')) {
    require dirname(__DIR__) . '/vendor/autoload.php';
} else {
    spl_autoload_register(function (string $class): void {
        $prefix = 'App\\';
        if (!str_starts_with($class, $prefix)) {
            return;
        }
        $relativePath = str_replace('\\', '/', substr($class, strlen($prefix)));
        $file = dirname(__DIR__) . '/src/' . $relativePath . '.php';
        if (is_file($file)) {
            require $file;
        }
    });
}

loadEnvFile(dirname(__DIR__) . '/.env');

/** Tiny, dependency-free .env loader — good enough for this project's needs. */
function loadEnvFile(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");

        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
            putenv("{$key}={$value}");
        }
    }
}

// --- Dependencies (deliberately simple - no DI container needed at this size) ---
$db = Database::connection();
$personRepository = new PersonRepository($db);
$contactRepository = new ContactRepository($db);
$peopleController = new PeopleController($personRepository, $contactRepository);
$contactsController = new ContactsController($contactRepository, $personRepository);

// --- Routes ---
$router = new Router();

$router->get('/api/health', fn (Request $r) => Response::json(['status' => 'ok']));

$router->get('/api/people', fn (Request $r) => $peopleController->index($r));
$router->post('/api/people', fn (Request $r) => $peopleController->store($r));
$router->get('/api/people/{id}', fn (Request $r, array $a) => $peopleController->show($r, $a));
$router->put('/api/people/{id}', fn (Request $r, array $a) => $peopleController->update($r, $a));
$router->delete('/api/people/{id}', fn (Request $r, array $a) => $peopleController->destroy($r, $a));

$router->post('/api/people/{personId}/contacts', fn (Request $r, array $a) => $contactsController->store($r, $a));
$router->get('/api/contacts/{id}', fn (Request $r, array $a) => $contactsController->show($r, $a));
$router->put('/api/contacts/{id}', fn (Request $r, array $a) => $contactsController->update($r, $a));
$router->delete('/api/contacts/{id}', fn (Request $r, array $a) => $contactsController->destroy($r, $a));

// --- Dispatch ---
$request = Request::fromGlobals();
$response = $router->dispatch($request);

// Simple, permissive CORS so the frontend (task 3) can call this API from
// any origin/port, whether running locally or deployed separately.
$allowedOrigins = $_ENV['CORS_ALLOWED_ORIGINS'] ?? '*';
$response
    ->withHeader('Access-Control-Allow-Origin', $allowedOrigins)
    ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
    ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
    ->send();
