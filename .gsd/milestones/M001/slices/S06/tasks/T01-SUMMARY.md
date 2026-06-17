---
id: T01
parent: S06
milestone: M001
key_files:
  - database/migrations/004_create_service_orders_tables.php
  - src/ServiceOrders/ServiceOrderRepository.php
  - src/ServiceOrders/ServiceOrderValidator.php
  - src/ServiceOrders/ValidationException.php
  - tests/ServiceOrders/ServiceOrderRepositoryTest.php
  - tests/Database/MigratorTest.php
key_decisions:
  - Service orders get their own `service_orders`, `service_order_items` and `service_order_status_history` tables while reusing catalog item snapshots compatible with sales.
  - Manual status changes exclude `closed`; closure will be handled by the sale-finalization path in T03 to avoid orders being closed without payment/sale linkage.
duration: 
verification_result: mixed
completed_at: 2026-06-06T11:26:30.309Z
blocker_discovered: false
---

# T01: Created the service-order schema and repository domain with tests.

**Created the service-order schema and repository domain with tests.**

## What Happened

Implemented the S06 service-order domain foundation. Added migration 004 with service order, item snapshot and status history tables for SQLite/MySQL. Added ServiceOrderValidator, ValidationException and ServiceOrderRepository with order creation, item snapshotting, totals calculation, list/find helpers, status history retrieval and manual status changes. Added repository tests for table creation, successful order creation with service/product items, invalid input, rollback on unavailable/excessive-discount items, status history and closed-status protection. Updated MigratorTest to include the new migration and tables.

## Verification

`php -l` passed on the new migration, ServiceOrders classes and updated tests. `composer test` passed: 47 tests, 256 assertions.

## Verification Evidence

| # | Command | Exit Code | Verdict | Duration |
|---|---------|-----------|---------|----------|
| 1 | `php -l database/migrations/004_create_service_orders_tables.php && php -l src/ServiceOrders/ValidationException.php && php -l src/ServiceOrders/ServiceOrderValidator.php && php -l src/ServiceOrders/ServiceOrderRepository.php && php -l tests/ServiceOrders/ServiceOrderRepositoryTest.php && php -l tests/Database/MigratorTest.php — pass, no syntax errors detected.` | -1 | unknown (coerced from string) | 0ms |
| 2 | `composer test — pass, PHPUnit OK (47 tests, 256 assertions).` | -1 | unknown (coerced from string) | 0ms |

## Deviations

None.

## Known Issues

None.

## Files Created/Modified

- `database/migrations/004_create_service_orders_tables.php`
- `src/ServiceOrders/ServiceOrderRepository.php`
- `src/ServiceOrders/ServiceOrderValidator.php`
- `src/ServiceOrders/ValidationException.php`
- `tests/ServiceOrders/ServiceOrderRepositoryTest.php`
- `tests/Database/MigratorTest.php`
