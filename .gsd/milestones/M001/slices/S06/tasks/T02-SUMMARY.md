---
id: T02
parent: S06
milestone: M001
key_files:
  - src/Controllers/ServiceOrderController.php
  - src/Http/Router.php
  - public/index.php
  - templates/service-orders/index.php
  - templates/service-orders/create.php
  - templates/service-orders/show.php
  - public/assets/app.css
  - tests/Feature/ServiceOrderFlowTest.php
  - src/ServiceOrders/ServiceOrderValidator.php
key_decisions:
  - Service-order routes are protected for `admin` and `caixa`, matching the operational service/payment workflow.
  - The create page renders optional blank item rows and the validator ignores only fully blank rows, enabling multi-item orders without JavaScript while preserving validation for malformed rows.
  - Avoided using `$status` as a PHP template loop variable because required templates share scope with `View::render()` and can overwrite the renderer status parameter.
duration: 
verification_result: mixed
completed_at: 2026-06-06T11:57:39.752Z
blocker_discovered: false
---

# T02: Added protected service-order routes, screens and feature tests.

**Added protected service-order routes, screens and feature tests.**

## What Happened

Implemented protected service-order web flow. Added ServiceOrderController with index, create, store, show and status update actions. Wired ServiceOrderRepository into the Router and public bootstrap. Added index/create/show templates with catalog search, multi-line order item entry, customer fields, order totals, item snapshots and status history. Added CSS for service-order item rows and detail layout. Added feature tests for auth guard, create page catalog visibility, order creation with service and product items, status update history and invalid create validation. Fixed a template-scope bug where a loop variable named `$status` overwrote the renderer HTTP status parameter.

## Verification

`php -l` passed for the new controller, Router, bootstrap, service-order templates, updated validator and feature test. `composer test` passed after the template fix: 52 tests, 288 assertions.

## Verification Evidence

| # | Command | Exit Code | Verdict | Duration |
|---|---------|-----------|---------|----------|
| 1 | `php -l templates/service-orders/show.php && php -l src/Controllers/ServiceOrderController.php — pass, no syntax errors detected after the template variable fix.` | -1 | unknown (coerced from string) | 0ms |
| 2 | `composer test — pass, PHPUnit OK (52 tests, 288 assertions).` | -1 | unknown (coerced from string) | 0ms |

## Deviations

None.

## Known Issues

None.

## Files Created/Modified

- `src/Controllers/ServiceOrderController.php`
- `src/Http/Router.php`
- `public/index.php`
- `templates/service-orders/index.php`
- `templates/service-orders/create.php`
- `templates/service-orders/show.php`
- `public/assets/app.css`
- `tests/Feature/ServiceOrderFlowTest.php`
- `src/ServiceOrders/ServiceOrderValidator.php`
