---
estimated_steps: 1
estimated_files: 5
skills_used: []
---

# T04: Adicionar seed UAT e verificacao final do PDV

Extend demo seed or tests so a downstream UAT has a stock-tracked product barcode and a service item. Update README/dashboard links as needed and run full verification through PHPUnit, install verifier and browser UAT on real PHP entrypoint: login, open PDV, add seeded barcode, finalize payment and confirm stock decreased or sale detail is visible.

## Inputs

- `database/seeders/catalog_seed.php`
- `src/Controllers/SalesController.php`
- `templates/sales/pos.php`

## Expected Output

- `tests/Feature/SalesFlowTest.php`

## Verification

composer test

## Observability Impact

Final UAT confirms browser-visible sale completion and no console/network failures.
