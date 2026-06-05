---
id: T01
parent: S02
milestone: M001
key_files:
  - database/migrations/002_create_catalog_tables.php
  - src/Catalog/ValidationException.php
  - src/Catalog/CatalogValidator.php
  - src/Catalog/CatalogRepository.php
  - tests/Catalog/CatalogRepositoryTest.php
  - tests/Database/MigratorTest.php
key_decisions:
  - Valores monetários de custo/preço são persistidos como centavos inteiros para evitar erro de ponto flutuante em vendas e lucro.
  - Serviços são normalizados com `track_stock = false`, mesmo que o formulário envie rastreamento de estoque.
duration: 
verification_result: mixed
completed_at: 2026-06-05T18:22:17.353Z
blocker_discovered: false
---

# T01: Criado schema, validação e repositório de catálogo com produtos, serviços, variantes e barcode.

**Criado schema, validação e repositório de catálogo com produtos, serviços, variantes e barcode.**

## What Happened

Foi criado o schema de catálogo para produtos/serviços e variantes, com campos de tipo, SKU, nome, descrição, custo/preço em centavos, controle de estoque, estoque mínimo, nome de etiqueta, ativo, variantes, código de barras único, estoque atual e timestamps. O repositório implementa criação/edição/listagem, variantes, busca por barcode e busca para venda. A validação rejeita nomes inválidos, valores negativos, SKUs duplicados e barcodes duplicados com mensagens por campo. O teste de migrations foi atualizado para duas migrations e os novos testes cobrem produto com estoque, serviço sem estoque, duplicidade de barcode e busca por nome/SKU/barcode.

## Verification

Executados lint dos arquivos de catálogo, `composer test`, `php bin/console migrate` e `php bin/console migrate:status`. PHPUnit passou com 9 testes e 39 assertions; banco local aplicou `002_create_catalog_tables.php` e ficou com 0 migrations pendentes.

## Verification Evidence

| # | Command | Exit Code | Verdict | Duration |
|---|---------|-----------|---------|----------|
| 1 | `php -l ... && composer test — exit 0, pass: OK (9 tests, 39 assertions)` | -1 | unknown (coerced from string) | 0ms |
| 2 | `php bin/console migrate && php bin/console migrate:status — exit 0, pass: 2 migrations executadas, 0 pendentes` | -1 | unknown (coerced from string) | 0ms |

## Deviations

Adicionado `src/Catalog/ValidationException.php` para transportar erros por campo até UI/testes. O teste de migrations existente foi atualizado para a nova migration de catálogo.

## Known Issues

Nenhum bloqueador. Estoque inicial está temporariamente em `product_variants.current_stock`; a S03/S05 introduzirá ledger completo de movimentações.

## Files Created/Modified

- `database/migrations/002_create_catalog_tables.php`
- `src/Catalog/ValidationException.php`
- `src/Catalog/CatalogValidator.php`
- `src/Catalog/CatalogRepository.php`
- `tests/Catalog/CatalogRepositoryTest.php`
- `tests/Database/MigratorTest.php`
