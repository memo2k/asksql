<?php

declare(strict_types=1);

namespace AskSql\AskSql\Contracts;

use AskSql\AskSql\GeneratedSql;

interface SqlGenerator
{
    public function generate(string $question, string $schemaPrompt): GeneratedSql;
}
