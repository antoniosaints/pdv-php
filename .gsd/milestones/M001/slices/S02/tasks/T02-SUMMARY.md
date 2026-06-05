---
id: T02
parent: S02
milestone: M001
key_files:
  - src/Http/Router.php
  - public/index.php
  - src/Controllers/CatalogController.php
  - templates/layout.php
  - templates/catalog/index.php
  - templates/catalog/create.php
  - templates/catalog/edit.php
  - templates/catalog/show.php
  - templates/catalog/_form.php
  - public/assets/app.css
  - tests/Feature/CatalogCrudTest.php
key_decisions:
  - O CRUD de catálogo ficou restrito a papéis `admin` e `estoque` nas rotas protegidas.
duration: 
verification_result: mixed
completed_at: 2026-06-05T18:52:30.853Z
blocker_discovered: false
---

# T02: Implementado CRUD protegido de produtos e serviços no catálogo.

**Implementado CRUD protegido de produtos e serviços no catálogo.**

## What Happened

Foi implementado o CRUD web protegido de catálogo com listagem, busca por nome/SKU, criação, edição, ativação/inativação e detalhe de produto/serviço. O roteador agora aceita parâmetros de rota e recebe o repositório de catálogo. As telas usam templates server-rendered com formulário compartilhado, validação por campo, preço/custo em formato brasileiro, checkboxes para estoque e status, tabela responsiva e navegação autenticada com link para Catálogo. Testes de feature cobrem autenticação obrigatória, criação, validação, atualização e toggle de status.

## Verification

Executados lint dos arquivos alterados e `composer test`; PHPUnit passou com `OK (13 tests, 58 assertions)`. Browser UAT criou produto real e `browser_assert` passou 7/7 verificando URL `/catalog/`, nome, preço, SKU, estoque controlado, sem console errors e sem failed requests.

## Verification Evidence

| # | Command | Exit Code | Verdict | Duration |
|---|---------|-----------|---------|----------|
| 1 | `php -l ... && composer test — exit 0, pass: OK (13 tests, 58 assertions)` | -1 | unknown (coerced from string) | 0ms |
| 2 | `browser_assert catalog detail — pass: 7/7 checks incluindo no_console_errors e no_failed_requests` | -1 | unknown (coerced from string) | 0ms |

## Deviations

O helper `browser_fill_form` reportou erro interno após enviar o formulário, mas a aplicação persistiu o produto e a verificação explícita por `browser_assert` passou. A página de detalhe já exibe área de variantes como placeholder para T03.

## Known Issues

A gestão de variantes e barcodes ainda não está ligada à UI; isso é o escopo da T03. O browser helper teve falha interna, mas a ação foi comprovada por asserções posteriores.

## Files Created/Modified

- `src/Http/Router.php`
- `public/index.php`
- `src/Controllers/CatalogController.php`
- `templates/layout.php`
- `templates/catalog/index.php`
- `templates/catalog/create.php`
- `templates/catalog/edit.php`
- `templates/catalog/show.php`
- `templates/catalog/_form.php`
- `public/assets/app.css`
- `tests/Feature/CatalogCrudTest.php`
