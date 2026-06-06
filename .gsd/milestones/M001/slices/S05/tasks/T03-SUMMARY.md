---
id: T03
parent: S05
milestone: M001
key_files:
  - templates/layout.php
  - templates/dashboard/index.php
  - README.md
  - tests/Feature/StockFlowTest.php
key_decisions: []
duration: 
verification_result: passed
completed_at: 2026-06-06T00:21:53.279Z
blocker_discovered: false
---

# T03: Linked stock controls into navigation/dashboard and documented stock movement behavior.

**Linked stock controls into navigation/dashboard and documented stock movement behavior.**

## What Happened

Linked the stock control surface into normal navigation. The main layout now includes an `Estoque` link for authenticated users and the dashboard action list includes `Controlar estoque`. README now documents `/stock`, low-stock alerts, replenishment, adjustments, movement history and the shared `stock_movements` ledger used by sales, replenishments and adjustments. Feature tests now assert that operational navigation exposes `/stock`.

## Verification

Fresh verification after the last code change passed: `PATH="/c/php:$PATH" /c/php/php.exe vendor/bin/phpunit` returned PHPUnit OK with 41 tests and 207 assertions. Syntax checks passed for layout, dashboard and StockFlowTest.

## Verification Evidence

| # | Command | Exit Code | Verdict | Duration |
|---|---------|-----------|---------|----------|
| 1 | `PATH="/c/php:$PATH" /c/php/php.exe vendor/bin/phpunit` | 0 | ✅ pass | 9900ms |
| 2 | `PATH="/c/php:$PATH" /c/php/php.exe -l templates/layout.php && /c/php/php.exe -l templates/dashboard/index.php && /c/php/php.exe -l tests/Feature/StockFlowTest.php` | 0 | ✅ pass | 6500ms |

## Deviations

None.

## Known Issues

None.

## Files Created/Modified

- `templates/layout.php`
- `templates/dashboard/index.php`
- `README.md`
- `tests/Feature/StockFlowTest.php`
