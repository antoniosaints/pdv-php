---
estimated_steps: 1
estimated_files: 10
skills_used: []
---

# T03: Implementar autenticacao e dashboard protegido

Implement secure session setup, password hashing, login/logout routes, auth guard, role checks and default admin seeding through the setup command. Add protected dashboard shell and tests proving protected routes redirect unauthenticated users.

## Inputs

- `src/Database/ConnectionFactory.php`
- `src/Database/Migrator.php`
- `public/index.php`
- `templates/layout.php`

## Expected Output

- `src/Auth/AuthService.php`
- `src/Http/Router.php`
- `src/Http/Middleware/AuthMiddleware.php`
- `src/Controllers/AuthController.php`
- `src/Controllers/DashboardController.php`
- `templates/auth/login.php`
- `templates/dashboard/index.php`
- `tests/Feature/AuthGuardTest.php`

## Verification

composer test

## Observability Impact

Authentication failures expose safe user-facing errors while server logs retain contextual non-secret route/session information.
