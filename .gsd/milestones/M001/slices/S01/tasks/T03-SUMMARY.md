---
id: T03
parent: S01
milestone: M001
key_files:
  - public/index.php
  - public/favicon.svg
  - src/Http/Request.php
  - src/Http/Response.php
  - src/Security/Csrf.php
  - src/Auth/AuthService.php
  - src/Http/Middleware/AuthMiddleware.php
  - src/Controllers/HomeController.php
  - src/Controllers/AuthController.php
  - src/Controllers/DashboardController.php
  - src/Http/Router.php
  - templates/layout.php
  - templates/auth/login.php
  - templates/auth/setup.php
  - templates/dashboard/index.php
  - public/assets/app.css
  - bin/console
  - tests/Feature/AuthGuardTest.php
  - README.md
key_decisions:
  - D005 — primeiro administrador via tela de setup bloqueada automaticamente, sem senha padrão.
duration: 
verification_result: mixed
completed_at: 2026-06-05T17:50:48.065Z
blocker_discovered: false
---

# T03: Implementados primeiro acesso seguro, login/logout, middleware de autenticação e dashboard protegido.

**Implementados primeiro acesso seguro, login/logout, middleware de autenticação e dashboard protegido.**

## What Happened

Foi implementado o fluxo de sessão e autenticação com `password_hash`, cookies HttpOnly/SameSite, token CSRF, logout, auth guard, papéis básicos no middleware, tela de criação do primeiro administrador, login protegido e dashboard inicial. O front controller agora usa PDO, AuthService, CSRF e Router reais. O console foi ajustado para não criar credenciais padrão: só cria admin por variáveis de ambiente explícitas ou pela tela de setup. O fluxo foi coberto por testes automatizados e verificado no navegador real.

## Verification

Executados lint PHP dos arquivos de autenticação e `composer test`; todos passaram. Também foi iniciado servidor PHP local, acessado `/login`, confirmado redirecionamento para `/setup/admin`, criado admin via formulário, carregado `/dashboard` e executadas asserções de URL, textos, ausência de console errors e ausência de failed requests.

## Verification Evidence

| # | Command | Exit Code | Verdict | Duration |
|---|---------|-----------|---------|----------|
| 1 | `php -l ... && composer test — exit 0, pass: OK (3 tests, 16 assertions)` | -1 | unknown (coerced from string) | 0ms |
| 2 | `browser_assert dashboard — pass: 5/5 checks, incluindo no_console_errors e no_failed_requests` | -1 | unknown (coerced from string) | 0ms |

## Deviations

Substituí o seed obrigatório de administrador por tela segura `/setup/admin` que só fica disponível antes do primeiro usuário. O console ainda suporta `--seed-admin`, mas apenas quando `ADMIN_EMAIL` e `ADMIN_PASSWORD` estão configurados no ambiente.

## Known Issues

Nenhum bloqueador. O banco local de desenvolvimento contém um admin de teste criado via browser UAT e fica ignorado pelo Git.

## Files Created/Modified

- `public/index.php`
- `public/favicon.svg`
- `src/Http/Request.php`
- `src/Http/Response.php`
- `src/Security/Csrf.php`
- `src/Auth/AuthService.php`
- `src/Http/Middleware/AuthMiddleware.php`
- `src/Controllers/HomeController.php`
- `src/Controllers/AuthController.php`
- `src/Controllers/DashboardController.php`
- `src/Http/Router.php`
- `templates/layout.php`
- `templates/auth/login.php`
- `templates/auth/setup.php`
- `templates/dashboard/index.php`
- `public/assets/app.css`
- `bin/console`
- `tests/Feature/AuthGuardTest.php`
- `README.md`
