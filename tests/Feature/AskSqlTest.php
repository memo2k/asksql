<?php

declare(strict_types=1);

use AskSql\AskSql\Contracts\SqlGenerator;
use AskSql\AskSql\Facades\AskSql;
use AskSql\AskSql\GeneratedSql;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;

it('runs the generated select and returns the rows', function () {
    config(['asksql.limits.statement_timeout_seconds' => 1]);

    Schema::create('orders', function (Blueprint $table) {
        $table->id();
        $table->string('name');
    });

    DB::table('orders')->insert([
        ['name' => 'Ada'],
        ['name' => 'Grace'],
    ]);

    $this->app->instance(SqlGenerator::class, new class implements SqlGenerator
    {
        public function generate(string $question, string $schemaPrompt): GeneratedSql
        {
            expect($schemaPrompt)->toContain('Table: orders')
                ->and($question)->toBe('List orders');

            return GeneratedSql::success('SELECT name FROM orders', 'Lists order names.');
        }
    });

    $result = AskSql::ask('List orders');

    expect($result->failed())->toBeFalse()
        ->and($result->sql)->toBe('SELECT name FROM orders LIMIT 1000')
        ->and($result->explanation)->toBe('Lists order names.')
        ->and(collect($result->rows)->pluck('name')->all())->toBe(['Ada', 'Grace']);
});

it('groups large numbers in the returned rows', function () {
    Schema::create('orders', function (Blueprint $table) {
        $table->unsignedBigInteger('total');
        $table->integer('loss');
        $table->integer('quantity');
        $table->decimal('amount', 12, 2);
        $table->string('rate');
        $table->string('code');
        $table->string('name');
        $table->string('note');
    });

    DB::table('orders')->insert([
        'total' => 2359325,
        'loss' => -2359325,
        'quantity' => 42,
        'amount' => 1234.5,
        'rate' => '-1234.50',
        'code' => '007',
        'name' => 'Ada',
        'note' => 'order 2359325',
    ]);

    $this->app->instance(SqlGenerator::class, new class implements SqlGenerator
    {
        public function generate(string $question, string $schemaPrompt): GeneratedSql
        {
            return GeneratedSql::success(
                'SELECT total, loss, quantity, amount, rate, code, name, note FROM orders',
                'Shows totals.',
            );
        }
    });

    $result = AskSql::ask('Show totals');

    expect($result->failed())->toBeFalse()
        ->and($result->rows)->toBe([
            [
                'total' => '2 359 325',
                'loss' => '-2 359 325',
                'quantity' => 42,
                'amount' => '1 234.5',
                'rate' => '-1 234.50',
                'code' => '007',
                'name' => 'Ada',
                'note' => 'order 2359325',
            ],
        ]);
});

it('returns the generator error without querying', function () {
    $this->app->instance(SqlGenerator::class, new class implements SqlGenerator
    {
        public function generate(string $question, string $schemaPrompt): GeneratedSql
        {
            return GeneratedSql::failure('AI service is busy. Try again in a moment.');
        }
    });

    $result = AskSql::ask('List orders');

    expect($result->failed())->toBeTrue()
        ->and($result->error)->toBe('AI service is busy. Try again in a moment.')
        ->and($result->rows)->toBe([])
        ->and($result->sql)->toBeNull();
});

it('does not run sql that fails validation', function () {
    Schema::create('orders', function (Blueprint $table) {
        $table->id();
        $table->string('name');
    });

    DB::table('orders')->insert(['name' => 'Ada']);

    $this->app->instance(SqlGenerator::class, new class implements SqlGenerator
    {
        public function generate(string $question, string $schemaPrompt): GeneratedSql
        {
            return GeneratedSql::success('DELETE FROM orders', 'Removes orders.');
        }
    });

    $result = AskSql::ask('Delete orders');

    expect($result->failed())->toBeTrue()
        ->and($result->sql)->toBeNull()
        ->and(DB::table('orders')->count())->toBe(1);
});

it('rejects a question that is longer than the configured limit', function () {
    config(['asksql.limits.max_question_length' => 5]);

    $this->app->instance(SqlGenerator::class, new class implements SqlGenerator
    {
        public function generate(string $question, string $schemaPrompt): GeneratedSql
        {
            throw new RuntimeException('The generator should not be called.');
        }
    });

    $result = AskSql::ask('too long');

    expect($result->failed())->toBeTrue()
        ->and($result->error)->toBe('The question is too long.');
});

it('stops after the hourly question limit', function () {
    config(['asksql.limits.queries_per_hour' => 1]);
    RateLimiter::clear('asksql');

    $this->app->instance(SqlGenerator::class, new class implements SqlGenerator
    {
        public function generate(string $question, string $schemaPrompt): GeneratedSql
        {
            return GeneratedSql::failure('skip');
        }
    });

    expect(AskSql::ask('List orders')->error)->toBe('skip')
        ->and(AskSql::ask('List orders')->error)->toBe('Too many questions this hour. Please try again later.');
});
