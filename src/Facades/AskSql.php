<?php

declare(strict_types=1);

namespace AskSql\AskSql\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \AskSql\AskSql\AskSql
 */
class AskSql extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \AskSql\AskSql\AskSql::class;
    }
}
