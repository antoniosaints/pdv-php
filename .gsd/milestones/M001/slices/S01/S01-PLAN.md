# S01: Instalacao base autenticacao e banco

**Goal:** Create the installable PHP foundation that proves hosting, routing, authentication, migrations, database portability patterns and diagnostics work together.
**Demo:** A clean checkout can install dependencies, create the SQLite schema, log in as admin and show a protected dashboard shell with database and health diagnostics.

## Must-Haves

- Composer install and setup command create a runnable PHP app with SQLite.
- Protected routes require login and role checks.
- Migration runner creates base tables idempotently.
- Health page shows app, DB and migration status.
- Test command verifies routing, DB connection and auth guard behavior.

## Proof Level

- This slice proves: operational

## Integration Closure

Produces the real PHP entrypoint, routing, middleware, layout, migration runner and health surface consumed by every later slice. Remaining milestone work adds domain modules on top.

## Verification

- Adds health page, structured PHP error logging, migration status and database path visibility without leaking secrets.

## Tasks

- [x] **T01: Criar esqueleto instalavel PHP Composer** `est:1h`
  Create the base Composer PHP application structure for a front-controller app. Add `composer.json`, PSR-4 autoloading, `.env.example`, `public/index.php`, config loading, simple response helpers, layout/view renderer, base CSS and README setup notes. Done when Composer validates and the public entrypoint can bootstrap without a database.
  - Files: `composer.json`, `.env.example`, `README.md`, `public/index.php`, `public/assets/app.css`, `src/Support/Env.php`, `src/Http/Response.php`, `src/View/View.php`, `templates/layout.php`
  - Verify: composer validate

- [x] **T02: Adicionar PDO SQLite e migrations** `est:1h30m`
  Add database configuration, PDO connection factory, migration runner and initial migrations for schema bookkeeping, users, products placeholder-independent audit/log tables and app settings. Add a console command to run migrations and a PHP test command that proves migrations are idempotent on SQLite.
  - Files: `config/database.php`, `src/Database/ConnectionFactory.php`, `src/Database/Migrator.php`, `database/migrations/001_create_core_tables.php`, `bin/console`, `tests/Database/MigratorTest.php`, `phpunit.xml`
  - Verify: composer test

- [x] **T03: Implementar autenticacao e dashboard protegido** `est:2h`
  Implement secure session setup, password hashing, login/logout routes, auth guard, role checks and default admin seeding through the setup command. Add protected dashboard shell and tests proving protected routes redirect unauthenticated users.
  - Files: `src/Auth/AuthService.php`, `src/Http/Router.php`, `src/Http/Middleware/AuthMiddleware.php`, `src/Controllers/AuthController.php`, `src/Controllers/DashboardController.php`, `templates/auth/login.php`, `templates/dashboard/index.php`, `tests/Feature/AuthGuardTest.php`, `database/migrations/001_create_core_tables.php`, `bin/console`
  - Verify: composer test

- [x] **T04: Adicionar health check e verificacao de instalacao** `est:1h30m`
  Add operational health diagnostics, install verification and final S01 documentation. Health must show PHP version, database connectivity, migration status, writable storage/log paths and current environment without secrets. Add a verification script for clean setup and document shared-hosting deployment assumptions.
  - Files: `src/Controllers/HealthController.php`, `templates/health/index.php`, `bin/verify-install.php`, `storage/logs/.gitkeep`, `storage/database/.gitkeep`, `tests/Feature/HealthTest.php`, `README.md`, `src/Http/Router.php`
  - Verify: composer test

## Files Likely Touched

- composer.json
- .env.example
- README.md
- public/index.php
- public/assets/app.css
- src/Support/Env.php
- src/Http/Response.php
- src/View/View.php
- templates/layout.php
- config/database.php
- src/Database/ConnectionFactory.php
- src/Database/Migrator.php
- database/migrations/001_create_core_tables.php
- bin/console
- tests/Database/MigratorTest.php
- phpunit.xml
- src/Auth/AuthService.php
- src/Http/Router.php
- src/Http/Middleware/AuthMiddleware.php
- src/Controllers/AuthController.php
- src/Controllers/DashboardController.php
- templates/auth/login.php
- templates/dashboard/index.php
- tests/Feature/AuthGuardTest.php
- src/Controllers/HealthController.php
- templates/health/index.php
- bin/verify-install.php
- storage/logs/.gitkeep
- storage/database/.gitkeep
- tests/Feature/HealthTest.php
