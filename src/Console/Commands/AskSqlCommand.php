<?php

declare(strict_types=1);

namespace AskSql\AskSql\Console\Commands;

use Illuminate\Console\Command;

class AskSqlCommand extends Command
{
    /**
     * The command signature.
     */
    protected $signature = 'asksql:placeholder';

    /**
     * The command description.
     */
    protected $description = 'Placeholder Artisan command shipped by the package asksql.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->line('AskSql placeholder command executed.');

        return self::SUCCESS;
    }
}
