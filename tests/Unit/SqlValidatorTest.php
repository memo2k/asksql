<?php

declare(strict_types=1);

use AskSql\AskSql\Support\SqlValidator;

beforeEach(function () {
    config([
        'asksql.limits.max_rows' => 100,
        'asksql.forbidden_schemas' => ['information_schema', 'mysql'],
        'asksql.excluded_tables' => ['users', 'migrations'],
    ]);
});

it('allows a single select and appends a row limit', function () {
    $result = app(SqlValidator::class)->validate('SELECT id FROM orders');

    expect($result)->toBe(['sql' => 'SELECT id FROM orders LIMIT 100']);
});

it('allows a with select query', function () {
    $result = app(SqlValidator::class)->validate(
        'WITH recent AS (SELECT id FROM orders) SELECT * FROM recent',
    );

    expect($result)->toBe([
        'sql' => 'WITH recent AS (SELECT id FROM orders) SELECT * FROM recent LIMIT 100',
    ]);
});

it('keeps a limit that is already under the maximum', function () {
    $result = app(SqlValidator::class)->validate('SELECT id FROM orders LIMIT 10');

    expect($result)->toBe(['sql' => 'SELECT id FROM orders LIMIT 10']);
});

it('clamps a limit above the configured maximum', function () {
    $result = app(SqlValidator::class)->validate('SELECT id FROM orders LIMIT 5000');

    expect($result)->toBe(['sql' => 'SELECT id FROM orders LIMIT 100']);
});

it('rejects an empty query', function () {
    $result = app(SqlValidator::class)->validate('   ');

    expect($result)->toBe(['error' => 'No SQL query was generated. Try rephrasing your question.']);
});

it('rejects inserts and other writes', function (string $sql) {
    $result = app(SqlValidator::class)->validate($sql);

    expect($result)->toHaveKey('error');
})->with([
    'insert' => 'INSERT INTO orders (id) VALUES (1)',
    'update' => 'UPDATE orders SET id = 1',
    'delete' => 'DELETE FROM orders',
    'drop' => 'DROP TABLE orders',
]);

it('rejects multiple statements', function () {
    $result = app(SqlValidator::class)->validate('SELECT id FROM orders; DROP TABLE orders');

    expect($result)->toBe(['error' => 'Only a single SELECT query is allowed.']);
});

it('rejects a forbidden keyword hidden inside a comment', function () {
    $result = app(SqlValidator::class)->validate('SEL/*x*/ECT id FROM orders; DEL/*x*/ETE FROM orders');

    expect($result)->toHaveKey('error');
});

it('rejects forbidden schemas and excluded tables', function (string $sql) {
    $result = app(SqlValidator::class)->validate($sql);

    expect($result)->toBe(['error' => 'Query may only use tables from the configured schema.']);
})->with([
    'schema' => 'SELECT * FROM information_schema.tables',
    'excluded table' => 'SELECT * FROM users',
]);

it('ignores forbidden words that only appear inside comments on an otherwise valid select', function () {
    $result = app(SqlValidator::class)->validate('SELECT id FROM orders /* DROP TABLE users */');

    expect($result)->toBe(['sql' => 'SELECT id FROM orders /* DROP TABLE users */ LIMIT 100']);
});
