<div align="center">
    <h1>AskSQL</h1>
</div>

<p align="center">
    <a href="https://packagist.org/packages/memo2k/asksql"><img src="https://img.shields.io/packagist/v/memo2k/asksql.svg?style=flat-square" alt="Packagist"></a>
    <a href="https://packagist.org/packages/memo2k/asksql"><img src="https://img.shields.io/packagist/php-v/memo2k/asksql.svg?style=flat-square" alt="PHP from Packagist"></a>
    <a href="https://packagist.org/packages/memo2k/asksql"><img src="https://badge.laravel.cloud/badge/memo2k/asksql?style=flat" alt="Laravel versions"></a>
    <a href="https://github.com/memo2k/asksql/actions"><img alt="Tests" src="https://img.shields.io/github/actions/workflow/status/memo2k/asksql/tests.yml?branch=master&label=Tests&style=flat-square"></a>
    <a href="https://packagist.org/packages/memo2k/asksql"><img src="https://img.shields.io/packagist/dt/memo2k/asksql.svg?style=flat-square" alt="Total Downloads"></a>
</p>

<p align="center">
    <img src=".github/demo1.gif" alt="AskSQL demo" width="100%">
</p>

AI Text to SQL

> [!NOTE]
> AskSQL only looks at the structure of the database. It does not fetch row values when it builds the prompt for Anthropic.

## Installation

You can install the package via Composer:

```bash
composer require memo2k/asksql
```

Add your Anthropic key to the host application's `.env`. That is the only required setup. AskSQL loads its configuration automatically and uses the app's default database connection.

```env
ANTHROPIC_API_KEY=
```

## Configuration

Every setting below can be changed from the host `.env`. Commented values are the defaults.

```env
# ASKSQL_ANTHROPIC_API_KEY=
# ASKSQL_ANTHROPIC_MODEL=claude-haiku-4-5
# ASKSQL_ANTHROPIC_MAX_TOKENS=2048
# ASKSQL_ANTHROPIC_API_VERSION=2023-06-01
# ASKSQL_ANTHROPIC_BASE_URL=https://api.anthropic.com/v1/messages
# ASKSQL_CONNECTION=
# ASKSQL_MAX_ROWS=1000
# ASKSQL_MAX_QUESTION_LENGTH=2000
# ASKSQL_QUERIES_PER_HOUR=60
# ASKSQL_STATEMENT_TIMEOUT=5
# ASKSQL_ALLOWED_TABLES=
# ASKSQL_EXCLUDED_TABLES=
```

`ASKSQL_ANTHROPIC_API_KEY` takes precedence when the app already uses `ANTHROPIC_API_KEY` for something else. `ASKSQL_ANTHROPIC_API_VERSION` defaults to `2023-06-01`, which is Anthropic's current Messages API version id. Leave `ASKSQL_CONNECTION` empty to use the default database connection.

`ASKSQL_ALLOWED_TABLES` is a comma-separated allowlist, for example `orders,products`. Leave it empty to expose every table except the excluded ones. Generated SQL that reads a table outside the allowlist is rejected. `ASKSQL_EXCLUDED_TABLES` adds comma-separated names to this built-in list:

`migrations`, `users`, `password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `personal_access_tokens`.

`ASKSQL_MAX_ROWS` caps the `LIMIT` on generated queries. `ASKSQL_MAX_QUESTION_LENGTH` rejects longer questions. `ASKSQL_QUERIES_PER_HOUR` limits how many times `ask()` runs each hour. `ASKSQL_STATEMENT_TIMEOUT` caps query time, in seconds, on MySQL and PostgreSQL.

Publish a copy of the config when you want to edit the PHP file in the app. `forbidden_schemas` has no environment variable. It blocks `information_schema`, `mysql`, `performance_schema`, `sys`, `pg_catalog`, and `pg_toast`.

```bash
php artisan vendor:publish --tag="asksql-config"
```

## Usage

Ask a question about the host application's database. AskSQL inspects the schema, asks the model for a single read-only `SELECT`, checks that SQL, then runs it. The prompt sent to Anthropic lists table names, column types, and foreign keys. It does not include row values.

```php
use AskSql\AskSql\Facades\AskSql;

$result = AskSql::ask('Which orders are still open?');

if ($result->failed()) {
    // $result->error
}

$result->sql;
$result->explanation;
$result->rows;
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Thank you for considering contributing to AskSQL! Please review our [contributing guide](.github/CONTRIBUTING.md) to get started.

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

- [Mehmed](https://github.com/memo2k)
- [All Contributors](../../contributors)

## License

AskSQL is open-sourced software licensed under the [MIT license](LICENSE.md).
