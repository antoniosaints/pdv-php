# Requirements

This file is the explicit capability and coverage contract for the project.

## Active

### R009 — O sistema deve oferecer relatórios de vendas, lucro, desempenho mensal, previsão do mês, dashboard de resumo e dicas de melhoria baseadas nos dados.
- Class: core-capability
- Status: active
- Description: O sistema deve oferecer relatórios de vendas, lucro, desempenho mensal, previsão do mês, dashboard de resumo e dicas de melhoria baseadas nos dados.
- Why it matters: O valor gerencial do sistema depende de transformar vendas e estoque em decisões práticas.
- Source: user
- Primary owning slice: M001/S07
- Validation: mapped to M001/S07
- Notes: M001 entrega dashboard e relatórios iniciais; previsões/dicas começam por regras simples e evoluem com histórico.

### R011 — O sistema deve ter backup/exportação do SQLite e logs/auditoria suficientes para recuperar ou diagnosticar operações críticas.
- Class: continuity
- Status: active
- Description: O sistema deve ter backup/exportação do SQLite e logs/auditoria suficientes para recuperar ou diagnosticar operações críticas.
- Why it matters: PDV e estoque são dados operacionais críticos; perda ou corrupção compromete a loja.
- Source: inferred from launchability and POS data risk
- Primary owning slice: M001/S08
- Validation: mapped to M001/S08
- Notes: M001 deve incluir pelo menos backup manual e logs de erro sem segredos; rotinas automáticas podem evoluir depois.

## Validated

### R001 — O sistema deve ser instalável em hospedagem PHP comum ou VPS simples com Composer, `.env` e SQLite por padrão.
- Class: launchability
- Status: validated
- Description: O sistema deve ser instalável em hospedagem PHP comum ou VPS simples com Composer, `.env` e SQLite por padrão.
- Why it matters: A facilidade de instalar e hospedar é parte central do valor do produto.
- Source: user
- Primary owning slice: M001/S01
- Validation: Validated by S01: Composer setup, SQLite migration, CLI install verifier and browser UAT through real PHP entrypoint passed.
- Notes: Installability foundation is validated for local/dev PHP hosting path; deployment-specific hardening continues in S08.

### R002 — O sistema deve proteger rotas administrativas e operacionais com autenticação, sessão segura e papéis básicos como administrador, caixa e estoque.
- Class: compliance/security
- Status: validated
- Description: O sistema deve proteger rotas administrativas e operacionais com autenticação, sessão segura e papéis básicos como administrador, caixa e estoque.
- Why it matters: PDV, relatórios e estoque expõem dados sensíveis e operações críticas.
- Source: inferred from business app scope
- Primary owning slice: M001/S01
- Validation: Validated by S01: first-admin setup, login/logout, CSRF, protected dashboard and auth guard tests passed.
- Notes: Basic admin protection validated; additional roles for caixa/estoque can be expanded in later slices.

### R003 — O sistema deve cadastrar produtos, serviços, variantes, preços, custos, códigos de barras, estoque mínimo e dados necessários para etiqueta.
- Class: core-capability
- Status: validated
- Description: O sistema deve cadastrar produtos, serviços, variantes, preços, custos, códigos de barras, estoque mínimo e dados necessários para etiqueta.
- Why it matters: Cadastro confiável é a base para PDV, estoque, etiquetas, reposição e relatórios.
- Source: user
- Primary owning slice: M001/S02
- Validation: Validated by S02: products, services, variants, barcodes, costs, prices, stock minimum and label data tested and browser-verified.
- Notes: Validated at catalog depth. Future slices consume this catalog for PDV, labels, stock and reports.

### R004 — O sistema deve registrar movimentações de estoque e baixar estoque automaticamente quando vendas de produtos forem concluídas.
- Class: core-capability
- Status: validated
- Description: O sistema deve registrar movimentações de estoque e baixar estoque automaticamente quando vendas de produtos forem concluídas.
- Why it matters: Controle automático evita divergência manual e torna relatórios e reposição confiáveis.
- Source: user
- Primary owning slice: M001/S03
- Validation: Validated by S03: transactional PDV sale finalization decrements product variant stock and writes stock_movements ledger rows; PHPUnit and browser UAT confirmed movement 12 → 11 after sale.
- Notes: Stock movement ledger now supports sale movements. Replenishment/adjustment movement types remain for S05.

### R005 — O PDV deve permitir venda avulsa responsiva com busca por produto, leitor de código de barras, itens, quantidades, descontos, formas de pagamento e finalização.
- Class: primary-user-loop
- Status: validated
- Description: O PDV deve permitir venda avulsa responsiva com busca por produto, leitor de código de barras, itens, quantidades, descontos, formas de pagamento e finalização.
- Why it matters: Este é o fluxo operacional mais crítico para uma loja.
- Source: user
- Primary owning slice: M001/S03
- Validation: Validated by S03: protected /pos flow supports barcode/search item selection, quantity, discount, payment method and finalization; browser UAT completed a seeded barcode sale and landed on the sale detail page.
- Notes: Backend accepts multiple item rows; current UI is server-rendered with GET-based item selection and can be progressively enhanced for scanner-driven multi-item cart behavior.

### R006 — O sistema deve imprimir recibos do PDV e etiquetas de produtos diretamente pelo navegador usando QZ Tray.
- Class: integration
- Status: validated
- Description: O sistema deve imprimir recibos do PDV e etiquetas de produtos diretamente pelo navegador usando QZ Tray.
- Why it matters: Impressão direta reduz fricção no caixa e habilita operação com impressoras térmicas e etiquetas.
- Source: user
- Primary owning slice: M001/S04
- Validation: Validated by S04: receipt and label preview routes, QZ Tray browser adapter, visible print diagnostics and native fallback were covered by feature tests and browser UAT with no console/network failures.
- Notes: Real QZ Tray hardware/service printing still requires environment-specific validation; MVP now provides the integration path, diagnostics and native browser fallback. Fiscal issuance remains out of scope.

### R007 — O sistema deve controlar reposição de estoque com estoque mínimo, alertas e registro de entradas de compra ou ajuste.
- Class: core-capability
- Status: validated
- Description: O sistema deve controlar reposição de estoque com estoque mínimo, alertas e registro de entradas de compra ou ajuste.
- Why it matters: Reposição reduz ruptura de estoque e ajuda o lojista a agir antes de perder vendas.
- Source: user
- Primary owning slice: M001/S05
- Validation: Validated by S05: protected stock screen shows low-stock alerts, records replenishment and manual adjustments, prevents negative stock and displays movement history; repository tests, feature tests and browser UAT passed.
- Notes: Validated at MVP depth: manual replenishment and adjustment are present. Supplier purchase orders, automatic reorder suggestions and advanced procurement can evolve in later milestones.

### R008 — O sistema deve registrar venda de serviços e ordens de serviço com status, cliente, itens/serviços, valores e conclusão.
- Class: core-capability
- Status: validated
- Description: O sistema deve registrar venda de serviços e ordens de serviço com status, cliente, itens/serviços, valores e conclusão.
- Why it matters: Algumas lojas vendem serviços além de produtos e precisam acompanhar execução antes da cobrança ou entrega.
- Source: user
- Primary owning slice: M001/S06
- Validation: Validated by S06: protected service-order flow creates customer orders with service/product items, status history, values and atomic close into sale/payment; browser UAT confirmed sale linkage and product stock decrement 12 → 11 while service items did not move stock.
- Notes: Validated at MVP depth by S06. Future milestones may add customer master records, staff assignment, scheduling, attachments and richer service lifecycle if needed.

### R010 — O sistema deve manter portabilidade entre SQLite e MySQL usando PDO, migrations versionadas e SQL conservador.
- Class: quality-attribute
- Status: validated
- Description: O sistema deve manter portabilidade entre SQLite e MySQL usando PDO, migrations versionadas e SQL conservador.
- Why it matters: A opção de migrar para MySQL precisa ser real, não apenas promessa de configuração.
- Source: user
- Primary owning slice: M001/S01
- Validation: Validated by S01: PDO connection and idempotent migrations on SQLite, with migration pattern containing SQLite/MySQL branches.
- Notes: Future domain migrations must preserve the same portability pattern.

## Deferred

## Out of Scope

### R012 — O MVP não deve emitir cupom fiscal, NFC-e, SAT, SPED ou integrar SEFAZ.
- Class: anti-feature
- Status: out-of-scope
- Description: O MVP não deve emitir cupom fiscal, NFC-e, SAT, SPED ou integrar SEFAZ.
- Why it matters: Evita confundir recibo gerencial com obrigação fiscal legal e mantém o MVP viável.
- Source: user confirmation
- Primary owning slice: none
- Validation: n/a
- Notes: Recibo gerencial não substitui documento fiscal quando exigido por lei. Futuro marco fiscal deve ser planejado separadamente.

## Traceability

| ID | Class | Status | Primary owner | Supporting | Proof |
|---|---|---|---|---|---|
| R001 | launchability | validated | M001/S01 | none | Validated by S01: Composer setup, SQLite migration, CLI install verifier and browser UAT through real PHP entrypoint passed. |
| R002 | compliance/security | validated | M001/S01 | none | Validated by S01: first-admin setup, login/logout, CSRF, protected dashboard and auth guard tests passed. |
| R003 | core-capability | validated | M001/S02 | none | Validated by S02: products, services, variants, barcodes, costs, prices, stock minimum and label data tested and browser-verified. |
| R004 | core-capability | validated | M001/S03 | none | Validated by S03: transactional PDV sale finalization decrements product variant stock and writes stock_movements ledger rows; PHPUnit and browser UAT confirmed movement 12 → 11 after sale. |
| R005 | primary-user-loop | validated | M001/S03 | none | Validated by S03: protected /pos flow supports barcode/search item selection, quantity, discount, payment method and finalization; browser UAT completed a seeded barcode sale and landed on the sale detail page. |
| R006 | integration | validated | M001/S04 | none | Validated by S04: receipt and label preview routes, QZ Tray browser adapter, visible print diagnostics and native fallback were covered by feature tests and browser UAT with no console/network failures. |
| R007 | core-capability | validated | M001/S05 | none | Validated by S05: protected stock screen shows low-stock alerts, records replenishment and manual adjustments, prevents negative stock and displays movement history; repository tests, feature tests and browser UAT passed. |
| R008 | core-capability | validated | M001/S06 | none | Validated by S06: protected service-order flow creates customer orders with service/product items, status history, values and atomic close into sale/payment; browser UAT confirmed sale linkage and product stock decrement 12 → 11 while service items did not move stock. |
| R009 | core-capability | active | M001/S07 | none | mapped to M001/S07 |
| R010 | quality-attribute | validated | M001/S01 | none | Validated by S01: PDO connection and idempotent migrations on SQLite, with migration pattern containing SQLite/MySQL branches. |
| R011 | continuity | active | M001/S08 | none | mapped to M001/S08 |
| R012 | anti-feature | out-of-scope | none | none | n/a |

## Coverage Summary

- Active requirements: 2
- Mapped to slices: 2
- Validated: 9 (R001, R002, R003, R004, R005, R006, R007, R008, R010)
- Unmapped active requirements: 0
