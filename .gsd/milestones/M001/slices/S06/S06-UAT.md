# S06: Servicos e ordens de servico — UAT

**Milestone:** M001
**Written:** 2026-06-06T13:40:04.524Z

# S06: Servicos e ordens de servico — UAT

**Milestone:** M001
**Written:** 2026-06-06

## UAT Type

- UAT mode: live-runtime
- Why this mode is sufficient: S06 is a protected, stateful web workflow that must prove routing, session auth, forms, database writes, sale integration and stock movement through the real PHP entrypoint.

## Preconditions

- Composer dependencies installed.
- Migrations executed with 4 migrations and 0 pending.
- Demo catalog seeded with `Ajuste de Barra Demo` and `Camiseta Demo`.
- Local PHP server running at `http://127.0.0.1:8080`.
- Authenticated admin/caixa user available.

## Smoke Test

Log in, open `/dashboard`, confirm the `Ordens de serviço` CTA is visible, then open `/service-orders/create` and confirm both demo items appear in the item selectors.

## Test Cases

### 1. Create and inspect a service order

1. Open `/service-orders/create`.
2. Fill customer name, phone and description.
3. Select `Ajuste de Barra Demo` in item row 1 and `Camiseta Demo` in item row 2.
4. Submit the order.
5. **Expected:** Browser lands on `/service-orders/{id}` showing the customer, both items, totals, status `Aberta`, and status history entry `Ordem criada`.

### 2. Advance status with history

1. On the order detail, change status to `Em execução` and submit a note.
2. **Expected:** The order detail still loads, status shows `Em execução`, and history includes the submitted note.

### 3. Close into sale and stock movement

1. On the order detail, submit the close-sale payment form.
2. **Expected:** Browser redirects to `/sales/{id}` showing `Venda concluída`, both order items, payment total and a stock movement row for the product.
3. Reopen `/service-orders/{id}`.
4. **Expected:** Order shows `Fechada`, `Venda #{id}`, close/status forms are absent, and history contains `Fechada pela venda #{id}`.

## Edge Cases

### Insufficient payment

1. Submit close-sale with payment below the order total.
2. **Expected:** Page returns validation message `Pagamento insuficiente para finalizar a venda.`, no sale is created and order remains open.

### Duplicate close or manual reopen

1. Submit close-sale again for a closed order or POST status `open` to the status route.
2. **Expected:** Request is rejected, no second sale is created, product stock is not decremented again and order remains closed.

## Failure Signals

- Missing `/service-orders` navigation for authenticated users.
- Order detail missing status history or sale link after close.
- `/sales/{id}` missing product stock movement after close.
- Closed order still rendering status or close-sale forms.
- Browser console errors or failed network requests during the flow.

## Requirements Proved By This UAT

- R008 — Proves customer service orders can be created with services/products, statuses, values and completion into sale/payment flow.

## Not Proven By This UAT

- Real customer master data, technician assignment, scheduling, attachments and advanced service lifecycle are outside S06 MVP depth.
- Hardware/payment-device integration is not involved; payment is recorded as application data only.

## Notes for Tester

The create page is intentionally server-rendered and uses a fixed set of optional item rows for MVP. Richer cart editing can be added later without changing the order/sale persistence contract.
