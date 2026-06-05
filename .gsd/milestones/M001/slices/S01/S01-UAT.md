# S01: Instalacao base autenticacao e banco — UAT

**Milestone:** M001
**Written:** 2026-06-05T18:03:45.536Z

# S01 UAT — Instalação base, autenticação e banco

## Preconditions

- Composer dependencies installed.
- SQLite database migrated with `php bin/console migrate`.
- PHP dev server running at `http://127.0.0.1:8080`.

## Scenario 1 — First admin setup

1. Navigate to `/login` before any user exists.
2. Confirm the app redirects to `/setup/admin`.
3. Fill name, email, password and confirmation.
4. Submit the form.
5. Confirm the app redirects to `/dashboard`.

Expected: dashboard shows "Base operacional pronta." and the logged-in admin name.

## Scenario 2 — Protected dashboard

1. With admin session active, open `/dashboard`.
2. Confirm status cards show base, catalog next module and SQLite.
3. Confirm there are no browser console errors or failed requests.

Expected: page is protected and renders operational shell.

## Scenario 3 — Health diagnostics

1. With admin session active, open `/health`.
2. Confirm the page says "Sistema saudável.".
3. Confirm checks for PHP, Banco de dados, Migrations, Storage, Logs and Ambiente.
4. Run `php bin/verify-install.php` in terminal.

Expected: browser diagnostics and CLI verifier both report OK.

## Evidence

- `composer test && php bin/verify-install.php` passed with PHPUnit `OK (5 tests, 23 assertions)` and all install checks OK.
- Browser assertions passed for `/dashboard` and `/health`, including no console errors and no failed requests.

