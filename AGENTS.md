# AGENTS.md — Lighthouse backend

## Stack

- **Laravel 13** (13.24.0) / PHP 8.4+ / Composer.
- **Docker**: FrankenPHP + Octane, nginx, PostgreSQL 15 + PostGIS, Redis 7.2, supervisord (octane, reverb, queue-worker, scheduler).
- **Octane** (FrankenPHP) app server. **Reverb** WebSocket (port 8006 internal / 8011 host).
- **PostgreSQL** (`lighthouse`) + PostGIS for database. **Redis** available via config.
- Database-driven queue, cache, session by default (`SESSION_DRIVER`/`QUEUE_CONNECTION`/`CACHE_STORE=database`).
- **Vite 8** + **Tailwind 4** for frontend assets (vanilla JS, no framework). Views server-side from Blade (`resources/views`).
- **Current state: stock Laravel skeleton** — no custom code, no API routes. `routes/web.php` only has the default `/` welcome route; tests are generated examples. Don't go looking for app-specific architecture that doesn't exist yet.

## Docker

All commands run inside the app container. Define:

```
alias APP='docker compose exec lighthouse-api'
```

Services:
- `lighthouse-api` — FrankenPHP/Octane (port 9000 internal, 8006 Reverb internal)
- `nginx` — reverse proxy (host port **8010**)
- `postgres` — PostgreSQL 15 + PostGIS (host port **5433**; avoid 5432/8005/8006 — used by plant-doctor)
- `redis` — Redis 7.2 (internal)

Infra files (`Dockerfile`, `docker-compose.yml`, `nginx*.conf`, `supervisord.conf`, `custom-php.ini`) are **gitignored**; commit the `.example` twins instead. `.env` is mounted read-only into the container — changes need `docker compose restart lighthouse-api`.

**Octane/Reverb install in the container** (not on host): if a fresh checkout lacks them, run inside the running container:
```
APP composer require laravel/octane laravel/reverb
```
(composer runs under the container's PHP 8.4; it updates `composer.json`/`lock`/`vendor` on the host via the bind mount).

## Toolchain: PHP binary mismatch (critical)

`php` on PATH resolves to XAMPP's PHP 8.2.4 (`C:\xampp\php\php.exe`), which **cannot run this project** — the installed vendor `platform_check.php` requires PHP >= 8.4.1. Always use Laragon's PHP binary:

```
C:\laragon\bin\php\php-8.5.4-nts-Win32-vs17-x64\php.exe
```

- `php artisan ...` (host) → `& "C:\laragon\bin\php\php-8.5.4-nts-Win32-vs17-x64\php.exe" artisan ...`
- `composer install/update` (host) → also must run under that binary (platform/lock require ^8.3): `& "...php.exe" "C:\laragon\bin\composer\composer.phar" ...`
- `npm`/`node` work normally.
- **Prefer running artisan/tests inside the container** (`APP php artisan ...`) — the `.env` points `DB_HOST=postgres`, which only resolves inside the Docker network.

## Commands

| Command | Description |
|---------|-------------|
| `APP composer run test` | Tests (`artisan config:clear` + `artisan test`; uses Postgres test DB `lighthouse_test`) |
| `APP composer run test -- --filter=MethodName` | Run a single/focused test |
| `APP composer run test -- tests/Feature/SomeTest.php` | Run a single file |
| `APP php artisan migrate` | Run migrations |
| `APP php artisan db:seed` | Run seeders |
| `APP php artisan octane:reload` | Reload Octane without downtime |
| `APP php artisan config:cache && APP php artisan route:cache` | Cache bootstrap |
| `APP php artisan key:generate` | Regenerate app key |
| `APP php artisan storage:link` | Link public storage |
| `APP php artisan make:model Xxx -m` | Model + migration |
| `APP php artisan make:controller XxxController` | Controller |
| `APP php artisan make:request XxxRequest` | Form request |
| `APP php artisan make:resource XxxResource` | API resource |
| `APP php artisan make:migration create_xxx_table` | Migration |
| `APP php artisan make:test XxxTest` | Test |
| `docker compose up -d --build` | Build + start stack |
| `docker compose exec postgres psql -U postgres -c "CREATE DATABASE lighthouse_test;"` | Create the Postgres test DB (once) |
| `vendor\bin\pint` | Format/lint (Laravel preset; no `pint.json` in repo) |
| `composer dev` | Local (host) dev stack: `artisan serve`, `queue:listen`, `pail`, Vite concurrently (blocking) — **does NOT use Docker/Postgres** |
| `npm run dev` / `npm run build` | Vite dev server / production build |

## Endpoint conventions (target pattern — not implemented yet)

No API endpoints exist yet. When building them, follow the pattern proven in plant-doctor (verify each piece as you add it):

- **Routes** in `routes/api.php` (or split files), grouped with middleware (`auth:api`), using `Route::apiResource()` for CRUD, `prefix`/`middleware` groups for scoped routes.
- **Controllers** use `ApiResponseTrait` (single trait, helpers: `successResponse`, `errorResponse`, `validationErrorResponse`, `notFoundResponse`, `unauthorizedResponse`, `forbiddenResponse`). Every public method wraps logic in `try { ... } catch (ValidationException $e) { return $this->validationErrorResponse($e->errors()); } catch (Exception $e) { return $this->errorResponse('...', 500); }`.
- **NEVER use inline `$request->validate([...])`** in controllers. Validation lives in dedicated Form Request classes (`app/Http/Requests/`) with `authorize() => true` + `rules()`; controllers use `$request->validated()`.
- **Business logic in `app/Services/`**, not controllers.
- **Serialization** with API Resources (`app/Http/Resources/`), using `whenLoaded()`/`whenCounted()` for relations.
- **Standard JSON shape**: `{ "success": bool, "message": string, "data": ... }` (+ optional `errors`).

## Testing

- **Dedicated PostgreSQL test DB** (`lighthouse_test`) — required; `phpunit.xml` sets `DB_CONNECTION=pgsql` / `DB_DATABASE=lighthouse_test` / `DB_HOST=postgres`. Create it once: `docker compose exec postgres psql -U postgres -c "CREATE DATABASE lighthouse_test;"`.
- **NEVER run `php artisan test` directly with a cached config** — `RefreshDatabase` runs `migrate:fresh` against the resolved connection. If `bootstrap/cache/config.php` is cached with the dev DB (`lighthouse`), it wipes development data. Always run tests via `composer run test` (runs `config:clear` first).
- **Safety guard**: `tests/TestCase.php` aborts if the resolved database is not `lighthouse_test`.
- Suites: `tests/Unit`, `tests/Feature`.

## Environment and data

- Database is **PostgreSQL** in the container (`DB_CONNECTION=pgsql`, host `postgres`, DB `lighthouse`). The old SQLite file `database/database.sqlite` is retired/unused.
- `SESSION_DRIVER`, `QUEUE_CONNECTION`, and `CACHE_STORE` are all `database`, so the `0001_01_01_*` migrations must be applied before sessions/cache/queues work — notably, the queue worker needs the `jobs` table and Reverb needs the `cache` table.
- `BROADCAST_CONNECTION=reverb`; Reverb config keys in `.env` (`REVERB_*`), host port 8011.

## Gotchas

- `php`/`composer` on PATH run XAMPP PHP 8.2.4 and fail the platform check — always use the Laragon PHP 8.5.4 binary (host-side commands only).
- **No git repo, no CI** in this project yet; don't look for `.github/` workflows.
- `config:cache`/`route:cache` persist to `bootstrap/cache/`; clear (`config:clear`) before running tests so `.env`/`phpunit.xml` values apply. The container's `laravel-bootstrap` supervisord program runs these caches at startup (it exits after — that's normal).
- `octane:install` will fail inside the container because `.env` is mounted read-only; `config/octane.php` is already committed instead.
- Ports 8010/8011/5433 avoid collisions with plant-doctor (8005/8006/5432) so both stacks can run simultaneously.
