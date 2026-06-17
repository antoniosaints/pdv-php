# S06: Servicos e ordens de servico

**Goal:** Entregar fluxo MVP de ordens de serviço: cadastrar cliente e itens de serviço/produto, acompanhar status e fechar a ordem em venda/pagamento reaproveitando o PDV e a baixa de estoque existente.
**Demo:** A user can create a customer service order, add services/products, move it through statuses and optionally close it into a sale/payment flow.

## Must-Haves

- Usuário autenticado com papel admin/caixa consegue listar, criar e abrir ordens de serviço.
- Ordem registra cliente, telefone/documento opcionais, descrição/notas, itens de serviço/produto, quantidades, descontos e totais em centavos.
- Status pode avançar por estados operacionais com histórico visível.
- Fechar uma ordem em venda cria um registro em `sales`, grava pagamentos, vincula `service_orders.sale_id` e baixa estoque apenas de produtos rastreados.
- Validações retornam mensagens de campo para cliente obrigatório, item inválido, quantidade inválida, pagamento insuficiente e status inválido.
- A tela expõe diagnóstico operacional suficiente: status atual, histórico de status, vínculo da venda e mensagens de validação sem segredos.

## Proof Level

- This slice proves: integration

## Integration Closure

Consumes S02 catalog search/service-product variants and S03 sale completion/payment/stock decrement. Produces service order facts for S07 dashboard/open orders and S08 final UAT.

## Verification

- Adds protected order detail as inspection surface for order status, totals, status history, sale linkage and validation failures; database status history records actor, transition, notes and timestamp.

## Tasks

- [x] **T01: Criar schema e dominio de ordens de servico** `est:3h`
  Add the service-order database migration plus repository, validator and exception. The repository must create orders from normalized customer/item input, snapshot catalog item data, calculate totals, list/find orders and record status history. Tests must prove migration creates tables, order creation snapshots service/product items, totals are calculated correctly, validation rejects malformed input and status transitions are recorded.
  - Files: `database/migrations/004_create_service_orders_tables.php`, `src/ServiceOrders/ServiceOrderRepository.php`, `src/ServiceOrders/ServiceOrderValidator.php`, `src/ServiceOrders/ValidationException.php`, `tests/ServiceOrders/ServiceOrderRepositoryTest.php`, `tests/Database/MigratorTest.php`
  - Verify: composer test

- [x] **T02: Adicionar telas e rotas protegidas de ordens** `est:3h`
  Wire ServiceOrderController through Router and public bootstrap. Add protected routes for list, create, store, detail and status update. Templates must render list/detail/create forms, item rows, totals, validation messages and status history. Feature tests must cover auth guard, successful order creation with seeded service/product, status update and invalid form messages.
  - Files: `src/Controllers/ServiceOrderController.php`, `src/Http/Router.php`, `public/index.php`, `templates/service-orders/index.php`, `templates/service-orders/create.php`, `templates/service-orders/show.php`, `public/assets/app.css`, `tests/Feature/ServiceOrderFlowTest.php`
  - Verify: composer test

- [x] **T03: Fechar ordem de servico em venda** `est:3h`
  Implement close-into-sale behavior without nested transactions. The repository/controller must build sale input from service-order items and posted payment data, call the existing SalesRepository sale completion path, link the sale to the order, mark status closed, set closed_at and redirect to the sale detail. Tests must prove sale/payment creation, order-sale linkage, product stock decrement only at closure, service items do not create stock movement and payment validation failures keep the order open.
  - Files: `src/ServiceOrders/ServiceOrderRepository.php`, `src/Controllers/ServiceOrderController.php`, `templates/service-orders/show.php`, `tests/ServiceOrders/ServiceOrderRepositoryTest.php`, `tests/Feature/ServiceOrderFlowTest.php`
  - Verify: composer test

- [x] **T04: Integrar navegacao documentacao e UAT de ordens** `est:1h30m`
  Add service-order links to layout/dashboard and document the service-order flow in README. Run final S06 verification through PHPUnit, install verifier and browser UAT: login, open service orders, create an order with demo service and product, advance status, close with payment, confirm sale detail and stock decrement with no console/network failures.
  - Files: `templates/layout.php`, `templates/dashboard/index.php`, `README.md`, `tests/Feature/ServiceOrderFlowTest.php`
  - Verify: composer test

## Files Likely Touched

- database/migrations/004_create_service_orders_tables.php
- src/ServiceOrders/ServiceOrderRepository.php
- src/ServiceOrders/ServiceOrderValidator.php
- src/ServiceOrders/ValidationException.php
- tests/ServiceOrders/ServiceOrderRepositoryTest.php
- tests/Database/MigratorTest.php
- src/Controllers/ServiceOrderController.php
- src/Http/Router.php
- public/index.php
- templates/service-orders/index.php
- templates/service-orders/create.php
- templates/service-orders/show.php
- public/assets/app.css
- tests/Feature/ServiceOrderFlowTest.php
- templates/layout.php
- templates/dashboard/index.php
- README.md
