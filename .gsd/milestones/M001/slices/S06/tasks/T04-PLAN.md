---
estimated_steps: 1
estimated_files: 4
skills_used: []
---

# T04: Integrar navegacao documentacao e UAT de ordens

Add service-order links to layout/dashboard and document the service-order flow in README. Run final S06 verification through PHPUnit, install verifier and browser UAT: login, open service orders, create an order with demo service and product, advance status, close with payment, confirm sale detail and stock decrement with no console/network failures.

## Inputs

- `templates/layout.php`
- `templates/dashboard/index.php`
- `README.md`
- `tests/Feature/ServiceOrderFlowTest.php`

## Expected Output

- `templates/layout.php`
- `templates/dashboard/index.php`
- `README.md`
- `tests/Feature/ServiceOrderFlowTest.php`

## Verification

composer test

## Observability Impact

Final UAT confirms service-order operational surfaces are reachable from navigation and stable through the real web entrypoint.
