<?php

declare(strict_types=1);

namespace AskSql\AskSql;

use AskSql\AskSql\Contracts\SqlGenerator;
use AskSql\AskSql\Support\ReadableNumbers;
use AskSql\AskSql\Support\SchemaPrompt;
use AskSql\AskSql\Support\SqlValidator;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

class AskSql
{
    public function __construct(
        private readonly SchemaPrompt $schemaPrompt,
        private readonly SqlGenerator $sqlGenerator,
        private readonly SqlValidator $sqlValidator,
        private readonly ReadableNumbers $readableNumbers,
    ) {}

    public function ask(string $question): QueryResult
    {
        if ($this->questionIsTooLong($question)) {
            return QueryResult::failure('The question is too long.');
        }

        if (RateLimiter::tooManyAttempts('asksql', $this->queriesPerHour())) {
            return QueryResult::failure('Too many questions this hour. Please try again later.');
        }

        RateLimiter::hit('asksql', 3600);

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
            $this->readableNumbers->format($this->rows($validated['sql'])),
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function rows(string $sql): array
    {
        $connection = $this->connection();
        $timeoutApplied = $this->applyStatementTimeout($connection);

        try {
            $rows = [];

            foreach ($connection->select($sql) as $row) {
                $rows[] = (array) $row;
            }

            return $rows;
        } finally {
            if ($timeoutApplied) {
                $this->clearStatementTimeout($connection);
            }
        }
    }

    private function questionIsTooLong(string $question): bool
    {
        $maxLength = (int) config('asksql.limits.max_question_length', 2000);

        if ($maxLength < 1) {
            $maxLength = 2000;
        }

        return mb_strlen($question) > $maxLength;
    }

    private function queriesPerHour(): int
    {
        return max(0, (int) config('asksql.limits.queries_per_hour', 60));
    }

    private function applyStatementTimeout(Connection $connection): bool
    {
        $seconds = (int) config('asksql.limits.statement_timeout_seconds', 5);
        $statement = $this->statementTimeoutSql($connection->getDriverName(), max(0, $seconds));

        if ($statement === null || $seconds < 1) {
            return false;
        }

        $connection->statement($statement);

        return true;
    }

    private function clearStatementTimeout(Connection $connection): void
    {
        $statement = $this->statementTimeoutSql($connection->getDriverName(), 0);

        if ($statement !== null) {
            $connection->statement($statement);
        }
    }

    private function statementTimeoutSql(string $driver, int $seconds): ?string
    {
        $milliseconds = $seconds * 1000;

        return match ($driver) {
            'mysql', 'mariadb' => 'SET SESSION MAX_EXECUTION_TIME = '.$milliseconds,
            'pgsql' => "SET statement_timeout = '{$seconds}s'",
            default => null,
        };
    }

    private function connection(): Connection
    {
        $name = config('asksql.connection');

        return DB::connection(is_string($name) && $name !== '' ? $name : null);
    }
}
