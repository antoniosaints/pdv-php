---
id: S03
parent: M001
milestone: M001
provides:
  - sales, sale_items, sale_payments and stock_movements tables
  - Transactional sale finalization through SalesRepository
  - Protected /pos and /sales/{id} routes
  - Completed sale detail surface for receipt/report/diagnostic consumers
  - Seeded product/service data proven usable by PDV
requires:
  - slice: S02
    provides: Catalog lookup methods, product/service variants, barcode data and current stock balances.
affects:
  - S04 consumes sale detail and receipt data shape
  - S05 consumes stock_movements ledger and current_stock decrement
  - S07 consumes sales, items, payments and movement facts
key_files:
  - database/migrations/003_create_sales_tables.php
  - src/Sales/SalesRepository.php
  - src/Sales/SalesValidator.php
  - src/Sales/ValidationException.php
  - src/Controllers/SalesController.php
  - src/Http/Router.php
  - src/Http/Request.php
  - templates/sales/pos.php
  - templates/sales/show.php
  - tests/Sales/SaleRepositoryTest.php
  - tests/Feature/SalesFlowTest.php
  - tests/Feature/CatalogSeedTest.php
  - README.md
key_decisions:
  - Sale finalization is server-rendered and CSRF-protected, with optional JSON barcode lookup for future interactive enhancement.
  - Money remains integer cents across catalog and sales.
  - Stock decrement and stock movement insertion occur inside one PDO transaction.
  - Services can be sold through the same sale flow but do not create physical stock movements.
patterns_established:
  - Sale facts use snapshots of product/variant names, barcode, price and cost for stable receipts/reports.
  - Stock-affecting sale operations are transactionally coupled to ledger movement rows.
  - Server-rendered forms remain the default operational path; JSON endpoints support progressive enhancement.
observability_surfaces:
  - Completed sale detail page with sale code/status/timestamps, totals, payments and stock movements.
  - stock_movements ledger rows recording before/after quantity and sale reference.
  - Validation messages for missing items, insufficient payment, unavailable items and insufficient stock.
  - Browser UAT asserts no console errors and no failed requests.
drill_down_paths:
  - .gsd/milestones/M001/slices/S03/tasks/T01-SUMMARY.md
  - .gsd/milestones/M001/slices/S03/tasks/T02-SUMMARY.md
  - .gsd/milestones/M001/slices/S03/tasks/T03-SUMMARY.md
  - .gsd/milestones/M001/slices/S03/tasks/T04-SUMMARY.md
duration: ""
verification_result: passed
completed_at: 2026-06-05T22:53:16.152Z
blocker_discovered: false
---

# S03: PDV vendas e baixa automatica

**S03 delivered the PDV sale flow with payment recording and automatic stock decrement.**

## What Happened

S03 delivered the first complete PDV sale loop. The app now has portable sales persistence tables, a transactional sale repository, validation for items/payments/stock, protected POS routes, a server-rendered sale screen, completed sale detail pages, and tests covering both happy path and rollback paths. A browser UAT against the real PHP entrypoint created an admin, selected the seeded product barcode, finalized a sale and confirmed stock decreased automatically from 12 to 11 on the sale detail diagnostic surface.

## Verification

Fresh verification passed: PHPUnit OK with 28 tests and 136 assertions; install verifier passed with 3 migrations and 0 pending; browser UAT completed a real sale through the PHP entrypoint and confirmed the sale detail page showed payment and stock movement from 12 to 11.

## Requirements Advanced

- R004 — Introduced stock movement ledger and automatic current_stock decrement during completed product sales.
- R005 — Delivered authenticated PDV route with barcode/search item selection, quantity, discount, payment method and sale finalization.

## Requirements Validated

- R004 — Validated by repository tests, feature tests and browser UAT showing sale stock movement `12 → 11` after finalization.
- R005 — Validated by protected POS UI, feature tests for sale finalization and browser UAT completing a barcode-driven sale with payment.

## New Requirements Surfaced

None.

## Requirements Invalidated or Re-scoped

None.

## Operational Readiness

None.

## Deviations

None.

## Known Limitations

The POS cart is currently server-rendered with one selected item per GET selection. The backend already accepts multiple item rows, but a richer scanner-driven multi-item cart UI can be added later with lightweight JavaScript. The local UAT database contains a demo admin and sale from verification.

## Follow-ups

S04 should use `SalesRepository::findSale()`, `itemsForSale()`, and `paymentsForSale()` for receipt preview/print data. S05 should reuse `stock_movements` and may add replenishment/adjustment movement types. A future UI improvement can add JavaScript cart accumulation for multiple scanned items without changing the server contract.

## Files Created/Modified

- `database/migrations/003_create_sales_tables.php` — Added sales, sale item, payment and stock movement tables for SQLite/MySQL.
- `src/Sales/SalesRepository.php` — Added transactional sale finalization, validation and sale inspection methods.
- `src/Sales/SalesValidator.php` — Added sale input normalization and validation for items/payments.
- `src/Controllers/SalesController.php` — Added protected POS routes, sale finalization, sale detail and barcode JSON lookup.
- `src/Http/Router.php` — Wired SalesRepository into the app router and bootstrap.
- `src/Http/Request.php` — Added nested POST array support for cart/payment data.
- `templates/sales/pos.php` — Added POS and sale detail pages.
- `templates/layout.php` — Added POS navigation and dashboard entry point.
- `public/assets/app.css` — Added responsive POS styling.
- `tests/Sales/SaleRepositoryTest.php` — Added repository, feature and seed coverage for PDV sale flow.
- `tests/Feature/SalesFlowTest.php` — Added browser-oriented feature tests for POS routes and stock decrement.
