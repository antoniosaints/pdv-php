---
id: S06
parent: M001
milestone: M001
provides:
  - Portable service-order schema: service_orders, service_order_items and service_order_status_history.
  - ServiceOrderRepository and validator for creation, listing, status history, status updates and atomic close-into-sale.
  - Protected `/service-orders` list/create/detail/status/close routes for admin/caixa users.
  - Navigation/dashboard/README entry points for service-order operation.
  - Service-order facts and status surfaces for S07 reporting and S08 final UAT.
requires:
  - slice: S02
    provides: Catalog products/services/variants and sale lookup data consumed for service order items.
  - slice: S03
    provides: SalesRepository completion, payments and stock movement decrement consumed for close-into-sale.
affects:
  - S07 can consume `service_orders` and status history for open-order counts, dashboard cards, monthly service revenue and operational tips.
  - S08 can include service-order creation, status movement, close-into-sale and stock decrement in final UAT.
key_files:
  - database/migrations/004_create_service_orders_tables.php
  - src/ServiceOrders/ServiceOrderRepository.php
  - src/ServiceOrders/ServiceOrderValidator.php
  - src/ServiceOrders/ValidationException.php
  - src/Sales/SalesRepository.php
  - src/Controllers/ServiceOrderController.php
  - src/Http/Router.php
  - public/index.php
  - templates/service-orders/index.php
  - templates/service-orders/create.php
  - templates/service-orders/show.php
  - public/assets/app.css
  - templates/layout.php
  - templates/dashboard/index.php
  - README.md
  - tests/ServiceOrders/ServiceOrderRepositoryTest.php
  - tests/Database/MigratorTest.php
  - tests/Feature/ServiceOrderFlowTest.php
key_decisions:
  - D007 — Service-order closure uses one transaction with conditional order claim and transaction-aware SalesRepository persistence to avoid duplicate sales/stock movements.
  - Manual status updates cannot change closed, sale-linked or cancelled service orders.
  - Services and products are snapshotted into service_order_items using the same item facts needed by sales, preserving history if catalog records change.
patterns_established:
  - Use item snapshots for operational history rather than relying on mutable catalog names/prices.
  - Compose cross-domain writes through one transaction when order, sale and stock integrity must move together.
  - Terminal business states must be enforced in repositories, not only hidden in templates.
observability_surfaces:
  - Protected service order detail page shows status, totals, customer data, items, sale link and status history.
  - service_order_status_history records actor, from/to status, notes and timestamp for every transition.
  - Sale detail diagnostics show payment and stock movement caused by service-order closure.
  - Validation failures for CSRF, invalid order input, insufficient payment, duplicate close and terminal status mutation render on the relevant service-order page.
drill_down_paths:
  - .gsd/milestones/M001/slices/S06/tasks/T01-SUMMARY.md
  - .gsd/milestones/M001/slices/S06/tasks/T02-SUMMARY.md
  - .gsd/milestones/M001/slices/S06/tasks/T03-SUMMARY.md
  - .gsd/milestones/M001/slices/S06/tasks/T04-SUMMARY.md
duration: ""
verification_result: passed
completed_at: 2026-06-06T13:40:04.523Z
blocker_discovered: false
---

# S06: Servicos e ordens de servico

**S06 shipped protected service orders with status history and atomic close into sale/payment/stock flow.**

## What Happened

S06 delivered an end-to-end service-order workflow. The app now has portable service order tables, domain validation, protected routes, list/create/detail pages, status history and closure into the existing sale/payment/stock flow. Operators can create an order for a customer with service and product items, advance status with notes, inspect totals and history, then close the order into a completed sale. Product stock is decremented only when the order is closed into sale; service items create sale facts without stock movement. After security review, close-into-sale was hardened into a single transaction with conditional order claim, duplicate close protection, terminal status enforcement and length limits. Navigation and README were updated, and final UAT verified the real browser path after hardening.

## Verification

Final verification passed after post-review hardening: `composer test` OK with 57 tests and 338 assertions; `php bin/verify-install.php` OK with 4 migrations and 0 pending; `composer audit --no-interaction` reported no advisories; browser UAT passed login, create order, status update, close sale, sale diagnostics, linked closed order and no terminal forms with no console/network failures.

## Requirements Advanced

- R008 — Implemented full customer service order flow with customer fields, service/product items, values, statuses, status history and sale linkage.

## Requirements Validated

- R008 — Validated by S06 repository tests, feature tests, install verifier and browser UAT: created an order with seeded service and product, advanced status, closed into sale, linked sale_id and showed product stock decrement 12 → 11 while service item had no stock movement.

## New Requirements Surfaced

None.

## Requirements Invalidated or Re-scoped

None.

## Operational Readiness

None.

## Deviations

Security review found that the initial close-into-sale approach committed the sale before linking the order. The final implementation was hardened to close order and sale inside one transaction using a conditional order claim.

## Known Limitations

MVP service orders do not yet include customer master records, staff assignment, scheduling, attachments, service-specific procurement or a JavaScript cart builder. The server-rendered create form provides a small fixed set of optional item rows and the backend accepts nested item arrays; richer order editing can evolve later.

## Follow-ups

S07 should use service order status and sale-linked service facts for open-order counts, service revenue and dashboard tips. Later milestones can add customer records, assignees, appointments, attachments and a richer multi-item cart UI if needed.

## Files Created/Modified

- `database/migrations/004_create_service_orders_tables.php` — Added service order, item snapshot and status history tables for SQLite/MySQL.
- `src/ServiceOrders/ServiceOrderRepository.php` — Added service-order creation, listing, status history, atomic close-into-sale and terminal-state protections.
- `src/ServiceOrders/ServiceOrderValidator.php` — Added service-order normalization, money parsing, status validation and length limits.
- `src/ServiceOrders/ValidationException.php` — Added domain validation exception.
- `src/Sales/SalesRepository.php` — Added transaction-aware sale completion for service-order composition while preserving the existing PDV sale API.
- `src/Controllers/ServiceOrderController.php` — Added protected service-order controller actions for index, create, store, detail, status update and close-sale.
- `src/Http/Router.php` — Wired service-order routes and dependency injection.
- `public/index.php` — Wired service-order repository in the public bootstrap.
- `templates/service-orders/index.php` — Added service-order list, create and detail pages with diagnostics and terminal closed-state UI.
- `templates/service-orders/create.php` — Added service-order list, create and detail pages with diagnostics and terminal closed-state UI.
- `templates/service-orders/show.php` — Added service-order list, create and detail pages with diagnostics and terminal closed-state UI.
- `public/assets/app.css` — Added styles for service-order item rows and detail grids.
- `templates/layout.php` — Added service-order navigation and dashboard CTA.
- `templates/dashboard/index.php` — Added service-order navigation and dashboard CTA.
- `README.md` — Documented service-order operation, statuses and close-into-sale behavior.
- `tests/ServiceOrders/ServiceOrderRepositoryTest.php` — Added repository tests for schema, creation, validation, status history, atomic close and terminal protections.
- `tests/Database/MigratorTest.php` — Updated migration expectations for service-order tables.
- `tests/Feature/ServiceOrderFlowTest.php` — Added feature tests for auth, navigation, creation, status update, close-sale, duplicate close prevention and validation failures.
