---
estimated_steps: 1
estimated_files: 2
skills_used: []
---

# T01: Criar agregador de relatorios gerenciais

Add a ReportsRepository that aggregates real sales, sale items, payments, stock and service orders. It must compute sales total, gross profit, average ticket, payment breakdown, product/service item totals, monthly performance, simple current-month projection, low-stock count/list and open service-order count/list. Repository tests must cover populated data, empty data, service-vs-product profit, low stock and open order counts.

## Inputs

- `src/Sales/SalesRepository.php`
- `src/Stock/StockRepository.php`
- `src/ServiceOrders/ServiceOrderRepository.php`
- `database/migrations/003_create_sales_tables.php`
- `database/migrations/004_create_service_orders_tables.php`

## Expected Output

- `src/Reports/ReportsRepository.php`
- `tests/Reports/ReportsRepositoryTest.php`

## Verification

composer test

## Observability Impact

Aggregate methods expose deterministic dashboard/report facts that future agents can inspect through tests and protected UI.
