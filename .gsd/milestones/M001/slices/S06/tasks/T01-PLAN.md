---
estimated_steps: 1
estimated_files: 6
skills_used: []
---

# T01: Criar schema e dominio de ordens de servico

Add the service-order database migration plus repository, validator and exception. The repository must create orders from normalized customer/item input, snapshot catalog item data, calculate totals, list/find orders and record status history. Tests must prove migration creates tables, order creation snapshots service/product items, totals are calculated correctly, validation rejects malformed input and status transitions are recorded.

## Inputs

- `database/migrations/002_create_catalog_tables.php`
- `database/migrations/003_create_sales_tables.php`
- `src/Catalog/CatalogRepository.php`
- `src/Sales/SalesValidator.php`
- `tests/Catalog/CatalogRepositoryTest.php`
- `tests/Sales/SaleRepositoryTest.php`

## Expected Output

- `database/migrations/004_create_service_orders_tables.php`
- `src/ServiceOrders/ServiceOrderRepository.php`
- `src/ServiceOrders/ServiceOrderValidator.php`
- `src/ServiceOrders/ValidationException.php`
- `tests/ServiceOrders/ServiceOrderRepositoryTest.php`
- `tests/Database/MigratorTest.php`

## Verification

composer test

## Observability Impact

Adds durable service order status history rows with actor, from/to status, notes and timestamp for later inspection.
