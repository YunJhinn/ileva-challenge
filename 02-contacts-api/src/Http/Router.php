<?php

declare(strict_types=1);

namespace App\Http;

use App\Support\NotFoundException;
use App\Support\ValidationException;
use Throwable;

/**
 * A deliberately small router: register `{method}('/pattern/{param}', $handler)`,
 * then call dispatch(). Route params are extracted from `{curly}` segments and
 * passed to the handler as an associative array.
 *
 * Also acts as the single place where exceptions are turned into the right
 * HTTP status + JSON body, so controllers never have to think about it.
 */
final class Router
{
    /** @var array<string, list<array{regex: string, paramNames: list<string>, handler: callable}>> */
    private array $routes = [];

    public function get(string $pattern, callable $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, callable $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    public function put(string $pattern, callable $handler): void
    {
        $this->add('PUT', $pattern, $handler);
    }

    public function delete(string $pattern, callable $handler): void
    {
        $this->add('DELETE', $pattern, $handler);
    }

    private function add(string $method, string $pattern, callable $handler): void
    {
        $paramNames = [];
        $regex = preg_replace_callback(
            '#\{(\w+)\}#',
            function (array $matches) use (&$paramNames): string {
                $paramNames[] = $matches[1];

                return '(?P<' . $matches[1] . '>[^/]+)';
            },
            $pattern
        );

        $this->routes[$method][] = [
            'regex' => '#^' . $regex . '$#',
            'paramNames' => $paramNames,
            'handler' => $handler,
        ];
    }

    public function dispatch(Request $request): Response
    {
        try {
            if ($request->method() === 'OPTIONS') {
                return Response::noContent();
            }

            foreach ($this->routes[$request->method()] ?? [] as $route) {
                if (preg_match($route['regex'], $request->path(), $matches) === 1) {
                    $args = [];
                    foreach ($route['paramNames'] as $name) {
                        $args[$name] = $matches[$name];
                    }

                    return ($route['handler'])($request, $args);
                }
            }

            if ($this->pathExistsForAnotherMethod($request)) {
                return Response::json([
                    'error' => 'method_not_allowed',
                    'message' => "Method {$request->method()} is not allowed for {$request->path()}",
                ], 405);
            }

            return Response::json([
                'error' => 'not_found',
                'message' => "Route {$request->path()} not found",
            ], 404);
        } catch (ValidationException $e) {
            return Response::json([
                'error' => 'validation_failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (NotFoundException $e) {
            return Response::json([
                'error' => 'not_found',
                'message' => $e->getMessage(),
            ], 404);
        } catch (Throwable $e) {
            error_log((string) $e);
            $debug = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';

            return Response::json([
                'error' => 'internal_server_error',
                'message' => $debug ? $e->getMessage() : 'Something went wrong.',
            ], 500);
        }
    }

    private function pathExistsForAnotherMethod(Request $request): bool
    {
        foreach ($this->routes as $method => $routes) {
            if ($method === $request->method()) {
                continue;
            }

            foreach ($routes as $route) {
                if (preg_match($route['regex'], $request->path()) === 1) {
                    return true;
                }
            }
        }

        return false;
    }
}
