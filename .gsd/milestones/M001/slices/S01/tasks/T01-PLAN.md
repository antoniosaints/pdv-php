---
estimated_steps: 1
estimated_files: 9
skills_used: []
---

# T01: Criar esqueleto instalavel PHP Composer

Create the base Composer PHP application structure for a front-controller app. Add `composer.json`, PSR-4 autoloading, `.env.example`, `public/index.php`, config loading, simple response helpers, layout/view renderer, base CSS and README setup notes. Done when Composer validates and the public entrypoint can bootstrap without a database.

## Inputs

- None specified.

## Expected Output

- `composer.json`
- `.env.example`
- `README.md`
- `public/index.php`
- `public/assets/app.css`
- `src/Support/Env.php`
- `src/Http/Response.php`
- `src/View/View.php`
- `templates/layout.php`

## Verification

composer validate

## Observability Impact

Establishes app error mode and log path configuration without logging secrets.
