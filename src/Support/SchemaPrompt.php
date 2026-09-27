<?php

declare(strict_types=1);

namespace AskSql\AskSql\Support;

use Illuminate\Support\Facades\DB;

class SchemaPrompt
{
    public function build(): string
    {
        $connection = DB::connection($this->connectionName());
        $builder = $connection->getSchemaBuilder();
        $lines = [
            'Database: '.$connection->getDatabaseName().' ('.$this->dialect($connection->getDriverName()).')',
            'Only query tables listed below. Table names are exact.',
            '',
        ];

        foreach ($this->visibleTables($builder->getTables()) as $tableName) {
            $lines[] = "Table: {$tableName}";
            $primaryKey = $this->primaryKeyColumns($builder->getIndexes($tableName));

            foreach ($builder->getColumns($tableName) as $column) {
                $type = $column['type_name'] !== '' ? $column['type_name'] : $column['type'];
                $parts = [$column['name'].': '.$type];

                if (in_array($column['name'], $primaryKey, true)) {
                    $parts[] = 'PRIMARY KEY';
                }

                if (! $column['nullable']) {
                    $parts[] = 'NOT NULL';
                }

                $lines[] = '  - '.implode(', ', $parts);
            }

            foreach ($builder->getForeignKeys($tableName) as $foreignKey) {
                foreach ($foreignKey['columns'] as $index => $column) {
                    $referencesColumn = $foreignKey['foreign_columns'][$index] ?? null;

                    if (! is_string($referencesColumn) || $referencesColumn === '') {
                        continue;
                    }

                    $lines[] = "  - FK: {$column} → {$foreignKey['foreign_table']}.{$referencesColumn}";
                }
            }

            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    /**
     * @param  list<array{name: string}>  $tables
     * @return list<string>
     */
    private function visibleTables(array $tables): array
    {
        $names = [];

        foreach ($tables as $table) {
            $name = $table['name'];

            if (str_starts_with($name, 'sqlite_') || ! $this->isVisible($name)) {
                continue;
            }

            $names[] = $name;
        }

        sort($names);

        return $names;
    }

    private function isVisible(string $table): bool
    {
        $table = strtolower($table);
        $excluded = array_map(strtolower(...), $this->configuredList('asksql.excluded_tables'));

        if (in_array($table, $excluded, true)) {
            return false;
        }

        $allowed = array_map(strtolower(...), $this->configuredList('asksql.allowed_tables'));

        if ($allowed === []) {
            return true;
        }

        return in_array($table, $allowed, true);
    }

    /**
     * @param  list<array{columns: list<string>, primary: bool}>  $indexes
     * @return list<string>
     */
    private function primaryKeyColumns(array $indexes): array
    {
        $columns = [];

        foreach ($indexes as $index) {
            if ($index['primary']) {
                array_push($columns, ...$index['columns']);
            }
        }

        return $columns;
    }

    private function connectionName(): ?string
    {
        $name = config('asksql.connection');

        return is_string($name) && $name !== '' ? $name : null;
    }

    private function dialect(string $driver): string
    {
        return match ($driver) {
            'mysql', 'mariadb' => 'MySQL',
            'pgsql' => 'PostgreSQL',
            'sqlsrv' => 'SQL Server',
            'sqlite' => 'SQLite',
            default => $driver,
        };
    }

    /**
     * @return list<string>
     */
    private function configuredList(string $configKey): array
    {
        $values = config($configKey, []);

        if (! is_array($values)) {
            return [];
        }

        $items = [];

        foreach ($values as $value) {
            if (is_string($value) && $value !== '') {
                $items[] = $value;
            }
        }

        return $items;
    }
}
