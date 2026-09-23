<?php

declare(strict_types=1);

use AskSql\AskSql\AskSql;

it('resolves the singleton', function () {
    expect(app(AskSql::class))->toBeInstanceOf(AskSql::class);
});

it('returns the same instance from the container', function () {
    expect(app(AskSql::class))->toBe(app(AskSql::class));
});

it('merges the package config', function () {
    expect(config('asksql.anthropic.api_key'))->toBeNull();
    expect(config('asksql.anthropic.api_version'))->toBe('2023-06-01');
    expect(config('asksql.anthropic.model'))->toBe('claude-haiku-4-5');
    expect(config('asksql.connection'))->toBeNull();
    expect(config('asksql.allowed_tables'))->toBe([]);
    expect(config('asksql.excluded_tables'))->toContain('migrations');
    expect(config('asksql.limits.max_rows'))->toBe(1000);
});

it('allows the host application to override package config', function () {
    config([
        'asksql.connection' => 'mysql',
        'asksql.limits.max_rows' => 10,
        'asksql.allowed_tables' => ['orders', 'products'],
    ]);

    expect(config('asksql.connection'))->toBe('mysql');
    expect(config('asksql.limits.max_rows'))->toBe(10);
    expect(config('asksql.allowed_tables'))->toBe(['orders', 'products']);
});
