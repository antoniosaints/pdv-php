---
id: T02
parent: S01
milestone: M001
key_files:
  - .gitignore
  - composer.lock
  - config/database.php
  - src/Database/Migration.php
  - src/Database/ConnectionFactory.php
  - src/Database/Migrator.php
  - database/migrations/001_create_core_tables.php
  - bin/console
  - phpunit.xml
  - tests/Database/MigratorTest.php
key_decisions:
  - Migrations recebem o driver PDO para permitir ramificações SQLite/MySQL sem espalhar SQL específico pelo domínio.
duration: 
verification_result: mixed
completed_at: 2026-06-05T17:35:17.133Z
blocker_discovered: false
---

# T02: Adicionada camada PDO SQLite, migrations versionadas, console e teste automatizado de idempotência.

**Adicionada camada PDO SQLite, migrations versionadas, console e teste automatizado de idempotência.**

## What Happened

Foi adicionada a configuração de banco com PDO, suporte a SQLite padrão e configuração MySQL futura, connection factory com criação automática do diretório SQLite, migration runner idempotente, migration base para usuários, auditoria e configurações, comando de console para executar e consultar migrations, PHPUnit e teste automatizado cobrindo idempotência e criação das tabelas core. Também foi criado `.gitignore` para evitar versionar vendor, `.env`, logs e bancos locais.

## Verification

Executados `php -l database/migrations/001_create_core_tables.php && composer test` e `php bin/console migrate && php bin/console migrate:status`; lint passou, PHPUnit passou com 1 teste e 8 assertions, migration real executou e status mostrou 1 executada e 0 pendentes.

## Verification Evidence

| # | Command | Exit Code | Verdict | Duration |
|---|---------|-----------|---------|----------|
| 1 | `php -l database/migrations/001_create_core_tables.php && composer test — exit 0, pass: OK (1 test, 8 assertions)` | -1 | unknown (coerced from string) | 0ms |
| 2 | `php bin/console migrate && php bin/console migrate:status — exit 0, pass: migration executada e 0 pendentes` | -1 | unknown (coerced from string) | 0ms |

## Deviations

Adicionados `src/Database/Migration.php`, `.gitignore` e `composer.lock` como suporte necessário. A flag `--seed-admin` foi aceita pelo console, mas o seed real ficou para T03 conforme planejado.

## Known Issues

Nenhum bloqueador. O banco SQLite padrão foi criado localmente em `storage/database/pdv.sqlite`, ignorado pelo Git.

## Files Created/Modified

- `.gitignore`
- `composer.lock`
- `config/database.php`
- `src/Database/Migration.php`
- `src/Database/ConnectionFactory.php`
- `src/Database/Migrator.php`
- `database/migrations/001_create_core_tables.php`
- `bin/console`
- `phpunit.xml`
- `tests/Database/MigratorTest.php`
