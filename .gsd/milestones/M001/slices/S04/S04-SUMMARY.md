---
id: S04
parent: M001
milestone: M001
provides:
  - Receipt preview route `/sales/{id}/receipt`
  - Label preview route `/catalog/{id}/variants/{variantId}/label`
  - QZ Tray adapter at `/assets/print.js`
  - Visible print diagnostics and native print fallback
  - Print-ready receipt and label CSS
requires:
  - slice: S03
    provides: Completed sale records, sale items and payments for receipt previews.
  - slice: S02
    provides: Product, variant, label name, price, SKU and barcode data for label previews.
affects:
  - S08 can now verify receipt and label print previews plus print diagnostics in final end-to-end UAT
key_files:
  - src/Controllers/PrintController.php
  - src/Http/Router.php
  - templates/print/receipt.php
  - templates/print/label.php
  - public/assets/print.js
  - templates/sales/show.php
  - templates/catalog/show.php
  - public/assets/app.css
  - tests/Feature/PrintPreviewTest.php
  - README.md
key_decisions:
  - Print previews are protected server-rendered pages rather than separate API responses.
  - QZ Tray integration is progressive: use QZ when `window.qz` exists, otherwise fall back to native browser print.
  - Receipt explicitly states it is gerencial non-fiscal to preserve the fiscal anti-feature boundary.
patterns_established:
  - Printing is implemented as protected preview pages plus progressive browser-side adapter.
  - Operational pages link to previews rather than hiding print routes behind guessed URLs.
  - QZ errors are surfaced in-page for agent/operator diagnosis.
observability_surfaces:
  - Print status panel on every receipt/label preview.
  - QZ availability state displayed as QZ detected, QZ unavailable, connecting, connected, sent, native print or error.
  - Last QZ/native print error detail rendered in the page without secrets.
  - Receipt and label preview pages provide deterministic print target ids for debugging.
drill_down_paths:
  - .gsd/milestones/M001/slices/S04/tasks/T01-SUMMARY.md
  - .gsd/milestones/M001/slices/S04/tasks/T02-SUMMARY.md
  - .gsd/milestones/M001/slices/S04/tasks/T03-SUMMARY.md
  - .gsd/milestones/M001/slices/S04/tasks/T04-SUMMARY.md
duration: ""
verification_result: passed
completed_at: 2026-06-05T23:58:36.413Z
blocker_discovered: false
---

# S04: Impressao recibos e etiquetas

**S04 delivered protected receipt and label previews with QZ Tray diagnostics and native print fallback.**

## What Happened

S04 delivered receipt and label print previews. Completed sales now link to a receipt preview with sale code, item lines, totals, payments and a non-fiscal disclaimer. Catalog variant rows now link to label previews with label name, variant, price, barcode and SKU. The frontend includes a QZ Tray adapter that reports status, attempts QZ printing when available and falls back to native browser printing when unavailable. Feature tests cover protected routes, preview content, operational links and diagnostics. Browser UAT confirmed receipt and label previews load through the real PHP app with QZ unavailable diagnostics and no console/network errors.

## Verification

Fresh verification passed: PHPUnit OK with 32 tests and 163 assertions; install verifier passed with 3 migrations and 0 pending; browser UAT validated receipt preview and label preview with QZ unavailable diagnostics and no console/network failures.

## Requirements Advanced

- R006 — Added receipt and label preview routes, browser-side QZ adapter and fallback diagnostics.

## Requirements Validated

- R006 — Validated by S04 feature tests and browser UAT confirming receipt preview, label preview, print diagnostics, QZ unavailable state and native fallback controls without console/network errors.

## New Requirements Surfaced

None.

## Requirements Invalidated or Re-scoped

None.

## Operational Readiness

None.

## Deviations

None.

## Known Limitations

Actual QZ Tray hardware/service printing was not verified because QZ Tray is not installed in the test browser. The adapter and fallback diagnostic path were verified in browser; real printer validation remains an environment-specific follow-up.

## Follow-ups

Future hardware validation can exercise a real QZ Tray service and configured thermal printer. S08 should include receipt/label preview checks in the full end-to-end UAT. Fiscal issuance remains out of scope.

## Files Created/Modified

- `src/Controllers/PrintController.php` — Added protected receipt and label print preview controller.
- `src/Http/Router.php` — Wired receipt and label print routes.
- `templates/print/receipt.php` — Added receipt print preview template with non-fiscal notice and diagnostics.
- `templates/print/label.php` — Added label print preview template with barcode/price/SKU and diagnostics.
- `public/assets/print.js` — Added browser-side QZ Tray adapter with status panel and native print fallback.
- `templates/layout.php` — Loaded print adapter through the shared layout.
- `templates/sales/show.php` — Added receipt action to completed sale page.
- `templates/catalog/show.php` — Added label action to catalog variant rows.
- `public/assets/app.css` — Added print preview, receipt, label and print-media styling.
- `README.md` — Documented print preview routes, QZ Tray behavior and native fallback.
- `tests/Feature/PrintPreviewTest.php` — Added feature tests for print previews and operational links.
