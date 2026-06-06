---
estimated_steps: 1
estimated_files: 6
skills_used: []
---

# T02: Adicionar tela protegida de estoque e reposicao

Add StockController and protected routes for `/stock`. The page must show low-stock alerts, all tracked variants, movement history and forms for replenishment and adjustment. Forms must be CSRF-protected, field-level validation must render clearly, and successful operations redirect back to stock. Feature tests must cover auth guard, low-stock page content, replenishment form success and invalid adjustment message.

## Inputs

- `src/Stock/StockRepository.php`
- `src/Security/Csrf.php`
- `templates/layout.php`

## Expected Output

- `src/Controllers/StockController.php`
- `templates/stock/index.php`
- `tests/Feature/StockFlowTest.php`

## Verification

composer test

## Observability Impact

Stock screen shows current stock, minimum stock, recent movement history and validation/failure messages.
