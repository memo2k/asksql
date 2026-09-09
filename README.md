<div align="center">
    <h1>AskSQL</h1>
</div>

<p align="center">
    <a href="https://packagist.org/packages/memo2k/asksql"><img src="https://img.shields.io/packagist/v/memo2k/asksql.svg?style=flat-square" alt="Packagist"></a>
    <a href="https://packagist.org/packages/memo2k/asksql"><img src="https://img.shields.io/packagist/php-v/memo2k/asksql.svg?style=flat-square" alt="PHP from Packagist"></a>
    <a href="https://packagist.org/packages/memo2k/asksql"><img src="https://badge.laravel.cloud/badge/memo2k/asksql?style=flat" alt="Laravel versions"></a>
    <a href="https://github.com/memo2k/asksql/actions"><img alt="GitHub Workflow Status (main)" src="https://img.shields.io/github/actions/workflow/status/memo2k/asksql/tests.yml?branch=main&label=Tests&style=flat-square"></a>
    <a href="https://packagist.org/packages/memo2k/asksql"><img src="https://img.shields.io/packagist/dt/memo2k/asksql.svg?style=flat-square" alt="Total Downloads"></a>
</p>

AI Text to SQL

## Installation

You can install the package via Composer:

```bash
composer require memo2k/asksql
```

You may publish all of the package's resources at once:

```bash
php artisan vendor:publish --tag="asksql"
```

Or, you may publish each resource individually:

### Publishing the Configuration File

```bash
php artisan vendor:publish --tag="asksql-config"
```

### Publishing and Running the Migrations

```bash
php artisan vendor:publish --tag="asksql-migrations"
php artisan migrate
```

### Publishing the Views

```bash
php artisan vendor:publish --tag="asksql-views"
```

### Publishing the Translations

```bash
php artisan vendor:publish --tag="asksql-lang"
```

### Publishing the Public Assets

```bash
php artisan vendor:publish --tag="asksql-assets"
```

## Usage

<!-- Add a basic usage example here. -->

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
