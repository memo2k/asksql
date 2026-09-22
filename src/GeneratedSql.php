<?php

declare(strict_types=1);

namespace AskSql\AskSql;

final readonly class GeneratedSql
{
    private function __construct(
        public ?string $sql,
        public string $explanation,
        public ?string $error,
    ) {}

    public static function success(string $sql, string $explanation): self
    {
        return new self($sql, $explanation, null);
    }

    public static function failure(string $error): self
    {
        return new self(null, '', $error);
    }

    public function failed(): bool
    {
        return $this->error !== null;
    }
}
