---
name: asksql-development
description: >
  Configure and apply the AskSQL package in Laravel applications.
license: MIT
metadata:
  author: Mehmed
---

# AskSQL

Use this skill when a Laravel application needs to integrate the AskSQL package.

## Primary Goal

- apply the `memo2k/asksql` package's public API in the smallest correct way

## Workflow

### 1. Inspect the Laravel app context

- confirm the app is a Laravel project
- inspect the target code paths where the package should be applied

### 2. Install and configure

- require `memo2k/asksql`
- set `ANTHROPIC_API_KEY` in the host `.env` (or `ASKSQL_ANTHROPIC_API_KEY` if the app already uses Anthropic elsewhere)
- do not put API keys in package or committed config files
- leave `asksql.connection` unset to use the host default database connection
- publish config only when the host needs an allowlist, extra excluded tables, or different limits:

```bash
php artisan vendor:publish --tag="asksql-config"
```

### 3. Apply the package's public API

Call `AskSql\AskSql\Facades\AskSql::ask($question)`. It returns `AskSql\AskSql\QueryResult` with `sql`, `explanation`, `rows`, and `error`. Check `failed()` before using the rows.

```php
use AskSql\AskSql\Facades\AskSql;

$result = AskSql::ask('Which orders are still open?');
```

Read host settings with `config('asksql.*')`. Do not call `env()` in application code for these keys. There is no package UI to install.

## Rules, References, and Templates

Read before executing:

- no additional resource files for this skill

Minimum host `.env`:

```env
ANTHROPIC_API_KEY=
```

Optional overrides: `ASKSQL_CONNECTION`, `ASKSQL_ANTHROPIC_MODEL`, `ASKSQL_MAX_ROWS`. Anthropic `api_version` default `2023-06-01` is the current Messages API version.

## Examples

- A Laravel app installs AskSQL, sets `ANTHROPIC_API_KEY`, and calls `AskSql::ask('Which orders are still open?')` against its default database.
- A controller returns `$result->rows` when `AskSql::ask($question)` does not fail.
- A Laravel app publishes `asksql-config` and sets `allowed_tables` to `['orders', 'products']` so the model cannot see other tables.

## Anti-patterns

- do not document package internals here; keep the skill focused on adoption in Laravel apps
- do not hardcode Anthropic keys in `config/asksql.php` or vendor files
- do not assume a demo store schema or a `text_to_sql_ai` database
- do not add a Blade page or publish views unless the app already needs them; `AskSql::ask()` is the public API
