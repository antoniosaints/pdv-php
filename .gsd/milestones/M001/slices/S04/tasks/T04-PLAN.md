---
estimated_steps: 1
estimated_files: 1
skills_used: []
---

# T04: Executar UAT de impressao e diagnosticos

Run final verification for S04: full PHPUnit, install verifier, and browser UAT through the real PHP entrypoint. UAT must log in, open an existing sale receipt preview, confirm receipt data and print status controls, open a catalog label preview and confirm barcode/price/status controls with no console errors or failed requests.

## Inputs

- `database/seeders/catalog_seed.php`
- `src/Controllers/PrintController.php`
- `public/assets/print.js`

## Expected Output

- `.gsd/milestones/M001/slices/S04/S04-UAT.md`

## Verification

composer test

## Observability Impact

Browser UAT proves the print diagnostic surfaces load without JavaScript/runtime failures even when QZ Tray is not installed.
