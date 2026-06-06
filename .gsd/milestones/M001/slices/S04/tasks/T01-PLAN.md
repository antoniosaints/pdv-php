---
estimated_steps: 1
estimated_files: 5
skills_used: []
---

# T01: Adicionar previews protegidos de recibo e etiqueta

Add a PrintController and protected routes for receipt and label previews. Receipt route must load sale, items and payments from SalesRepository; label route must load product and variant data from CatalogRepository. Missing sale/product/variant should return contextual 404 pages. Add feature tests for authentication, receipt preview content and label preview content.

## Inputs

- `src/Sales/SalesRepository.php`
- `src/Catalog/CatalogRepository.php`
- `templates/sales/show.php`
- `templates/catalog/show.php`

## Expected Output

- `src/Controllers/PrintController.php`
- `templates/print/receipt.php`
- `templates/print/label.php`
- `tests/Feature/PrintPreviewTest.php`

## Verification

composer test

## Observability Impact

Preview pages expose print target metadata and clear 404 messages for invalid sale/product/variant references.
