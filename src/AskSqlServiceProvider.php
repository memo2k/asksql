<?php

declare(strict_types=1);

namespace AskSql\AskSql;

use AskSql\AskSql\Contracts\SqlGenerator;
use AskSql\AskSql\Generators\AnthropicSqlGenerator;
use Illuminate\Support\ServiceProvider;

class AskSqlServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/asksql.php', 'asksql');

        $this->app->singleton(AskSql::class);

        $this->app->singleton(SqlGenerator::class, AnthropicSqlGenerator::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/asksql.php' => config_path('asksql.php'),
        ], ['asksql', 'asksql-config']);
    }
}
