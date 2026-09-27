<?php

declare(strict_types=1);

it('reads comma separated table lists from the environment', function () {
    setAskSqlEnv('ASKSQL_ALLOWED_TABLES', 'orders, products');
    setAskSqlEnv('ASKSQL_EXCLUDED_TABLES', 'invoices');

    $config = require dirname(__DIR__, 2).'/config/asksql.php';

    expect($config['allowed_tables'])->toBe(['orders', 'products'])
        ->and($config['excluded_tables'])->toContain('migrations', 'invoices');

    setAskSqlEnv('ASKSQL_ALLOWED_TABLES', null);
    setAskSqlEnv('ASKSQL_EXCLUDED_TABLES', null);
});

function setAskSqlEnv(string $key, ?string $value): void
{
    if ($value === null) {
        putenv($key);
        unset($_ENV[$key], $_SERVER[$key]);

        return;
    }

    putenv($key.'='.$value);
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}
