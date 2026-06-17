---
estimated_steps: 1
estimated_files: 8
skills_used: []
---

# T02: Adicionar telas e rotas protegidas de ordens

Wire ServiceOrderController through Router and public bootstrap. Add protected routes for list, create, store, detail and status update. Templates must render list/detail/create forms, item rows, totals, validation messages and status history. Feature tests must cover auth guard, successful order creation with seeded service/product, status update and invalid form messages.

## Inputs

- `src/ServiceOrders/ServiceOrderRepository.php`
- `src/ServiceOrders/ServiceOrderValidator.php`
- `src/Http/Router.php`
- `templates/sales/pos.php`
- `templates/stock/index.php`
- `tests/Feature/SalesFlowTest.php`

## Expected Output

- `src/Controllers/ServiceOrderController.php`
- `src/Http/Router.php`
- `public/index.php`
- `templates/service-orders/index.php`
- `templates/service-orders/create.php`
- `templates/service-orders/show.php`
- `public/assets/app.css`
- `tests/Feature/ServiceOrderFlowTest.php`

## Verification

composer test

## Observability Impact

Order detail page exposes current status, totals, validation errors and status history as operator diagnostics.
