<?php

declare(strict_types=1);

use AskSql\AskSql\Contracts\SqlGenerator;
use AskSql\AskSql\Generators\AnthropicSqlGenerator;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'asksql.anthropic.api_key' => 'test-key',
        'asksql.anthropic.model' => 'claude-haiku-4-5',
        'asksql.limits.max_rows' => 50,
    ]);

    Http::preventStrayRequests();
});

it('binds the anthropic generator as the sql generator', function () {
    expect(app(SqlGenerator::class))
        ->toBeInstanceOf(AnthropicSqlGenerator::class)
        ->toBe(app(SqlGenerator::class));
});

it('returns validated sql from a successful anthropic response', function () {
    Http::fake([
        'https://api.anthropic.com/*' => Http::response([
            'content' => [
                ['text' => '{"sql":"SELECT id FROM orders","explanation":"Lists order ids."}'],
            ],
        ]),
    ]);

    $result = app(SqlGenerator::class)->generate('How many orders?', "Database: app (SQLite)\nTable: orders");

    expect($result->failed())->toBeFalse()
        ->and($result->sql)->toBe('SELECT id FROM orders LIMIT 50')
        ->and($result->explanation)->toBe('Lists order ids.');

    Http::assertSent(function ($request): bool {
        $message = $request['messages'][0]['content'];

        return $request->hasHeader('x-api-key', 'test-key')
            && $request->hasHeader('anthropic-version', '2023-06-01')
            && $request['model'] === 'claude-haiku-4-5'
            && str_contains($message, 'How many orders?')
            && str_contains($message, 'Table: orders');
    });
});

it('accepts json wrapped in a markdown fence', function () {
    Http::fake([
        'https://api.anthropic.com/*' => Http::response([
            'content' => [
                ['text' => "```json\n{\"sql\":\"SELECT id FROM orders\",\"explanation\":\"Listed.\"}\n```"],
            ],
        ]),
    ]);

    $result = app(SqlGenerator::class)->generate('List orders', 'Table: orders');

    expect($result->sql)->toBe('SELECT id FROM orders LIMIT 50')
        ->and($result->explanation)->toBe('Listed.');
});

it('does not call anthropic when the api key is missing', function () {
    config(['asksql.anthropic.api_key' => null]);

    Http::fake();

    $result = app(SqlGenerator::class)->generate('How many orders?', 'Table: orders');

    expect($result->failed())->toBeTrue()
        ->and($result->error)->toBe('Something went wrong. Try again later.');

    Http::assertNothingSent();
});

it('returns a busy message when anthropic rate limits the request', function () {
    Http::fake([
        'https://api.anthropic.com/*' => Http::response(['error' => 'rate limited'], 429),
    ]);

    $result = app(SqlGenerator::class)->generate('How many orders?', 'Table: orders');

    expect($result->error)->toBe('AI service is busy. Try again in a moment.');
});

it('returns a reachability message when anthropic fails', function () {
    Http::fake([
        'https://api.anthropic.com/*' => Http::response(['error' => 'unavailable'], 500),
    ]);

    $result = app(SqlGenerator::class)->generate('How many orders?', 'Table: orders');

    expect($result->error)->toBe('Could not reach the AI service. Try again.');
});

it('rejects an invalid model payload', function () {
    Http::fake([
        'https://api.anthropic.com/*' => Http::response([
            'content' => [
                ['text' => 'not json'],
            ],
        ]),
    ]);

    $result = app(SqlGenerator::class)->generate('How many orders?', 'Table: orders');

    expect($result->error)->toBe('The model returned an invalid response. Try again.');
});

it('rejects sql that fails validation', function () {
    Http::fake([
        'https://api.anthropic.com/*' => Http::response([
            'content' => [
                ['text' => '{"sql":"DELETE FROM orders","explanation":"Removes orders."}'],
            ],
        ]),
    ]);

    $result = app(SqlGenerator::class)->generate('Delete orders', 'Table: orders');

    expect($result->failed())->toBeTrue()
        ->and($result->sql)->toBeNull();
});
