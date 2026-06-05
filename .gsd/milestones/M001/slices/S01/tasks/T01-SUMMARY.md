---
id: T01
parent: S01
milestone: M001
key_files:
  - composer.json
  - .env.example
  - README.md
  - public/index.php
  - public/assets/app.css
  - src/Support/Env.php
  - src/Http/Response.php
  - src/View/View.php
  - templates/layout.php
  - templates/home.php
key_decisions:
  - D004 — microkernel PHP próprio com FastRoute e Dotenv em vez de framework pesado.
duration: 
verification_result: mixed
completed_at: 2026-06-05T17:30:20.048Z
blocker_discovered: false
---

# T01: Criado o esqueleto instalável PHP Composer com front controller, templates e CSS responsivo inicial.

**Criado o esqueleto instalável PHP Composer com front controller, templates e CSS responsivo inicial.**

## What Happened

Foi criada a base PHP Composer com PSR-4, dependências mínimas, scripts de desenvolvimento, exemplo de ambiente, README inicial, front controller, carregamento seguro de ambiente, resposta HTTP, renderizador de templates e layout responsivo inicial. A página inicial já comunica o escopo do MVP e deixa links para login e health check que serão implementados nas próximas tarefas.

## Verification

Executado `composer validate --no-check-publish && php -l public/index.php && php -l src/Support/Env.php && php -l src/Http/Response.php && php -l src/View/View.php`; Composer validou e todos os arquivos PHP passaram no lint.

## Verification Evidence

| # | Command | Exit Code | Verdict | Duration |
|---|---------|-----------|---------|----------|
| 1 | `composer validate --no-check-publish && php -l public/index.php && php -l src/Support/Env.php && php -l src/Http/Response.php && php -l src/View/View.php — exit 0, pass` | -1 | unknown (coerced from string) | 0ms |

## Deviations

Foi criado também `templates/home.php` para permitir que o front controller renderize uma página inicial real no T01.

## Known Issues

`composer install` ainda não foi executado; o T01 validou a configuração e sintaxe, enquanto o T02 instalará/validará dependências ao adicionar testes e migrations.

## Files Created/Modified

- `composer.json`
- `.env.example`
- `README.md`
- `public/index.php`
- `public/assets/app.css`
- `src/Support/Env.php`
- `src/Http/Response.php`
- `src/View/View.php`
- `templates/layout.php`
- `templates/home.php`
