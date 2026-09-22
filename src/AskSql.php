<?php

declare(strict_types=1);

namespace AskSql\AskSql;

use AskSql\AskSql\Contracts\SqlGenerator;
use AskSql\AskSql\Support\SchemaPrompt;
use AskSql\AskSql\Support\SqlValidator;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;

class AskSql
{
    public function __construct(
        private readonly SchemaPrompt $schemaPrompt,
        private readonly SqlGenerator $sqlGenerator,
        private readonly SqlValidator $sqlValidator,
    ) {}

    public function ask(string $question): QueryResult
    {
        $generated = $this->sqlGenerator->generate($question, $this->schemaPrompt->build());

        if ($generated->failed()) {
            return QueryResult::failure($generated->error ?? 'Something went wrong. Try again.');
        }

        $validated = $this->sqlValidator->validate((string) $generated->sql);

        if (isset($validated['error'])) {
            return QueryResult::failure($validated['error']);
        }

        return QueryResult::success(
            $validated['sql'],
            $generated->explanation,
            $this->rows($validated['sql']),
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function rows(string $sql): array
    {
        $rows = [];

        foreach ($this->connection()->select($sql) as $row) {
            $rows[] = (array) $row;
        }

        return $rows;
    }

    private function connection(): Connection
    {
        $name = config('asksql.connection');

        return DB::connection(is_string($name) && $name !== '' ? $name : null);
    }
}
