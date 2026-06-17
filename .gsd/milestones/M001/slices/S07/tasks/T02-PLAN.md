---
estimated_steps: 1
estimated_files: 6
skills_used: []
---

# T02: Transformar dashboard em painel gerencial real

Wire ReportsRepository into Router/public bootstrap and DashboardController. Replace placeholder dashboard cards with real metrics and deterministic tips based on sales, profit, low stock and open orders. Feature tests must prove authenticated dashboard renders real numbers, empty state and tip text.

## Inputs

- `src/Reports/ReportsRepository.php`
- `templates/dashboard/index.php`
- `tests/Feature/ServiceOrderFlowTest.php`
- `tests/Feature/StockFlowTest.php`

## Expected Output

- `src/Controllers/DashboardController.php`
- `src/Http/Router.php`
- `public/index.php`
- `templates/dashboard/index.php`
- `public/assets/app.css`
- `tests/Feature/DashboardReportTest.php`

## Verification

composer test

## Observability Impact

Dashboard becomes the primary protected health/decision surface for sales, stock and open-order status.
