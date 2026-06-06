---
id: T01
parent: S04
milestone: M001
key_files:
  - src/Controllers/PrintController.php
  - src/Http/Router.php
  - templates/print/receipt.php
  - templates/print/label.php
  - tests/Feature/PrintPreviewTest.php
key_decisions: []
duration: 
verification_result: passed
completed_at: 2026-06-05T23:45:51.984Z
blocker_discovered: false
---

# T01: Added protected print previews for sale receipts and catalog labels.

**Added protected print previews for sale receipts and catalog labels.**

## What Happened

Added PrintController with protected receipt and label preview routes. Receipt previews consume completed sale, item and payment data from SalesRepository. Label previews consume product and variant data from CatalogRepository and verify that the variant belongs to the product. Added receipt and label templates with clear gerencial non-fiscal messaging, QZ/native print actions and status panels. Added feature tests for auth guard, receipt content and label content.

## Verification

Fresh verification after the last code change passed: `PATH="/c/php:$PATH" /c/php/php.exe vendor/bin/phpunit` returned PHPUnit OK with 31 tests and 154 assertions. Syntax checks passed for PrintController, Router, print templates and PrintPreviewTest.

## Verification Evidence

| # | Command | Exit Code | Verdict | Duration |
|---|---------|-----------|---------|----------|
| 1 | `PATH="/c/php:$PATH" /c/php/php.exe vendor/bin/phpunit` | 0 | ✅ pass | 7000ms |
| 2 | `PATH="/c/php:$PATH" /c/php/php.exe -l src/Controllers/PrintController.php && /c/php/php.exe -l src/Http/Router.php && /c/php/php.exe -l templates/print/receipt.php && /c/php/php.exe -l templates/print/label.php && /c/php/php.exe -l tests/Feature/PrintPreviewTest.php` | 0 | ✅ pass | 8400ms |

## Deviations

The T01 templates include a static print diagnostic panel and buttons that will be wired to the QZ adapter in T02.

## Known Issues

None.

## Files Created/Modified

- `src/Controllers/PrintController.php`
- `src/Http/Router.php`
- `templates/print/receipt.php`
- `templates/print/label.php`
- `tests/Feature/PrintPreviewTest.php`
