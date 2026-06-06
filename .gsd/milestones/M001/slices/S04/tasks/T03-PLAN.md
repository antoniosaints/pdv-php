---
estimated_steps: 1
estimated_files: 5
skills_used: []
---

# T03: Ligar acoes de impressao no fluxo operacional

Wire receipt links from completed sales and label links from catalog variant rows. Add print-specific responsive CSS for receipt, label and diagnostic panels. Update README with QZ Tray expectations and browser fallback. Ensure visual layout remains readable on desktop and mobile.

## Inputs

- `templates/sales/show.php`
- `templates/catalog/show.php`
- `public/assets/app.css`

## Expected Output

- `public/assets/app.css`
- `README.md`

## Verification

composer test

## Observability Impact

Users can reach diagnostic print previews from the operational sale and catalog pages instead of guessing routes.
