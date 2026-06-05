# S02: Catalogo produtos variantes e servicos

**Goal:** Deliver the catalog foundation for products, variants, services and barcode lookup with validation and stock-ready data.
**Demo:** An authenticated admin can create products, variants, services, barcodes, costs, prices, stock minimums and label data, then search them responsively.

## Must-Haves

- Product and service CRUD works from protected pages.
- Variants and barcode fields are persisted and searchable.
- Costs, prices and stock minimums are captured.
- Invalid duplicate or missing barcode data is rejected clearly.
- Seed/test data can create products used by downstream PDV tests.

## Proof Level

- This slice proves: integration

## Integration Closure

Consumes S01 routing, auth and DB. Produces catalog tables and lookup services consumed by PDV, labels, stock and reports.

## Verification

- Adds validation errors, audit entries for catalog changes and searchable product identifiers for diagnostics.

## Tasks

- [x] **T01: Criar schema e repositorio de catalogo** `est:2h`
  Add catalog migrations and repository layer for products, variants and barcodes. Include product type (`product` or `service`), SKU, name, cost, price, stock tracking, minimum stock, active flag, variant attributes and unique barcodes. Add tests proving migration idempotency, product/service creation, variant creation and duplicate barcode rejection.
  - Files: `database/migrations/002_create_catalog_tables.php`, `src/Catalog/CatalogRepository.php`, `src/Catalog/CatalogValidator.php`, `tests/Catalog/CatalogRepositoryTest.php`
  - Verify: composer test

- [x] **T02: Implementar CRUD protegido de produtos e servicos** `est:2h`
  Implement protected catalog listing, creation, editing, activation/deactivation and detail routes. Add controller methods, templates and navigation links for admin users. Forms must persist type, SKU, name, description, cost, price, stock tracking, stock minimum and active state.
  - Files: `src/Controllers/CatalogController.php`, `templates/catalog/index.php`, `templates/catalog/create.php`, `templates/catalog/edit.php`, `templates/catalog/show.php`, `templates/catalog/_form.php`, `src/Http/Router.php`, `templates/layout.php`, `public/assets/app.css`, `tests/Feature/CatalogCrudTest.php`
  - Verify: composer test

- [x] **T03: Adicionar variantes codigos de barras e busca** `est:2h`
  Add variant and barcode management inside the product edit/detail flow. Users must be able to add or update variants with name, SKU, barcode, price override, cost override, initial stock and active status. Duplicate barcodes must fail with actionable validation. Add lookup methods by barcode and search term for the PDV.
  - Files: `src/Catalog/CatalogRepository.php`, `src/Catalog/CatalogValidator.php`, `src/Controllers/CatalogController.php`, `templates/catalog/show.php`, `templates/catalog/_variant_form.php`, `tests/Feature/CatalogVariantTest.php`, `tests/Catalog/CatalogLookupTest.php`
  - Verify: composer test

- [x] **T04: Adicionar seed dados de catalogo e UAT** `est:1h30m`
  Add catalog seed command/data, dashboard catalog link, responsive polish and final browser UAT for catalog flows. Seed must create at least one stock-tracked product with variant/barcode and one service item for downstream PDV tests.
  - Files: `bin/console`, `database/seeders/catalog_seed.php`, `templates/dashboard/index.php`, `README.md`, `tests/Feature/CatalogSeedTest.php`, `public/assets/app.css`
  - Verify: composer test

## Files Likely Touched

- database/migrations/002_create_catalog_tables.php
- src/Catalog/CatalogRepository.php
- src/Catalog/CatalogValidator.php
- tests/Catalog/CatalogRepositoryTest.php
- src/Controllers/CatalogController.php
- templates/catalog/index.php
- templates/catalog/create.php
- templates/catalog/edit.php
- templates/catalog/show.php
- templates/catalog/_form.php
- src/Http/Router.php
- templates/layout.php
- public/assets/app.css
- tests/Feature/CatalogCrudTest.php
- templates/catalog/_variant_form.php
- tests/Feature/CatalogVariantTest.php
- tests/Catalog/CatalogLookupTest.php
- bin/console
- database/seeders/catalog_seed.php
- templates/dashboard/index.php
- README.md
- tests/Feature/CatalogSeedTest.php
