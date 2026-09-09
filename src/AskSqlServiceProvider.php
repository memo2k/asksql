<?php

declare(strict_types=1);

namespace AskSql\AskSql;

use AskSql\AskSql\Console\Commands\AskSqlCommand;
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/asksql.php');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'asksql');

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'asksql');

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/asksql.php' => config_path('asksql.php'),
        ], ['asksql', 'asksql-config']);

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/asksql'),
        ], ['asksql', 'asksql-views']);

        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('vendor/asksql'),
        ], ['asksql', 'asksql-lang']);

        $this->publishes([
            __DIR__.'/../public' => public_path('vendor/asksql'),
        ], ['asksql', 'asksql-assets']);

        $this->publishesMigrations([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], ['asksql', 'asksql-migrations']);

        $this->commands([
            AskSqlCommand::class,
        ]);
    }
}
