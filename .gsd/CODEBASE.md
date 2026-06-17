# Codebase Map

Generated: 2026-06-06T13:15:50Z | Files: 76 | Described: 0/76
<!-- gsd:codebase-meta {"generatedAt":"2026-06-06T13:15:50Z","fingerprint":"c8a6567a2722c27a3b4849d2c651bb37554fe6e2","fileCount":76,"truncated":false} -->

### (root)/
- `.env.example`
- `.gitignore`
- `composer.json`
- `phpunit.xml`
- `README.md`

### bin/
- `bin/console`
- `bin/verify-install.php`

### config/
- `config/database.php`

### database/migrations/
- `database/migrations/001_create_core_tables.php`
- `database/migrations/002_create_catalog_tables.php`
- `database/migrations/003_create_sales_tables.php`

### database/seeders/
- `database/seeders/catalog_seed.php`

### public/
- `public/index.php`

### public/assets/
- `public/assets/app.css`
- `public/assets/print.js`

### src/Auth/
- `src/Auth/AuthService.php`

### src/Catalog/
- `src/Catalog/CatalogRepository.php`
- `src/Catalog/CatalogValidator.php`
- `src/Catalog/ValidationException.php`

### src/Controllers/
- `src/Controllers/AuthController.php`
- `src/Controllers/CatalogController.php`
- `src/Controllers/DashboardController.php`
- `src/Controllers/HealthController.php`
- `src/Controllers/HomeController.php`
- `src/Controllers/PrintController.php`
- `src/Controllers/SalesController.php`
- `src/Controllers/StockController.php`

### src/Database/
- `src/Database/ConnectionFactory.php`
- `src/Database/Migration.php`
- `src/Database/Migrator.php`

### src/Http/
- `src/Http/Request.php`
- `src/Http/Response.php`
- `src/Http/Router.php`

### src/Http/Middleware/
- `src/Http/Middleware/AuthMiddleware.php`

### src/Sales/
- `src/Sales/SalesRepository.php`
- `src/Sales/SalesValidator.php`
- `src/Sales/ValidationException.php`

### src/Security/
- `src/Security/Csrf.php`

### src/Stock/
- `src/Stock/StockRepository.php`
- `src/Stock/StockValidator.php`
- `src/Stock/ValidationException.php`

### src/Support/
- `src/Support/Env.php`
- `src/Support/HealthCheck.php`

### src/View/
- `src/View/View.php`

### storage/database/
- `storage/database/.gitkeep`

### storage/logs/
- `storage/logs/.gitkeep`

### templates/
- `templates/home.php`
- `templates/layout.php`

### templates/auth/
- `templates/auth/login.php`
- `templates/auth/setup.php`

### templates/catalog/
- `templates/catalog/_form.php`
- `templates/catalog/_variant_form.php`
- `templates/catalog/create.php`
- `templates/catalog/edit.php`
- `templates/catalog/index.php`
- `templates/catalog/show.php`

### templates/dashboard/
- `templates/dashboard/index.php`

### templates/health/
- `templates/health/index.php`

### templates/print/
- `templates/print/label.php`
- `templates/print/receipt.php`

### templates/sales/
- `templates/sales/pos.php`
- `templates/sales/show.php`

### templates/stock/
- `templates/stock/index.php`

### tests/Catalog/
- `tests/Catalog/CatalogLookupTest.php`
- `tests/Catalog/CatalogRepositoryTest.php`

### tests/Database/
- `tests/Database/MigratorTest.php`

### tests/Feature/
- `tests/Feature/AuthGuardTest.php`
- `tests/Feature/CatalogCrudTest.php`
- `tests/Feature/CatalogSeedTest.php`
- `tests/Feature/CatalogVariantTest.php`
- `tests/Feature/HealthTest.php`
- `tests/Feature/PrintPreviewTest.php`
- `tests/Feature/SalesFlowTest.php`
- `tests/Feature/StockFlowTest.php`

### tests/Sales/
- `tests/Sales/SaleRepositoryTest.php`

### tests/Stock/
- `tests/Stock/StockRepositoryTest.php`
