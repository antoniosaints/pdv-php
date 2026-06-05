---
estimated_steps: 1
estimated_files: 10
skills_used: []
---

# T02: Implementar CRUD protegido de produtos e servicos

Implement protected catalog listing, creation, editing, activation/deactivation and detail routes. Add controller methods, templates and navigation links for admin users. Forms must persist type, SKU, name, description, cost, price, stock tracking, stock minimum and active state.

## Inputs

- `src/Catalog/CatalogRepository.php`
- `src/Catalog/CatalogValidator.php`
- `src/Auth/AuthService.php`
- `templates/layout.php`

## Expected Output

- `src/Controllers/CatalogController.php`
- `templates/catalog/index.php`
- `templates/catalog/create.php`
- `templates/catalog/edit.php`
- `templates/catalog/show.php`
- `templates/catalog/_form.php`
- `src/Http/Router.php`
- `templates/layout.php`
- `public/assets/app.css`
- `tests/Feature/CatalogCrudTest.php`

## Verification

composer test

## Observability Impact

Validation messages are rendered inline; catalog changes can later be audited from controller context.
