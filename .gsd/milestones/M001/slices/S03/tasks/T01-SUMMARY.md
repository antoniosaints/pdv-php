---
id: T01
parent: S03
milestone: M001
key_files:
  - database/migrations/003_create_sales_tables.php
  - tests/Database/MigratorTest.php
  - tests/Sales/SaleRepositoryTest.php
key_decisions: []
duration: 
verification_result: passed
completed_at: 2026-06-05T22:22:34.214Z
blocker_discovered: false
---

# T01: Created the sales, payment and stock movement schema for the PDV flow and verified it with PHPUnit.

**Created the sales, payment and stock movement schema for the PDV flow and verified it with PHPUnit.**

## What Happened

Added the S03 sales persistence migration with portable SQLite/MySQL tables for sales, sale_items, sale_payments and stock_movements. The schema stores money as integer cents, completed sale status/timestamps, item snapshots, payment facts and auditable stock movement references. Updated the migration test to include the new tables and added a Sales schema test that creates catalog data, inserts a sale, item, payment and stock movement, then verifies the persisted facts.

## Verification

Fresh verification after the last code change passed: `PATH="/c/php:$PATH" /c/php/php.exe vendor/bin/phpunit` returned PHPUnit OK with 19 tests and 89 assertions.

## Verification Evidence

| # | Command | Exit Code | Verdict | Duration |
|---|---------|-----------|---------|----------|
| 1 | `PATH="/c/php:$PATH" /c/php/php.exe vendor/bin/phpunit` | 0 | ✅ pass | 6500ms |

## Deviations

Composer test script could not run directly in this Git Bash environment because php/phpunit were not resolved on PATH. Verification used explicit PHP binary with vendor PHPUnit after composer install completed.

## Known Issues

Local shell PATH does not expose php by default; use PATH="/c/php:$PATH" or /c/php/php.exe for verification in this environment.

## Files Created/Modified

- `database/migrations/003_create_sales_tables.php`
- `tests/Database/MigratorTest.php`
- `tests/Sales/SaleRepositoryTest.php`
