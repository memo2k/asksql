<?php

declare(strict_types=1);

use AskSql\AskSql\Support\SchemaPrompt;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::create('customers', function (Blueprint $table) {
        $table->id();
        $table->string('name');
    });

    Schema::create('orders', function (Blueprint $table) {
        $table->id();
        $table->foreignId('customer_id')->constrained('customers');
        $table->integer('total');
    });

    Schema::create('migrations', function (Blueprint $table) {
        $table->id();
        $table->string('migration');
    });

    Schema::create('asksql_questions', function (Blueprint $table) {
        $table->id();
        $table->string('question');
    });

    DB::table('customers')->insert(['name' => 'Ada']);
    DB::table('orders')->insert(['customer_id' => 1, 'total' => 42]);
});

it('describes visible tables, columns, foreign keys, and sample rows', function () {
    $prompt = app(SchemaPrompt::class)->build();

    expect($prompt)
        ->toContain('(SQLite)')
        ->toContain('Table: customers')
        ->toContain('Table: orders')
        ->toContain('name: varchar')
        ->toContain('PRIMARY KEY')
        ->toContain('FK: customer_id → customers.id')
        ->toContain('Ada')
        ->not->toContain('Table: migrations')
        ->not->toContain('Table: asksql_questions');
});

it('limits the prompt to the configured allowlist', function () {
    config(['asksql.allowed_tables' => ['orders']]);

    $prompt = app(SchemaPrompt::class)->build();

    expect($prompt)
        ->toContain('Table: orders')
        ->not->toContain('Table: customers');
});
