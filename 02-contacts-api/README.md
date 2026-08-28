# 2. Back-end da lista de contatos

REST API in plain PHP 8.1+ (no framework, zero required runtime packages — see the root README for why). Stores people, each with zero or more contacts (`phone`, `email`, or `whatsapp`).

## Layout

```
public/index.php        Front controller: wires routes, dependencies, dispatches
src/Http/                Request / Response / Router (the whole "framework")
src/Controllers/         PeopleController, ContactsController
src/Repositories/        PersonRepository, ContactRepository (PDO, prepared statements)
src/Models/               Person, Contact (immutable read models)
src/Support/              Validator, ValidationException, NotFoundException
src/Config/Database.php   PDO connection factory + auto-migration
database/migrations/      Raw SQL schema (one file per supported driver)
tests/                    PHPUnit (Unit + Feature) + a dependency-free smoke test
```

## Run it

```bash
cp .env.example .env
php -S 0.0.0.0:8080 -t public
curl http://localhost:8080/api/health
```

No Composer required to just run the app - it has zero external runtime dependencies, and `public/index.php` falls back to a small hand-rolled autoloader if `vendor/autoload.php` hasn't been generated. Composer only comes into play for the PHPUnit test suite (below).

## Configuration

Everything is driven by environment variables (see `.env.example`):

- `DB_CONNECTION` — `sqlite` (default) or `mysql`.
- `DB_SQLITE_PATH` — where to put the SQLite file (ignored for MySQL).
- `DB_HOST` / `DB_PORT` / `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` — MySQL only.
- `CORS_ALLOWED_ORIGINS` — defaults to `*`.

The schema is created automatically on first connection (`Database::migrate()`), no separate migration step needed.

## Tests

```bash
# PHPUnit (needs `composer require --dev phpunit/phpunit` first if vendor/bin/phpunit isn't present)
composer test

# Dependency-free smoke test — boots its own server against a throwaway SQLite DB
bash tests/smoke-test.sh
```

## Example requests

```bash
curl -X POST localhost:8080/api/people -H 'Content-Type: application/json' -d '{"name":"Ada Lovelace"}'

curl -X POST localhost:8080/api/people/1/contacts \
  -H 'Content-Type: application/json' \
  -d '{"type":"email","value":"ada@example.com"}'

curl localhost:8080/api/people/1
```

See the root `README.md` for the full route table.
