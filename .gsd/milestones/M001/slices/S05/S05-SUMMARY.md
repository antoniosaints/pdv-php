---
id: S05
parent: M001
milestone: M001
provides:
  - StockRepository low-stock, tracked variant and recent movement methods
  - Protected `/stock` page
  - Protected replenishment and adjustment POST routes
  - Movement ledger rows for purchase and adjustment types
  - Stock navigation/dashboard entry points
requires:
  - slice: S02
    provides: Product/variant current stock and stock minimum values.
  - slice: S03
    provides: Existing stock_movements ledger and sale movement pattern.
affects:
  - S07 can consume stock status and movement history for low-stock dashboard/report cards
  - S08 can include stock replenishment and adjustment in final UAT
key_files:
  - src/Stock/StockRepository.php
  - src/Stock/StockValidator.php
  - src/Stock/ValidationException.php
  - src/Controllers/StockController.php
  - templates/stock/index.php
  - tests/Stock/StockRepositoryTest.php
  - tests/Feature/StockFlowTest.php
  - README.md
key_decisions:
  - S05 reuses the S03 `stock_movements` table instead of adding a separate stock-entry table.
  - Manual replenishment uses movement type `purchase`; manual corrections use movement type `adjustment`.
  - Adjustments require a reason and cannot make current stock negative.
patterns_established:
  - Manual stock changes are transactionally coupled to `product_variants.current_stock` and `stock_movements`.
  - Low-stock status is computed from active tracked variants and product stock minimums.
  - Operational stock diagnostics are exposed in a protected server-rendered page.
observability_surfaces:
  - Stock page summary metrics for tracked variants, low-stock count and movement count.
  - Low-stock alert cards with current and minimum quantities.
  - Movement history showing type, delta, before/after and reason.
  - Validation messages for invalid variant, invalid quantity, missing adjustment reason and negative resulting stock.
drill_down_paths:
  - .gsd/milestones/M001/slices/S05/tasks/T01-SUMMARY.md
  - .gsd/milestones/M001/slices/S05/tasks/T02-SUMMARY.md
  - .gsd/milestones/M001/slices/S05/tasks/T03-SUMMARY.md
  - .gsd/milestones/M001/slices/S05/tasks/T04-SUMMARY.md
duration: ""
verification_result: passed
completed_at: 2026-06-06T00:26:16.953Z
blocker_discovered: false
---

# S05: Reposicao e controle de estoque

**S05 delivered stock replenishment, adjustments, low-stock alerts and movement history.**

## What Happened

S05 delivered operational stock control. The app now lists tracked variants, flags variants at or below stock minimum, records replenishment entries, records positive/negative adjustments with required reasons, prevents negative resulting stock, and shows recent stock movements with before/after quantities. These operations reuse the shared `stock_movements` ledger introduced for sales, so sales, purchases and adjustments are inspectable in one history. Browser UAT verified the protected stock page, a low-stock item, a replenishment submission and updated movement history through the real PHP entrypoint.

## Verification

Fresh verification passed: PHPUnit OK with 41 tests and 207 assertions; install verifier passed with 3 migrations and 0 pending; browser UAT validated `/stock`, low-stock visibility, replenishment submission and movement history with no console/network failures.

## Requirements Advanced

- R007 — Implemented low-stock alerts, manual replenishment entries, manual adjustments and movement history using shared stock ledger.

## Requirements Validated

- R007 — Validated by S05 repository tests, feature tests and browser UAT showing low-stock product, replenishment submission and movement history.

## New Requirements Surfaced

None.

## Requirements Invalidated or Re-scoped

None.

## Operational Readiness

None.

## Deviations

None.

## Known Limitations

S05 implements manual replenishment and manual adjustment at MVP depth. It does not yet model suppliers, purchase orders, receiving documents or automatic reorder suggestions beyond low-stock alerts.

## Follow-ups

S07 should use `StockRepository::lowStockVariants()` and `recentMovements()` for dashboard/report cards. S08 should verify stock replenishment/adjustment in final end-to-end UAT. Advanced purchase order workflow can be deferred to later milestones if needed.

## Files Created/Modified

- `src/Stock/StockRepository.php` — Added transactional stock repository for tracked variants, low-stock detection, replenishment, adjustments and movement history.
- `src/Stock/StockValidator.php` — Added stock input normalization and validation.
- `src/Stock/ValidationException.php` — Added stock validation exception.
- `src/Controllers/StockController.php` — Added protected stock controller and forms.
- `src/Http/Router.php` — Wired stock routes and repository injection.
- `public/index.php` — Wired StockRepository into app bootstrap.
- `templates/stock/index.php` — Added stock screen template with alerts, forms, balances and movement history.
- `templates/layout.php` — Added stock link to authenticated navigation and dashboard action.
- `templates/dashboard/index.php` — Added dashboard stock CTA.
- `public/assets/app.css` — Added stock-specific CSS.
- `README.md` — Documented `/stock` and shared stock movement ledger behavior.
- `tests/Stock/StockRepositoryTest.php` — Added repository and feature tests for stock flows.
- `tests/Feature/StockFlowTest.php` — Added browser-oriented feature tests for stock page/replenishment/validation.
