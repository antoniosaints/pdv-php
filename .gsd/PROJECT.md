# Project

## What This Is

Sistema web responsivo de PDV e gestão de loja, construído como aplicação PHP monolítica com Composer. O produto deve controlar produtos, variantes, etiquetas, estoque, reposição, caixa/PDV, vendas avulsas, serviços, ordens de serviço, relatórios, dashboard e impressão local de recibos/etiquetas via QZ Tray.

## Core Value

A loja precisa conseguir cadastrar produtos, vender no PDV com baixa automática de estoque e consultar o desempenho sem depender de uma instalação complexa ou backend separado.

## Project Shape

- **Complexity:** complex
- **Why:** O sistema combina PDV, estoque, impressão local, relatórios financeiros, instalação simples, segurança de sessão e caminho de migração de banco.

## Current State

Repositório inicial sem código de aplicação. Foram registradas decisões de arquitetura e requisitos de produto para orientar o primeiro marco.

## Architecture / Key Patterns

- PHP monolítico com Composer, server-rendered HTML e JavaScript apenas onde necessário.
- SQLite por padrão, usando PDO, migrations versionadas e SQL conservador para manter opção futura de MySQL.
- Sem API separada no MVP; rotas web e handlers PHP compõem a aplicação.
- Impressão local via QZ Tray para recibos gerenciais e etiquetas.
- Leitor de código de barras tratado como entrada de teclado no navegador.
- Recibo gerencial no MVP; emissão fiscal NFC-e/SAT está fora do escopo inicial.

## Capability Contract

See `.gsd/REQUIREMENTS.md` for the explicit capability contract, requirement status, and coverage mapping.

## Milestone Sequence

- [ ] M001: MVP operacional de PDV e estoque — instalar, logar, cadastrar produtos/variantes, vender no PDV, baixar estoque, imprimir recibo/etiqueta e consultar relatórios iniciais.
- [ ] M002: Operação avançada de loja — aprofundar ordens de serviço, etiquetas, reposição, permissões, auditoria e rotinas de backup.
- [ ] M003: Inteligência gerencial e escala — previsões melhores, dicas baseadas em histórico, migração MySQL assistida e preparação multi-loja se necessário.
