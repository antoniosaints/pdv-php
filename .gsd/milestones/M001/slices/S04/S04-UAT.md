# S04: Impressao recibos e etiquetas — UAT

**Milestone:** M001
**Written:** 2026-06-05T23:58:36.415Z

# S04 UAT

## Scenario: receipt and label previews expose print diagnostics

1. Run full test and install verification.
2. Start the PHP server at `http://127.0.0.1:8080`.
3. Log in as an admin user.
4. Open `/pos?barcode=7891000000010`, finalize the seeded product sale, and land on `/sales/{id}`.
5. Click `Imprimir recibo` and confirm the receipt preview shows:
   - `Preview do recibo`
   - `Recibo gerencial não fiscal`
   - `Camiseta Demo`
   - QZ/fallback print controls and visible `QZ indisponível` diagnostics when QZ Tray is not installed.
6. Open catalog search for `DEMO-CAMISETA`, open the product detail, click `Imprimir etiqueta`, and confirm the label preview shows:
   - `Preview da etiqueta`
   - `CAMISETA DEMO`
   - barcode `7891000000010`
   - QZ/fallback print controls and visible `QZ indisponível` diagnostics.
7. Confirm browser diagnostics show no console errors and no failed network requests after clearing earlier invalid-login noise.

## Evidence captured

- PHPUnit: `OK (32 tests, 163 assertions)`.
- Install verifier: 3 migrations executed, 0 pending, installation verified.
- Browser UAT passed for receipt and label previews with QZ unavailable diagnostics, no console errors and no failed requests.
