# S03: PDV vendas e baixa automatica — UAT

**Milestone:** M001
**Written:** 2026-06-05T22:53:16.153Z

# S03 UAT

## Scenario: barcode sale decrements stock

1. Run migrations and seed demo catalog data.
2. Start the PHP server at `http://127.0.0.1:8080`.
3. Create or log in as an admin/caixa user.
4. Open `/pos?barcode=7891000000010`.
5. Confirm `Camiseta Demo` appears in the cart with payment amount `64,90`.
6. Click `Finalizar venda`.
7. Confirm the browser lands on `/sales/{id}` and shows:
   - `Venda concluída`
   - total/payment `R$ 64,90`
   - stock movement `12 → 11 (-1)`
8. Confirm browser diagnostics show no console errors and no failed network requests.

## Evidence captured

- PHPUnit: `OK (28 tests, 136 assertions)`.
- Install verifier: 3 migrations executed, 0 pending, installation verified.
- Browser assertions passed on `http://127.0.0.1:8080/sales/1` for `Venda concluída`, `R$ 64,90`, `12 → 11`, no console errors and no failed requests.
