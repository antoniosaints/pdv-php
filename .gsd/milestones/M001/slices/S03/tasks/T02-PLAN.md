---
estimated_steps: 1
estimated_files: 4
skills_used: []
---

# T02: Implementar repositorio e validacao transacional de vendas

Create SalesRepository and SalesValidator. The repository must accept a sale draft with items, discounts and payments, load active variants through catalog data, validate quantities/prices/payments, reject insufficient tracked stock, and commit sale rows, payment rows, stock movement rows and variant stock decrement inside one PDO transaction. Add tests for successful product sale decrement, service sale without stock movement, insufficient stock rollback and payment mismatch validation.

## Inputs

- `src/Catalog/CatalogRepository.php`
- `database/migrations/003_create_sales_tables.php`

## Expected Output

- `src/Sales/SalesRepository.php`
- `src/Sales/SalesValidator.php`
- `src/Sales/ValidationException.php`
- `tests/Sales/SaleRepositoryTest.php`

## Verification

composer test

## Observability Impact

Validation exceptions expose field-level failure reasons; persisted sale/payment/movement rows make completed transactions inspectable.
