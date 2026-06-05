---
id: T04
parent: S01
milestone: M001
key_files:
  - src/Support/HealthCheck.php
  - src/Controllers/HealthController.php
  - src/Http/Router.php
  - public/index.php
  - templates/health/index.php
  - public/assets/app.css
  - bin/verify-install.php
  - storage/logs/.gitkeep
  - storage/database/.gitkeep
  - tests/Feature/HealthTest.php
  - README.md
key_decisions:
  - D006 — health check protegido por login, com CLI para verificação pré-operacional.
duration: 
verification_result: mixed
completed_at: 2026-06-05T18:00:54.124Z
blocker_discovered: false
---

# T04: Adicionado diagnóstico operacional protegido e verificador CLI de instalação.

**Adicionado diagnóstico operacional protegido e verificador CLI de instalação.**

## What Happened

Foi criado um HealthCheck reutilizável para web, CLI e testes. A página `/health` agora é uma rota protegida para admin e mostra PHP, banco, migrations, storage, logs e ambiente sem segredos. O script `bin/verify-install.php` permite validar a instalação pelo terminal, útil em hospedagem e antes do primeiro login. O README foi atualizado com a etapa de verificação, e a interface recebeu estilos para estados OK/WARN/FAIL.

## Verification

Executados lint dos novos arquivos, `composer test`, `php bin/verify-install.php` e verificação no navegador em `/health`. PHPUnit passou com 5 testes e 23 assertions; CLI retornou todos checks OK; browser_assert passou 7/7 sem console errors ou failed requests.

## Verification Evidence

| # | Command | Exit Code | Verdict | Duration |
|---|---------|-----------|---------|----------|
| 1 | `php -l ... && composer test — exit 0, pass: OK (5 tests, 23 assertions)` | -1 | unknown (coerced from string) | 0ms |
| 2 | `php bin/verify-install.php — exit 0, pass: todos checks OK e instalação verificada` | -1 | unknown (coerced from string) | 0ms |
| 3 | `browser_assert /health — pass: 7/7 checks incluindo no_console_errors e no_failed_requests` | -1 | unknown (coerced from string) | 0ms |

## Deviations

O health web foi protegido por autenticação/admin em vez de público, e o script CLI cobre a verificação antes do primeiro login.

## Known Issues

Nenhum bloqueador. O verificador mostra path relativo do SQLite para operação; como a página é protegida, isso é aceitável para diagnóstico administrativo.

## Files Created/Modified

- `src/Support/HealthCheck.php`
- `src/Controllers/HealthController.php`
- `src/Http/Router.php`
- `public/index.php`
- `templates/health/index.php`
- `public/assets/app.css`
- `bin/verify-install.php`
- `storage/logs/.gitkeep`
- `storage/database/.gitkeep`
- `tests/Feature/HealthTest.php`
- `README.md`
