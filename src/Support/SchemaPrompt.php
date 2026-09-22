<?php

declare(strict_types=1);

namespace AskSql\AskSql\Support;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;

class SchemaPrompt
{
    public function build(): string
    {
        $schema = $this->describe(DB::connection($this->connectionName()));

        $lines = [
            "Database: {$schema['name']} ({$schema['dialect']})",
            'Only query tables listed below. Table names are exact.',
            '',
        ];

        foreach ($schema['tables'] as $table) {
            $lines[] = "Table: {$table['name']}";

            foreach ($table['columns'] as $column) {
                $parts = [$column['name'].': '.$column['type']];

                if ($column['primary']) {
                    $parts[] = 'PRIMARY KEY';
                }

                if (! $column['nullable']) {
                    $parts[] = 'NOT NULL';
                }

                $lines[] = '  - '.implode(', ', $parts);
            }

            foreach ($table['foreign_keys'] as $foreignKey) {
                $lines[] = "  - FK: {$foreignKey['column']} → {$foreignKey['references_table']}.{$foreignKey['references_column']}";
            }

            if ($table['sample_rows'] !== []) {
                $encoded = json_encode($table['sample_rows'], JSON_UNESCAPED_UNICODE);
                $lines[] = '  Sample rows: '.($encoded === false ? '[]' : $encoded);
            }

            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    /**
     * @return array{
     *     name: string,
     *     dialect: string,
     *     tables: list<array{
     *         name: string,
     *         columns: list<array{name: string, type: string, nullable: bool, primary: bool}>,
     *         foreign_keys: list<array{column: string, references_table: string, references_column: string}>,
     *         sample_rows: list<array<string, mixed>>
     *     }>
     * }
     */
    private function describe(Connection $connection): array
    {
        $builder = $connection->getSchemaBuilder();
        $tables = [];

        foreach ($this->visibleTables($builder->getTables()) as $tableName) {
            $primaryKey = $this->primaryKeyColumns($builder->getIndexes($tableName));
            $columns = [];

            foreach ($builder->getColumns($tableName) as $column) {
                $columns[] = [
                    'name' => $column['name'],
                    'type' => $column['type_name'] !== '' ? $column['type_name'] : $column['type'],
                    'nullable' => $column['nullable'],
                    'primary' => in_array($column['name'], $primaryKey, true),
                ];
            }

            $foreignKeys = [];

            foreach ($builder->getForeignKeys($tableName) as $foreignKey) {
                foreach ($foreignKey['columns'] as $index => $column) {
                    $referencesColumn = $foreignKey['foreign_columns'][$index] ?? null;

                    if (! is_string($referencesColumn) || $referencesColumn === '') {
                        continue;
                    }

                    $foreignKeys[] = [
                        'column' => $column,
                        'references_table' => $foreignKey['foreign_table'],
                        'references_column' => $referencesColumn,
                    ];
                }
            }

            $tables[] = [
                'name' => $tableName,
                'columns' => $columns,
                'foreign_keys' => $foreignKeys,
                'sample_rows' => $this->sampleRows($connection, $tableName),
            ];
        }

        return [
            'name' => $connection->getDatabaseName(),
            'dialect' => $this->dialect($connection->getDriverName()),
            'tables' => $tables,
        ];
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

    /**
     * @return list<array<string, mixed>>
     */
    private function sampleRows(Connection $connection, string $table): array
    {
        $rows = [];

        foreach ($connection->table($table)->limit(2)->get() as $row) {
            $rows[] = (array) $row;
        }

        return $rows;
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
