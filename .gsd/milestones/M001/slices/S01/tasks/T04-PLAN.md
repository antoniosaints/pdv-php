---
estimated_steps: 1
estimated_files: 8
skills_used: []
---

# T04: Adicionar health check e verificacao de instalacao

Add operational health diagnostics, install verification and final S01 documentation. Health must show PHP version, database connectivity, migration status, writable storage/log paths and current environment without secrets. Add a verification script for clean setup and document shared-hosting deployment assumptions.

## Inputs

- `src/Auth/AuthService.php`
- `src/Database/ConnectionFactory.php`
- `src/Database/Migrator.php`
- `templates/layout.php`

## Expected Output

- `src/Controllers/HealthController.php`
- `templates/health/index.php`
- `bin/verify-install.php`
- `storage/logs/.gitkeep`
- `storage/database/.gitkeep`
- `tests/Feature/HealthTest.php`
- `README.md`

## Verification

composer test

## Observability Impact

Adds the first durable inspection surface for runtime health, migration state and filesystem permissions.
