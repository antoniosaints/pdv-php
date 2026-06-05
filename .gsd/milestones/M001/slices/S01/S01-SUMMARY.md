---
id: S01
parent: M001
milestone: M001
provides:
  - Composer PHP project structure and lockfile.
  - SQLite default database configuration with MySQL-compatible migration pattern.
  - Auth/session/CSRF/role middleware primitives.
  - Protected dashboard and health check routes.
  - CLI setup and install verification commands.
  - Test harness for future slices.
requires:
  []
affects:
  []
key_files:
  - composer.json
  - composer.lock
  - .env.example
  - README.md
  - public/index.php
  - public/assets/app.css
  - src/Database/ConnectionFactory.php
  - src/Database/Migrator.php
  - database/migrations/001_create_core_tables.php
  - src/Auth/AuthService.php
  - src/Http/Router.php
  - src/Controllers/AuthController.php
  - src/Controllers/DashboardController.php
  - src/Controllers/HealthController.php
  - templates/auth/login.php
  - templates/auth/setup.php
  - templates/dashboard/index.php
  - templates/health/index.php
  - bin/console
  - bin/verify-install.php
  - tests/Database/MigratorTest.php
  - tests/Feature/AuthGuardTest.php
  - tests/Feature/HealthTest.php
key_decisions:
  - D001 — PHP monolith with Composer, no separate API for MVP.
  - D002 — SQLite default via PDO with MySQL migration path.
  - D004 — microkernel PHP with FastRoute and Dotenv instead of Laravel/Symfony in MVP.
  - D005 — first administrator via locked setup screen, no default password.
  - D006 — `/health` protected by auth/admin with CLI verifier for install checks.
patterns_established:
  - Front controller composes services explicitly from `public/index.php`.
  - Domain code uses PDO and migrations instead of embedding raw setup in controllers.
  - First sensitive operational surfaces are protected by auth and role checks.
  - Verification combines PHPUnit, CLI install checks and browser assertions.
observability_surfaces:
  - `/health` protected admin page with PHP, DB, migration, storage, logs and environment status.
  - `php bin/verify-install.php` CLI verifier for hosting/install checks.
  - `schema_migrations` table for migration status.
  - `audit_logs` table for auth events.
  - PHP bootstrap error logging through `error_log`.
drill_down_paths:
  - .gsd/milestones/M001/slices/S01/tasks/T01-SUMMARY.md
  - .gsd/milestones/M001/slices/S01/tasks/T02-SUMMARY.md
  - .gsd/milestones/M001/slices/S01/tasks/T03-SUMMARY.md
  - .gsd/milestones/M001/slices/S01/tasks/T04-SUMMARY.md
duration: ""
verification_result: passed
completed_at: 2026-06-05T18:03:45.535Z
blocker_discovered: false
---

# S01: Instalacao base autenticacao e banco

**S01 delivered the installable, authenticated PHP foundation with SQLite migrations, dashboard and protected health diagnostics.**

## What Happened

S01 created the operational PHP foundation for the PDV project. The app now has Composer dependencies and scripts, an environment template, public front controller, responsive layout, PDO database setup, idempotent migrations, console migration command, secure first-admin setup, login/logout, session auth, CSRF protection, protected dashboard, protected health diagnostics and a CLI install verifier. The slice also added automated PHPUnit coverage for migration idempotency, auth guard behavior, first-admin setup lockout and health diagnostics. Browser UAT verified the real PHP server path: `/login` redirects to setup when no users exist, setup creates an admin, `/dashboard` loads protected content and `/health` shows healthy diagnostics without console or network errors.

## Verification

Fresh final verification after last code change: `composer test && php bin/verify-install.php` passed with PHPUnit `OK (5 tests, 23 assertions)` and install checks OK. Browser UAT for `/dashboard` and `/health` passed assertions including visible expected content, no console errors and no failed network requests.

## Requirements Advanced

- R001 — Clean Composer setup, README instructions, SQLite storage, migration runner and CLI verifier implemented.
- R002 — Authentication, secure session, CSRF, admin setup and protected routes implemented.
- R010 — PDO connection, migrations and SQLite/MySQL branch pattern established.
- R011 — Operational diagnostics, audit log table and install verifier established as foundation for backup/continuity.

## Requirements Validated

- R001 — `composer test && php bin/verify-install.php` passed; browser server on `127.0.0.1:8080` loaded setup/dashboard/health through the real entrypoint.
- R002 — `tests/Feature/AuthGuardTest.php` verifies dashboard redirects guests and first admin setup locks after user creation; browser UAT confirmed protected dashboard.
- R010 — `tests/Database/MigratorTest.php` verifies migrations are idempotent on SQLite; migration code includes explicit SQLite/MySQL branches behind PDO.

## New Requirements Surfaced

None.

## Requirements Invalidated or Re-scoped

None.

## Operational Readiness

None.

## Deviations

None.

## Known Limitations

S01 only establishes the install/auth/database/diagnostic foundation. Catalog, PDV, stock, printing and reports are planned but not implemented yet. The local SQLite database contains only development/test state and is ignored by Git.

## Follow-ups

Proceed to S02 catalog planning and implementation: product/service schema, variants, barcode lookup, CRUD screens, validation and seed data.

## Files Created/Modified

- `composer.json` — Composer project, dependencies, scripts and lockfile for PHP app.
- `.env.example` — Environment example with app, DB, session and log settings.
- `README.md` — Installation, setup, health and hosting notes.
- `public/index.php` — Front controller wiring environment, PDO, auth, routing and health check.
- `public/assets/app.css` — Responsive UI foundation and diagnostic/dashboard styling.
- `public/favicon.svg` — SVG favicon preventing browser 404 noise.
- `src/Database/ConnectionFactory.php` — PDO connection factory and migration infrastructure.
- `src/Database/Migration.php, src/Database/Migrator.php` — Migration contract and runner.
- `database/migrations/001_create_core_tables.php` — Core tables migration for users, audit logs and settings with SQLite/MySQL branches.
- `src/Auth/AuthService.php, src/Security/Csrf.php, src/Http/*.php` — Auth, CSRF, request, response, routing and middleware infrastructure.
- `src/Controllers/*.php, templates/**/*.php` — Home, auth, dashboard and health controllers plus templates.
- `bin/console, bin/verify-install.php` — Console migrations and install verifier.
- `phpunit.xml, tests/**/*.php` — PHPUnit configuration and S01 feature/database tests.
