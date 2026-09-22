<?php

declare(strict_types=1);

namespace AskSql\AskSql;

final readonly class QueryResult
{
    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function __construct(
        public ?string $sql,
        public string $explanation,
        public array $rows,
        public ?string $error,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public static function success(string $sql, string $explanation, array $rows): self
    {
        return new self($sql, $explanation, $rows, null);
    }

    public static function failure(string $error): self
    {
        return new self(null, '', [], $error);
    }

    public function failed(): bool
    {
        return $this->error !== null;
    }
}
