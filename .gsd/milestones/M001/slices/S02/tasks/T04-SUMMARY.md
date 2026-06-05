---
id: T04
parent: S02
milestone: M001
key_files:
  - bin/console
  - database/seeders/catalog_seed.php
  - templates/dashboard/index.php
  - templates/catalog/index.php
  - README.md
  - tests/Feature/CatalogSeedTest.php
key_decisions:
  - Dados demo de catálogo são opcionais via `php bin/console seed:catalog`, para não poluir instalações reais automaticamente.
duration: 
verification_result: mixed
completed_at: 2026-06-05T19:51:13.223Z
blocker_discovered: false
---

# T04: Adicionado seed opcional de catálogo e fechamento operacional para dados demo do PDV.

**Adicionado seed opcional de catálogo e fechamento operacional para dados demo do PDV.**

## What Happened

Foi adicionado um seeder opcional de catálogo com um produto físico demo, variante, código de barras e estoque inicial, além de um serviço demo sem controle de estoque. O console ganhou o comando `seed:catalog`; o README documenta o uso; o dashboard ganhou CTA para abrir catálogo; e a listagem foi atualizada para refletir que variantes e códigos de barras já fazem parte do catálogo. O teste de seed garante que os barcodes demo são localizáveis e que o serviço demo não controla estoque físico.

## Verification

Executados `php -l bin/console`, `php -l database/seeders/catalog_seed.php`, `php -l tests/Feature/CatalogSeedTest.php`, `composer test`, `php bin/console seed:catalog`, `php -l templates/catalog/index.php` e nova rodada de `composer test`. A suíte final passou com `OK (18 tests, 81 assertions)`. Browser assert na listagem passou 5/5 vendo Camiseta Demo, Ajuste de Barra Demo e sem console/network errors.

## Verification Evidence

| # | Command | Exit Code | Verdict | Duration |
|---|---------|-----------|---------|----------|
| 1 | `php -l ... && composer test && php bin/console seed:catalog — exit 0, pass: OK (18 tests, 81 assertions) e seed criado` | -1 | unknown (coerced from string) | 0ms |
| 2 | `php -l templates/catalog/index.php && composer test — exit 0, pass: OK (18 tests, 81 assertions)` | -1 | unknown (coerced from string) | 0ms |
| 3 | `browser_assert /catalog — pass: 5/5 incluindo dados demo, no_console_errors e no_failed_requests` | -1 | unknown (coerced from string) | 0ms |

## Deviations

Adicionado comando `seed:catalog` em vez de misturar seed demo no `composer setup`, mantendo setup mínimo e seed opcional.

## Known Issues

O comando de seed é idempotente por checagem de SKU demo no catálogo; se dados forem alterados manualmente com os mesmos SKUs, ele pode ignorar recriação, o que é aceitável para seed de demonstração.

## Files Created/Modified

- `bin/console`
- `database/seeders/catalog_seed.php`
- `templates/dashboard/index.php`
- `templates/catalog/index.php`
- `README.md`
- `tests/Feature/CatalogSeedTest.php`
