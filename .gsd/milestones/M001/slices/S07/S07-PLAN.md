# S07: Relatorios dashboard e dicas

**Goal:** Entregar dashboard e relatórios MVP com totais de vendas, lucro bruto, desempenho/projeção mensal, estoque baixo, ordens abertas e dicas simples baseadas nos dados reais já persistidos.
**Demo:** The dashboard shows sales total, gross profit, low-stock alerts, open orders, monthly projection and simple improvement tips based on real stored data.

## Must-Haves

- Dashboard protegido mostra total de vendas, lucro bruto, ticket médio, ordens abertas, itens com estoque baixo, projeção mensal e dicas simples.
- Relatórios protegidos mostram resumo de vendas por período, pagamentos, produtos/serviços vendidos e desempenho mensal em dados reais.
- Cálculos usam dados persistidos de vendas, itens, pagamentos, estoque e ordens de serviço, com centavos inteiros e sem SQL não-portável.
- Estados vazios são legíveis e não quebram o dashboard.
- Testes provam agregações, projeção mensal, dicas e consumo de ordens/estoque.
- Browser UAT valida dashboard e relatório após dados seed/vendas/ordens.

## Proof Level

- This slice proves: integration

## Integration Closure

Consumes S03 sales/sale_items/payments, S05 stock status/movements and S06 service_orders/sale linkage. Produces dashboard/report surfaces for S08 final UAT and validates R009 at MVP depth.

## Verification

- Adds protected dashboard/report diagnostic surfaces showing computed totals, open order counts, low-stock signals, monthly projection and deterministic tips; repository tests pin aggregate formulas and empty-state behavior.

## Tasks

- [ ] **T01: Criar agregador de relatorios gerenciais** `est:3h`
  Add a ReportsRepository that aggregates real sales, sale items, payments, stock and service orders. It must compute sales total, gross profit, average ticket, payment breakdown, product/service item totals, monthly performance, simple current-month projection, low-stock count/list and open service-order count/list. Repository tests must cover populated data, empty data, service-vs-product profit, low stock and open order counts.
  - Files: `src/Reports/ReportsRepository.php`, `tests/Reports/ReportsRepositoryTest.php`
  - Verify: composer test

- [ ] **T02: Transformar dashboard em painel gerencial real** `est:2h30m`
  Wire ReportsRepository into Router/public bootstrap and DashboardController. Replace placeholder dashboard cards with real metrics and deterministic tips based on sales, profit, low stock and open orders. Feature tests must prove authenticated dashboard renders real numbers, empty state and tip text.
  - Files: `src/Controllers/DashboardController.php`, `src/Http/Router.php`, `public/index.php`, `templates/dashboard/index.php`, `public/assets/app.css`, `tests/Feature/DashboardReportTest.php`
  - Verify: composer test

- [ ] **T03: Adicionar pagina protegida de relatorios** `est:2h30m`
  Add a protected `/reports` page and route for sales/profit/payment/monthly details. The page must show totals, payment breakdown, top sold items, monthly projection and open-order/low-stock callouts. Feature tests must cover auth guard, populated report content and empty-state rendering.
  - Files: `src/Controllers/ReportsController.php`, `src/Http/Router.php`, `templates/reports/index.php`, `templates/layout.php`, `templates/dashboard/index.php`, `public/assets/app.css`, `tests/Feature/DashboardReportTest.php`
  - Verify: composer test

- [ ] **T04: Documentar e validar UAT de relatorios** `est:1h30m`
  Update README with dashboard/report usage and run final S07 verification: PHP lint, PHPUnit, install verifier and browser UAT. UAT must log in, verify dashboard metrics/tips, open reports, confirm sales/profit/projection/low-stock/open-order data and no console/network failures.
  - Files: `README.md`, `tests/Feature/DashboardReportTest.php`
  - Verify: composer test

## Files Likely Touched

- src/Reports/ReportsRepository.php
- tests/Reports/ReportsRepositoryTest.php
- src/Controllers/DashboardController.php
- src/Http/Router.php
- public/index.php
- templates/dashboard/index.php
- public/assets/app.css
- tests/Feature/DashboardReportTest.php
- src/Controllers/ReportsController.php
- templates/reports/index.php
- templates/layout.php
- README.md
