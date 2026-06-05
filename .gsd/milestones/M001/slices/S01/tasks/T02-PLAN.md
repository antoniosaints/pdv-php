---
estimated_steps: 1
estimated_files: 7
skills_used: []
---

# T02: Adicionar PDO SQLite e migrations

Add database configuration, PDO connection factory, migration runner and initial migrations for schema bookkeeping, users, products placeholder-independent audit/log tables and app settings. Add a console command to run migrations and a PHP test command that proves migrations are idempotent on SQLite.

## Inputs

- `composer.json`
- `.env.example`
- `src/Support/Env.php`

## Expected Output

- `config/database.php`
- `src/Database/ConnectionFactory.php`
- `src/Database/Migrator.php`
- `database/migrations/001_create_core_tables.php`
- `bin/console`
- `tests/Database/MigratorTest.php`
- `phpunit.xml`

## Verification

composer test

## Observability Impact

Migration status becomes inspectable through the migrations table and console output.
