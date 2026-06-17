---
estimated_steps: 1
estimated_files: 7
skills_used: []
---

# T03: Adicionar pagina protegida de relatorios

Add a protected `/reports` page and route for sales/profit/payment/monthly details. The page must show totals, payment breakdown, top sold items, monthly projection and open-order/low-stock callouts. Feature tests must cover auth guard, populated report content and empty-state rendering.

## Inputs

- `src/Reports/ReportsRepository.php`
- `templates/sales/show.php`
- `templates/stock/index.php`
- `templates/service-orders/index.php`

## Expected Output

- `src/Controllers/ReportsController.php`
- `src/Http/Router.php`
- `templates/reports/index.php`
- `templates/layout.php`
- `templates/dashboard/index.php`
- `public/assets/app.css`
- `tests/Feature/DashboardReportTest.php`

## Verification

composer test

## Observability Impact

Reports page exposes drill-down aggregate tables for diagnosing why dashboard numbers changed.
