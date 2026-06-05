---
estimated_steps: 1
estimated_files: 7
skills_used: []
---

# T03: Adicionar variantes codigos de barras e busca

Add variant and barcode management inside the product edit/detail flow. Users must be able to add or update variants with name, SKU, barcode, price override, cost override, initial stock and active status. Duplicate barcodes must fail with actionable validation. Add lookup methods by barcode and search term for the PDV.

## Inputs

- `src/Controllers/CatalogController.php`
- `templates/catalog/show.php`
- `tests/Feature/CatalogCrudTest.php`

## Expected Output

- `src/Catalog/CatalogRepository.php`
- `src/Catalog/CatalogValidator.php`
- `src/Controllers/CatalogController.php`
- `templates/catalog/show.php`
- `templates/catalog/_variant_form.php`
- `tests/Feature/CatalogVariantTest.php`
- `tests/Catalog/CatalogLookupTest.php`

## Verification

composer test

## Observability Impact

Barcode lookup paths return clear not-found and duplicate validation signals for later PDV diagnostics.
