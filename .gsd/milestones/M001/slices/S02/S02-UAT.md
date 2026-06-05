# S02: Catalogo produtos variantes e servicos — UAT

**Milestone:** M001
**Written:** 2026-06-05T19:59:25.947Z

# S02 UAT — Catálogo de produtos, variantes e serviços

## Preconditions

- S01 foundation complete.
- User logged in as admin.
- Database migrated through `002_create_catalog_tables.php`.

## Scenario 1 — Product CRUD

1. Open `/catalog/create`.
2. Create a product with SKU, name, description, cost, price, stock minimum and label name.
3. Confirm redirect to `/catalog/{id}`.
4. Confirm detail page shows price, cost, controlled stock, stock minimum and SKU.

Expected: product persists and is visible from `/catalog`.

## Scenario 2 — Variant and barcode

1. Open a product detail page.
2. Add a variant with SKU, barcode, price override and initial stock.
3. Confirm the variant appears in the variants table.
4. Open `/catalog/lookup/barcode?barcode=<barcode>`.

Expected: JSON returns `found:true`, variant data and effective price/cost fields.

## Scenario 3 — Demo seed

1. Run `php bin/console seed:catalog`.
2. Open `/catalog`.
3. Confirm `Camiseta Demo`, `Ajuste de Barra Demo` and SKU `DEMO-CAMISETA` appear.
4. Open `/catalog/lookup/barcode?barcode=7891000000010`.

Expected: lookup returns `DEMO-CAMISETA-PRETA-M` and `effective_price_cents:6490`.

## Evidence

- `composer test && php bin/verify-install.php` passed with PHPUnit `OK (18 tests, 81 assertions)` and install checks OK.
- Browser assertions passed for `/catalog` seeded list and `/catalog/lookup/barcode?barcode=7891000000010`, including no console errors and no failed requests.

