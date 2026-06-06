---
id: T04
parent: S03
milestone: M001
key_files:
  - tests/Feature/CatalogSeedTest.php
  - README.md
  - templates/dashboard/index.php
key_decisions: []
duration: 
verification_result: passed
completed_at: 2026-06-05T22:51:14.463Z
blocker_discovered: false
---

# T04: Validated seeded PDV data and completed browser UAT for a real sale with automatic stock decrement.

**Validated seeded PDV data and completed browser UAT for a real sale with automatic stock decrement.**

## What Happened

Strengthened the catalog seed verification so seeded demo data is not only lookup-ready but can complete a real repository-level PDV sale with stock decrement and payment persistence. Updated README with the PDV route, demo barcodes and sale-detail diagnostic surface. Ran migrations, seed and install verification against the local SQLite database. Then performed browser UAT against the real PHP entrypoint: created a local admin through setup, opened the dashboard, opened `/pos?barcode=7891000000010`, finalized the demo sale and confirmed the sale detail page showed the item, total, payment and stock movement from 12 to 11.

## Verification

Fresh verification passed: PHPUnit OK with 28 tests and 136 assertions; `bin/console migrate && bin/console seed:catalog && bin/verify-install.php` reported 3 migrations executed, 0 pending and installation verified; browser assertions passed on `/sales/1` for `Venda concluída`, `R$ 64,90`, `12 → 11`, no console errors and no failed requests.

## Verification Evidence

| # | Command | Exit Code | Verdict | Duration |
|---|---------|-----------|---------|----------|
| 1 | `PATH="/c/php:$PATH" /c/php/php.exe vendor/bin/phpunit` | 0 | ✅ pass | 6900ms |
| 2 | `PATH="/c/php:$PATH" /c/php/php.exe bin/console migrate && /c/php/php.exe bin/console seed:catalog && /c/php/php.exe bin/verify-install.php` | 0 | ✅ pass | 6100ms |
| 3 | `Browser UAT: setup admin, open /pos?barcode=7891000000010, finalize sale, assert /sales/1 shows Venda concluída, R$ 64,90 and 12 → 11 with no console errors or failed requests` | 0 | ✅ pass | 0ms |

## Deviations

Browser UAT used a local admin created through `/setup/admin` and the local SQLite database. Composer script remains environment-sensitive, so test commands used the explicit `/c/php/php.exe` binary.

## Known Issues

The local UAT database now contains demo seed data, one UAT admin user and one completed sale from the browser verification. This is local development state only.

## Files Created/Modified

- `tests/Feature/CatalogSeedTest.php`
- `README.md`
- `templates/dashboard/index.php`
