<?php

declare(strict_types=1);

namespace AskSql\AskSql\Tests;

use AskSql\AskSql\AskSqlServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            AskSqlServiceProvider::class,
        ];
    }
}
