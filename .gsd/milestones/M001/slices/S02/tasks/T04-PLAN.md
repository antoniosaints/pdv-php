---
estimated_steps: 1
estimated_files: 6
skills_used: []
---

# T04: Adicionar seed dados de catalogo e UAT

Add catalog seed command/data, dashboard catalog link, responsive polish and final browser UAT for catalog flows. Seed must create at least one stock-tracked product with variant/barcode and one service item for downstream PDV tests.

## Inputs

- `src/Catalog/CatalogRepository.php`
- `src/Controllers/CatalogController.php`
- `templates/dashboard/index.php`

## Expected Output

- `bin/console`
- `database/seeders/catalog_seed.php`
- `templates/dashboard/index.php`
- `README.md`
- `tests/Feature/CatalogSeedTest.php`

## Verification

composer test

## Observability Impact

Seed command output and catalog page empty/filled states make setup state visible to future agents.
