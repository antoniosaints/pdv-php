# S05: Reposicao e controle de estoque — UAT

**Milestone:** M001
**Written:** 2026-06-06T00:26:16.955Z

# S05 UAT

## Scenario: stock replenishment updates balance and movement history

1. Run PHPUnit and install verification.
2. Prepare a local admin user and a low-stock tracked variant for UAT.
3. Start the PHP server at `http://127.0.0.1:8080`.
4. Log in as admin.
5. Open `/stock` and confirm:
   - `Reposição e ajustes`
   - low-stock product `Produto UAT Estoque Baixo`
   - stock controls/forms are visible
   - movement history section exists
6. Select `Produto UAT Estoque Baixo · Unica` in the replenishment form.
7. Submit quantity `4` with reason `Compra UAT S05`.
8. Confirm the page returns to `/stock` and movement history shows:
   - `Compra UAT S05`
   - movement type `purchase`
9. Confirm browser diagnostics show no console errors and no failed network requests.

## Evidence captured

- PHPUnit: `OK (41 tests, 207 assertions)`.
- Install verifier: 3 migrations executed, 0 pending, installation verified.
- Browser UAT passed for stock page, low-stock visibility, replenishment submission and movement history with no console errors or failed requests.
