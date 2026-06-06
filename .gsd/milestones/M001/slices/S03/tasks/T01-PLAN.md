---
estimated_steps: 1
estimated_files: 3
skills_used: []
---

# T01: Criar schema de vendas pagamentos e movimentos de estoque

Add a portable migration for sales, sale_items, sale_payments and stock_movements. Tables must store money in integer cents, sale status, timestamps, variant/product snapshots, payment method/amount and stock movement type/reference. Add repository-level tests proving migration idempotency and that schema supports both sale facts and stock ledger facts.

## Inputs

- `database/migrations/001_create_core_tables.php`
- `database/migrations/002_create_catalog_tables.php`

## Expected Output

- `database/migrations/003_create_sales_tables.php`
- `tests/Sales/SaleRepositoryTest.php`

## Verification

composer test

## Observability Impact

Creates auditable stock_movements ledger and sale status fields for downstream diagnostics.
