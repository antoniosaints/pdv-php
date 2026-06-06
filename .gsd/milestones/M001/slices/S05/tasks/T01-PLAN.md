---
estimated_steps: 1
estimated_files: 4
skills_used: []
---

# T01: Implementar repositorio de reposicao e ajustes de estoque

Create StockRepository and StockValidator. Repository must list low-stock variants, list recent stock movements joined to product/variant names, and transactionally record replenishment or adjustment movements updating current stock and writing before/after rows into stock_movements. Tests must prove low-stock detection, positive replenishment, positive/negative adjustment and rollback/validation for negative resulting stock.

## Inputs

- `database/migrations/003_create_sales_tables.php`
- `src/Catalog/CatalogRepository.php`

## Expected Output

- `src/Stock/StockRepository.php`
- `src/Stock/StockValidator.php`
- `src/Stock/ValidationException.php`
- `tests/Stock/StockRepositoryTest.php`

## Verification

composer test

## Observability Impact

Stock movements include type, delta, before/after quantity, reason and timestamp for operational diagnostics.
