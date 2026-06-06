---
estimated_steps: 1
estimated_files: 5
skills_used: []
---

# T02: Implementar adaptador QZ Tray com fallback nativo

Add a browser-side QZ Tray adapter JavaScript and print diagnostics component. The adapter must detect whether `window.qz` exists, show status transitions, expose last error without secrets, and fall back to `window.print()` when QZ is unavailable. Wire buttons on receipt and label preview pages. Add tests/assertions that print pages include QZ diagnostics and fallback controls.

## Inputs

- `templates/print/receipt.php`
- `templates/print/label.php`

## Expected Output

- `public/assets/print.js`
- `templates/print/receipt.php`
- `templates/print/label.php`

## Verification

composer test

## Observability Impact

Adds visible print status, QZ availability, last error and fallback path on every print preview page.
