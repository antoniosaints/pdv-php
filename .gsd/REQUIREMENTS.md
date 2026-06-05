# Requirements

This file is the explicit capability and coverage contract for the project.

## Active

### R004 — O sistema deve registrar movimentações de estoque e baixar estoque automaticamente quando vendas de produtos forem concluídas.
- Class: core-capability
- Status: active
- Description: O sistema deve registrar movimentações de estoque e baixar estoque automaticamente quando vendas de produtos forem concluídas.
- Why it matters: Controle automático evita divergência manual e torna relatórios e reposição confiáveis.
- Source: user
- Primary owning slice: M001/S03
- Validation: Partially advanced by S02; automatic movement/decrement not yet validated.
- Notes: Catalog carries current stock and stock minimums; full stock movement ledger and automatic decrement remain mapped to S03/S05.

### R005 — O PDV deve permitir venda avulsa responsiva com busca por produto, leitor de código de barras, itens, quantidades, descontos, formas de pagamento e finalização.
- Class: primary-user-loop
- Status: active
- Description: O PDV deve permitir venda avulsa responsiva com busca por produto, leitor de código de barras, itens, quantidades, descontos, formas de pagamento e finalização.
- Why it matters: Este é o fluxo operacional mais crítico para uma loja.
- Source: user
- Primary owning slice: M001/S03
- Validation: mapped to M001/S03
- Notes: Leitor de código de barras será tratado como entrada de teclado no navegador, padrão dos leitores USB/Bluetooth.

### R006 — O sistema deve imprimir recibos do PDV e etiquetas de produtos diretamente pelo navegador usando QZ Tray.
- Class: integration
- Status: active
- Description: O sistema deve imprimir recibos do PDV e etiquetas de produtos diretamente pelo navegador usando QZ Tray.
- Why it matters: Impressão direta reduz fricção no caixa e habilita operação com impressoras térmicas e etiquetas.
- Source: user
- Primary owning slice: M001/S04
- Validation: mapped to M001/S04
- Notes: M001 prova integração de recibo; etiquetas podem começar como layout e impressão básica, com refinamento posterior.

### R007 — O sistema deve controlar reposição de estoque com estoque mínimo, alertas e registro de entradas de compra ou ajuste.
- Class: core-capability
- Status: active
- Description: O sistema deve controlar reposição de estoque com estoque mínimo, alertas e registro de entradas de compra ou ajuste.
- Why it matters: Reposição reduz ruptura de estoque e ajuda o lojista a agir antes de perder vendas.
- Source: user
- Primary owning slice: M001/S05
- Validation: mapped to M001/S05
- Notes: M001 entrega alerta e entrada manual; sugestões avançadas podem usar histórico em marco posterior.

### R008 — O sistema deve registrar venda de serviços e ordens de serviço com status, cliente, itens/serviços, valores e conclusão.
- Class: core-capability
- Status: active
- Description: O sistema deve registrar venda de serviços e ordens de serviço com status, cliente, itens/serviços, valores e conclusão.
- Why it matters: Algumas lojas vendem serviços além de produtos e precisam acompanhar execução antes da cobrança ou entrega.
- Source: user
- Primary owning slice: M001/S06
- Validation: mapped to M001/S06
- Notes: Serviços podem entrar no PDV no M001; ordens de serviço completas ficam para marco posterior se o MVP precisar ser reduzido.

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
| R004 | core-capability | active | M001/S03 | none | Partially advanced by S02; automatic movement/decrement not yet validated. |
| R005 | primary-user-loop | active | M001/S03 | none | mapped to M001/S03 |
| R006 | integration | active | M001/S04 | none | mapped to M001/S04 |
| R007 | core-capability | active | M001/S05 | none | mapped to M001/S05 |
| R008 | core-capability | active | M001/S06 | none | mapped to M001/S06 |
| R009 | core-capability | active | M001/S07 | none | mapped to M001/S07 |
| R010 | quality-attribute | validated | M001/S01 | none | Validated by S01: PDO connection and idempotent migrations on SQLite, with migration pattern containing SQLite/MySQL branches. |
| R011 | continuity | active | M001/S08 | none | mapped to M001/S08 |
| R012 | anti-feature | out-of-scope | none | none | n/a |

## Coverage Summary

- Active requirements: 7
- Mapped to slices: 7
- Validated: 4 (R001, R002, R003, R010)
- Unmapped active requirements: 0
