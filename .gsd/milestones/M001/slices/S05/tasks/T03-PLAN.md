---
estimated_steps: 1
estimated_files: 4
skills_used: []
---

# T03: Ligar estoque ao fluxo operacional e documentacao

Wire stock navigation into layout/dashboard and update docs. Dashboard must link to stock controls. README must explain stock screen, movement types and that sales/replenishment/adjustment share the same movement ledger. Tests should assert operational link visibility.

## Inputs

- `templates/stock/index.php`
- `templates/dashboard/index.php`

## Expected Output

- `README.md`

## Verification

composer test

## Observability Impact

Operators can reach stock diagnostics from normal navigation without knowing route names.
