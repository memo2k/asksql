<?php

declare(strict_types=1);

namespace AskSql\AskSql\Tests;

use AskSql\AskSql\AskSqlServiceProvider;
use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            AskSqlServiceProvider::class,
        ];
    }

    /**
     * @param  Application  $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('cache.default', 'array');
    }
}
