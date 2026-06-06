---
estimated_steps: 1
estimated_files: 1
skills_used: []
---

# T04: Executar UAT de reposicao e historico de estoque

Run final S05 verification: full PHPUnit, install verifier and browser UAT. UAT must log in, open stock screen, confirm low-stock/demo stock information, perform a replenishment or adjustment, and verify movement history and changed current stock with no console errors or failed requests.

## Inputs

- `src/Controllers/StockController.php`
- `templates/stock/index.php`

## Expected Output

- `.gsd/milestones/M001/slices/S05/S05-UAT.md`

## Verification

composer test

## Observability Impact

Browser UAT proves stock status, movement history and validation surfaces work through the real app entrypoint.
