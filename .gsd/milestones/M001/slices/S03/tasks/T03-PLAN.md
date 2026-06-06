---
estimated_steps: 1
estimated_files: 7
skills_used: []
---

# T03: Adicionar rotas e interface responsiva do PDV

Wire SalesRepository into Router and add SalesController with protected caixa/admin routes for /pos, barcode JSON add, search-backed item selection, cart form state, discount/quantity fields, payment method and finalize action. Add templates and navigation. The UI may be server-rendered with lightweight JavaScript for barcode/search interactions, but finalization must post a normal CSRF-protected form. Feature tests must cover auth guard, GET PDV, successful finalize redirect and validation message for insufficient stock.

## Inputs

- `src/Sales/SalesRepository.php`
- `src/Catalog/CatalogRepository.php`
- `src/Security/Csrf.php`
- `templates/layout.php`

## Expected Output

- `src/Controllers/SalesController.php`
- `templates/sales/pos.php`
- `templates/sales/show.php`
- `tests/Feature/SalesFlowTest.php`

## Verification

composer test

## Observability Impact

Completed sale detail page shows sale id/status/items/payments and stock movement effect for diagnostics.
