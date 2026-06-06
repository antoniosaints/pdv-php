# S03: PDV vendas e baixa automatica

**Goal:** Entregar o fluxo de PDV para venda avulsa com busca por produto ou código de barras, carrinho, quantidade/desconto, forma de pagamento, finalização transacional e baixa automática de estoque.
**Demo:** A cashier can scan or type a barcode, add items to cart, apply quantity/discount, choose payment, finalize a sale and see stock decrease automatically.

## Must-Haves

- Caixa autenticado consegue abrir o PDV e localizar itens por nome ou código de barras.
- Produtos controlados por estoque não podem vender quantidade maior que o saldo atual.
- Finalização cria venda, itens, pagamento e movimentos de estoque em uma única transação.
- Estoque atual da variante diminui automaticamente após venda concluída.
- Serviços podem entrar na venda sem movimento físico de estoque.
- Falhas de validação retornam mensagens claras e não alteram estoque parcialmente.

## Proof Level

- This slice proves: integration

## Integration Closure

Consumes S02 catalog lookup contracts and product variant stock balances. Produces sales, sale_items, sale_payments and stock_movements records consumed by receipts, replenishment and reports.

## Verification

- Adds sale status, stock movement ledger rows, validation messages for insufficient stock and receipt/sale detail pages for inspecting completed sale state.

## Tasks

- [x] **T01: Criar schema de vendas pagamentos e movimentos de estoque** `est:2h`
  Add a portable migration for sales, sale_items, sale_payments and stock_movements. Tables must store money in integer cents, sale status, timestamps, variant/product snapshots, payment method/amount and stock movement type/reference. Add repository-level tests proving migration idempotency and that schema supports both sale facts and stock ledger facts.
  - Files: `database/migrations/003_create_sales_tables.php`, `tests/Database/MigratorTest.php`, `tests/Sales/SaleRepositoryTest.php`
  - Verify: composer test

- [x] **T02: Implementar repositorio e validacao transacional de vendas** `est:3h`
  Create SalesRepository and SalesValidator. The repository must accept a sale draft with items, discounts and payments, load active variants through catalog data, validate quantities/prices/payments, reject insufficient tracked stock, and commit sale rows, payment rows, stock movement rows and variant stock decrement inside one PDO transaction. Add tests for successful product sale decrement, service sale without stock movement, insufficient stock rollback and payment mismatch validation.
  - Files: `src/Sales/SalesRepository.php`, `src/Sales/SalesValidator.php`, `src/Sales/ValidationException.php`, `tests/Sales/SaleRepositoryTest.php`
  - Verify: composer test

- [x] **T03: Adicionar rotas e interface responsiva do PDV** `est:3h`
  Wire SalesRepository into Router and add SalesController with protected caixa/admin routes for /pos, barcode JSON add, search-backed item selection, cart form state, discount/quantity fields, payment method and finalize action. Add templates and navigation. The UI may be server-rendered with lightweight JavaScript for barcode/search interactions, but finalization must post a normal CSRF-protected form. Feature tests must cover auth guard, GET PDV, successful finalize redirect and validation message for insufficient stock.
  - Files: `src/Http/Router.php`, `src/Controllers/SalesController.php`, `templates/sales/pos.php`, `templates/sales/show.php`, `templates/layout.php`, `public/assets/app.css`, `tests/Feature/SalesFlowTest.php`
  - Verify: composer test

- [x] **T04: Adicionar seed UAT e verificacao final do PDV** `est:2h`
  Extend demo seed or tests so a downstream UAT has a stock-tracked product barcode and a service item. Update README/dashboard links as needed and run full verification through PHPUnit, install verifier and browser UAT on real PHP entrypoint: login, open PDV, add seeded barcode, finalize payment and confirm stock decreased or sale detail is visible.
  - Files: `database/seeders/catalog_seed.php`, `bin/console`, `templates/dashboard/index.php`, `README.md`, `tests/Feature/CatalogSeedTest.php`
  - Verify: composer test

## Files Likely Touched

- database/migrations/003_create_sales_tables.php
- tests/Database/MigratorTest.php
- tests/Sales/SaleRepositoryTest.php
- src/Sales/SalesRepository.php
- src/Sales/SalesValidator.php
- src/Sales/ValidationException.php
- src/Http/Router.php
- src/Controllers/SalesController.php
- templates/sales/pos.php
- templates/sales/show.php
- templates/layout.php
- public/assets/app.css
- tests/Feature/SalesFlowTest.php
- database/seeders/catalog_seed.php
- bin/console
- templates/dashboard/index.php
- README.md
- tests/Feature/CatalogSeedTest.php
