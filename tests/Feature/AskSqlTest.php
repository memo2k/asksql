<?php

declare(strict_types=1);

use AskSql\AskSql\Contracts\SqlGenerator;
use AskSql\AskSql\Facades\AskSql;
use AskSql\AskSql\GeneratedSql;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('runs the generated select and returns the rows', function () {
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
