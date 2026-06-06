# S05: Reposicao e controle de estoque

**Goal:** Entregar controle operacional de reposição com alertas de estoque baixo, registro de entradas/ajustes e histórico de movimentos usando o ledger de estoque existente.
**Demo:** The stock screen shows low-stock products, lets the user record replenishment entries or adjustments, and updates balances and movement history.

## Must-Haves

- Usuário admin/estoque consegue abrir tela de estoque protegida.
- Tela lista variantes com estoque atual, estoque mínimo e alerta de baixo estoque.
- Usuário consegue registrar entrada de compra/reposição com quantidade positiva.
- Usuário consegue registrar ajuste manual para mais ou para menos, com motivo obrigatório.
- Cada operação atualiza `product_variants.current_stock` e grava `stock_movements` com antes/depois, tipo, motivo e referência.
- Validações impedem quantidade inválida, ajuste que deixaria estoque negativo e variante inexistente.
- Histórico de movimentos mostra vendas, reposições e ajustes recentes.

## Proof Level

- This slice proves: integration

## Integration Closure

Consumes S02 catalog/variant stock data and S03 stock_movements ledger. Produces replenishment and adjustment movements plus stock status UI for reports and final UAT.

## Verification

- Adds stock movement history, before/after quantities, movement reason, validation errors and low-stock status so agents/operators can inspect inventory state changes.

## Tasks

- [x] **T01: Implementar repositorio de reposicao e ajustes de estoque** `est:3h`
  Create StockRepository and StockValidator. Repository must list low-stock variants, list recent stock movements joined to product/variant names, and transactionally record replenishment or adjustment movements updating current stock and writing before/after rows into stock_movements. Tests must prove low-stock detection, positive replenishment, positive/negative adjustment and rollback/validation for negative resulting stock.
  - Files: `src/Stock/StockRepository.php`, `src/Stock/StockValidator.php`, `src/Stock/ValidationException.php`, `tests/Stock/StockRepositoryTest.php`
  - Verify: composer test

- [x] **T02: Adicionar tela protegida de estoque e reposicao** `est:3h`
  Add StockController and protected routes for `/stock`. The page must show low-stock alerts, all tracked variants, movement history and forms for replenishment and adjustment. Forms must be CSRF-protected, field-level validation must render clearly, and successful operations redirect back to stock. Feature tests must cover auth guard, low-stock page content, replenishment form success and invalid adjustment message.
  - Files: `src/Controllers/StockController.php`, `src/Http/Router.php`, `public/index.php`, `templates/stock/index.php`, `public/assets/app.css`, `tests/Feature/StockFlowTest.php`
  - Verify: composer test

- [x] **T03: Ligar estoque ao fluxo operacional e documentacao** `est:1h30m`
  Wire stock navigation into layout/dashboard and update docs. Dashboard must link to stock controls. README must explain stock screen, movement types and that sales/replenishment/adjustment share the same movement ledger. Tests should assert operational link visibility.
  - Files: `templates/layout.php`, `templates/dashboard/index.php`, `README.md`, `tests/Feature/StockFlowTest.php`
  - Verify: composer test

- [x] **T04: Executar UAT de reposicao e historico de estoque** `est:1h30m`
  Run final S05 verification: full PHPUnit, install verifier and browser UAT. UAT must log in, open stock screen, confirm low-stock/demo stock information, perform a replenishment or adjustment, and verify movement history and changed current stock with no console errors or failed requests.
  - Files: `tests/Feature/StockFlowTest.php`
  - Verify: composer test

## Files Likely Touched

- src/Stock/StockRepository.php
- src/Stock/StockValidator.php
- src/Stock/ValidationException.php
- tests/Stock/StockRepositoryTest.php
- src/Controllers/StockController.php
- src/Http/Router.php
- public/index.php
- templates/stock/index.php
- public/assets/app.css
- tests/Feature/StockFlowTest.php
- templates/layout.php
- templates/dashboard/index.php
- README.md
