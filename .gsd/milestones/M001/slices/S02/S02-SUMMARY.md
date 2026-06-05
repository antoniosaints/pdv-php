---
id: S02
parent: M001
milestone: M001
provides:
  - Catalog tables and repository for products, services and variants.
  - Protected catalog CRUD UI.
  - Barcode and search lookup methods/endpoints for PDV.
  - Demo product/service seed data for downstream testing.
  - Validation patterns for field-level user feedback.
requires:
  []
affects:
  []
key_files:
  - database/migrations/002_create_catalog_tables.php
  - src/Catalog/CatalogRepository.php
  - src/Catalog/CatalogValidator.php
  - src/Catalog/ValidationException.php
  - src/Controllers/CatalogController.php
  - src/Http/Router.php
  - templates/catalog/index.php
  - templates/catalog/create.php
  - templates/catalog/edit.php
  - templates/catalog/show.php
  - templates/catalog/_form.php
  - templates/catalog/_variant_form.php
  - database/seeders/catalog_seed.php
  - bin/console
  - tests/Catalog/CatalogRepositoryTest.php
  - tests/Catalog/CatalogLookupTest.php
  - tests/Feature/CatalogCrudTest.php
  - tests/Feature/CatalogVariantTest.php
  - tests/Feature/CatalogSeedTest.php
key_decisions:
  - Money values are stored as integer cents for cost/price.
  - Services are catalog products with type `service` and do not track physical stock.
  - Catalog lookup endpoints remain authenticated JSON routes inside the PHP monolith, not a separate API.
  - Demo catalog data is optional via `php bin/console seed:catalog`.
patterns_established:
  - Products and services share catalog tables, distinguished by `type`.
  - Variants are the barcode/sale lookup unit consumed by the PDV.
  - Integer cents are used for all monetary catalog values.
  - Authenticated JSON endpoints can support interactive web flows without creating a separate API service.
observability_surfaces:
  - Inline validation messages for product and variant forms.
  - HTTP 404 JSON for missing barcode lookup.
  - Catalog list empty/filled states.
  - Seed command output naming created demo items and barcodes.
  - Migration status now includes catalog migration.
drill_down_paths:
  - .gsd/milestones/M001/slices/S02/tasks/T01-SUMMARY.md
  - .gsd/milestones/M001/slices/S02/tasks/T02-SUMMARY.md
  - .gsd/milestones/M001/slices/S02/tasks/T03-SUMMARY.md
  - .gsd/milestones/M001/slices/S02/tasks/T04-SUMMARY.md
duration: ""
verification_result: passed
completed_at: 2026-06-05T19:59:25.945Z
blocker_discovered: false
---

# S02: Catalogo produtos variantes e servicos

**S02 delivered the protected catalog with products, services, variants, barcodes, lookup endpoints and demo seed data.**

## What Happened

S02 delivered the protected catalog foundation. The system now supports products and services, costs/prices in cents, stock tracking flags, stock minimums, label names, variants, SKUs, barcodes, current stock, activation/deactivation, catalog listing/search, detail pages, variant management, authenticated JSON barcode lookup and sale search endpoints, and optional demo seed data. The implementation is wired through the existing S01 auth/router/view/DB foundation and covered by repository and feature tests. Browser UAT verified the real PHP routes for catalog listing and barcode lookup using seeded data.

## Verification

Fresh final verification after last code change: `composer test && php bin/verify-install.php` passed with PHPUnit `OK (18 tests, 81 assertions)`, 2 migrations executed and 0 pending. Browser assertions passed for catalog list and barcode JSON lookup, including seeded product/service visibility, effective price, no console errors and no failed requests.

## Requirements Advanced

- R003 — Implemented product/service CRUD, costs, prices, SKUs, label names and active status.
- R004 — Variants now carry current stock and minimum-stock product data exists for later movement/replenishment logic.
- R005 — Barcode lookup endpoint and variant model provide the product lookup foundation for PDV scanner input.
- R006 — Label name and barcode fields are present for label generation in S04.
- R008 — Service items exist in the catalog and can be looked up by barcode for later PDV/service sale flows.

## Requirements Validated

- R003 — S02 tests and browser UAT verify products, services, variants, barcodes, costs, prices, stock minimums, label names and duplicate barcode validation.

## New Requirements Surfaced

None.

## Requirements Invalidated or Re-scoped

None.

## Operational Readiness

None.

## Deviations

None.

## Known Limitations

Stock is currently represented as `product_variants.current_stock` for catalog readiness. S03/S05 must introduce auditable stock movements and update balances transactionally during sales and replenishment. Fiscal issuance remains out of scope.

## Follow-ups

Proceed to S03 PDV sale loop using `CatalogRepository::findByBarcode()` and `searchForSale()` as the stable product lookup surface. Introduce sale, sale_items, payments and stock movement tables with transaction boundaries.

## Files Created/Modified

- `database/migrations/002_create_catalog_tables.php` — Catalog schema for products/services and variants with SQLite/MySQL branches.
- `src/Catalog/CatalogRepository.php, src/Catalog/CatalogValidator.php, src/Catalog/ValidationException.php` — Catalog repository, validation and validation exception.
- `src/Http/Router.php, src/Controllers/CatalogController.php` — Protected catalog routes, controller and routing parameters.
- `templates/catalog/*.php` — Catalog list/create/edit/detail and variant forms.
- `templates/layout.php, templates/dashboard/index.php, public/assets/app.css` — Navigation, dashboard CTA and responsive catalog/variant styling.
- `bin/console, database/seeders/catalog_seed.php` — Catalog seed command and demo seeder.
- `tests/Catalog/*.php, tests/Feature/Catalog*.php` — Tests for repository, lookup, CRUD, variants and seed.
