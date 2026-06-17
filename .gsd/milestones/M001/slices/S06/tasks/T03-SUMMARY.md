---
id: T03
parent: S06
milestone: M001
key_files:
  - src/Sales/SalesRepository.php
  - src/ServiceOrders/ServiceOrderRepository.php
  - src/ServiceOrders/ServiceOrderValidator.php
  - src/Controllers/ServiceOrderController.php
  - src/Http/Router.php
  - templates/service-orders/show.php
  - tests/ServiceOrders/ServiceOrderRepositoryTest.php
  - tests/Feature/ServiceOrderFlowTest.php
key_decisions:
  - D007 — Service-order closure uses a single transaction with conditional order claim and `SalesRepository::completeSaleInCurrentTransaction()` to prevent duplicate sales/stock movement under concurrent close requests.
  - Closed or sale-linked service orders are terminal for manual status updates at repository level and hidden in the UI.
  - Service-order text/status inputs now enforce length limits aligned with persistence constraints.
duration: 
verification_result: mixed
completed_at: 2026-06-06T13:28:51.706Z
blocker_discovered: false
---

# T03: Added atomic service-order closure into sales with terminal-state protections.

**Added atomic service-order closure into sales with terminal-state protections.**

## What Happened

Implemented and hardened close-into-sale behavior for service orders. The final version avoids split transactions by letting ServiceOrderRepository conditionally claim the open order, call SalesRepository through a transaction-aware method, link the sale, close the order and append status history before commit. The repository blocks duplicate close attempts, blocks manual status changes after closure or cancellation, and validates field lengths. Tests cover sale/payment creation, order-sale linkage, stock decrement only at closure, service items without stock movement, insufficient payment rollback, duplicate close prevention and closed-order manual reopen prevention.

## Verification

`php -l` passed on all T03-touched PHP files/templates/tests. `composer test` passed after hardening: 57 tests, 338 assertions. `composer audit --no-interaction` reported no advisories. Browser UAT after hardening confirmed sale creation, stock movement, closed order linkage and no terminal forms on closed orders.

## Verification Evidence

| # | Command | Exit Code | Verdict | Duration |
|---|---------|-----------|---------|----------|
| 1 | `php -l src/Sales/SalesRepository.php && php -l src/ServiceOrders/ServiceOrderRepository.php && php -l src/ServiceOrders/ServiceOrderValidator.php && php -l src/Controllers/ServiceOrderController.php && php -l templates/service-orders/show.php && php -l tests/ServiceOrders/ServiceOrderRepositoryTest.php && php -l tests/Feature/ServiceOrderFlowTest.php — pass, no syntax errors detected.` | -1 | unknown (coerced from string) | 0ms |
| 2 | `composer test — pass, PHPUnit OK (57 tests, 338 assertions).` | -1 | unknown (coerced from string) | 0ms |
| 3 | `composer audit --no-interaction — pass, no security vulnerability advisories found.` | -1 | unknown (coerced from string) | 0ms |
| 4 | `browser UAT rerun — pass, created order #3, closed into sale #3, sale diagnostics showed stock movement, order detail showed Fechada/Venda #3 and DOM reported {statusForms:0, closeForms:0}.` | -1 | unknown (coerced from string) | 0ms |

## Deviations

Security review found an integrity issue after the initial T03 completion; the task was hardened before slice completion.

## Known Issues

None.

## Files Created/Modified

- `src/Sales/SalesRepository.php`
- `src/ServiceOrders/ServiceOrderRepository.php`
- `src/ServiceOrders/ServiceOrderValidator.php`
- `src/Controllers/ServiceOrderController.php`
- `src/Http/Router.php`
- `templates/service-orders/show.php`
- `tests/ServiceOrders/ServiceOrderRepositoryTest.php`
- `tests/Feature/ServiceOrderFlowTest.php`
