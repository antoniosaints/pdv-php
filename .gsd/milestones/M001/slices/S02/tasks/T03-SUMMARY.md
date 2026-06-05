---
id: T03
parent: S02
milestone: M001
key_files:
  - src/Http/Router.php
  - src/Controllers/CatalogController.php
  - templates/catalog/show.php
  - templates/catalog/_variant_form.php
  - public/assets/app.css
  - public/favicon.ico
  - tests/Feature/CatalogVariantTest.php
  - tests/Catalog/CatalogLookupTest.php
key_decisions:
  - Endpoints de lookup do catálogo foram implementados dentro do monólito web como JSON autenticado, sem criar uma API separada.
duration: 
verification_result: mixed
completed_at: 2026-06-05T19:36:59.082Z
blocker_discovered: false
---

# T03: Adicionadas variantes, códigos de barras e lookup autenticado para o futuro PDV.

**Adicionadas variantes, códigos de barras e lookup autenticado para o futuro PDV.**

## What Happened

Foram adicionadas rotas protegidas para criar e atualizar variantes de produtos, formulário de variante no detalhe do catálogo, edição rápida de variantes existentes, validação por campo para SKU/barcode duplicado e endpoints JSON autenticados para lookup por código de barras e busca por termo. O detalhe do produto agora mostra variantes com SKU, barcode, preço específico, estoque e status. Os testes cobrem criação de variante via rota, erro acionável de barcode duplicado, endpoints JSON com dados prontos para venda, e exclusão de produtos/variantes inativas nos lookups.

## Verification

Executados lint dos arquivos alterados e `composer test`; PHPUnit passou com `OK (17 tests, 76 assertions)`. Browser UAT criou produto e variante com barcode `7890000000011`, verificou a variante no detalhe, abriu `/catalog/lookup/barcode?barcode=7890000000011` e `browser_assert` passou 5/5 incluindo `effective_price_cents:6490`, sem console errors e sem failed requests.

## Verification Evidence

| # | Command | Exit Code | Verdict | Duration |
|---|---------|-----------|---------|----------|
| 1 | `php -l ... && composer test — exit 0, pass: OK (17 tests, 76 assertions)` | -1 | unknown (coerced from string) | 0ms |
| 2 | `browser_assert JSON lookup — pass: 5/5 checks incluindo no_console_errors e no_failed_requests` | -1 | unknown (coerced from string) | 0ms |

## Deviations

Adicionado fallback `public/favicon.ico` para evitar 404 em endpoints JSON abertos diretamente no navegador. O endpoint JSON é protegido por sessão e retorna dados prontos para PDV.

## Known Issues

A UI de edição rápida de variantes dentro da tabela é funcional, mas visualmente densa; pode ser refinada quando o PDV consumir esses dados. O ledger real de estoque ainda será introduzido em S03/S05.

## Files Created/Modified

- `src/Http/Router.php`
- `src/Controllers/CatalogController.php`
- `templates/catalog/show.php`
- `templates/catalog/_variant_form.php`
- `public/assets/app.css`
- `public/favicon.ico`
- `tests/Feature/CatalogVariantTest.php`
- `tests/Catalog/CatalogLookupTest.php`
