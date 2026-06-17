---
estimated_steps: 1
estimated_files: 2
skills_used: []
---

# T04: Documentar e validar UAT de relatorios

Update README with dashboard/report usage and run final S07 verification: PHP lint, PHPUnit, install verifier and browser UAT. UAT must log in, verify dashboard metrics/tips, open reports, confirm sales/profit/projection/low-stock/open-order data and no console/network failures.

## Inputs

- `README.md`
- `src/Reports/ReportsRepository.php`
- `templates/dashboard/index.php`
- `templates/reports/index.php`

## Expected Output

- `README.md`
- `tests/Feature/DashboardReportTest.php`

## Verification

composer test

## Observability Impact

Final UAT proves dashboard/report surfaces are reachable and trustworthy through the real PHP entrypoint.
