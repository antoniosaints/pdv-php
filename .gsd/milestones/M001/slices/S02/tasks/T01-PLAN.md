---
estimated_steps: 1
estimated_files: 4
skills_used: []
---

# T01: Criar schema e repositorio de catalogo

Add catalog migrations and repository layer for products, variants and barcodes. Include product type (`product` or `service`), SKU, name, cost, price, stock tracking, minimum stock, active flag, variant attributes and unique barcodes. Add tests proving migration idempotency, product/service creation, variant creation and duplicate barcode rejection.

## Inputs

- `src/Database/Migrator.php`
- `database/migrations/001_create_core_tables.php`
- `phpunit.xml`

## Expected Output

- `database/migrations/002_create_catalog_tables.php`
- `src/Catalog/CatalogRepository.php`
- `src/Catalog/CatalogValidator.php`
- `tests/Catalog/CatalogRepositoryTest.php`

## Verification

composer test

## Observability Impact

Repository writes audit-ready timestamps and returns validation errors suitable for UI diagnostics.
