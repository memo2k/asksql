<?php

declare(strict_types=1);

namespace AskSql\AskSql\Support;

class SqlValidator
{
    private const int MAX_LENGTH = 16_384;

    private const string FORBIDDEN_PATTERN = '/\b(DELETE|DROP|UPDATE|INSERT|REPLACE|TRUNCATE|ALTER|CREATE|GRANT|REVOKE|CALL|EXEC(?:UTE)?|LOCK|UNLOCK|RENAME|COPY|LOAD|KILL)\b/i';

    /** @var list<string> */
    private const array FORBIDDEN_PHRASES = [
        'COPY TO',
        'COPY FROM',
        'PG_READ_FILE',
        'PG_LS_DIR',
        'FOR UPDATE',
        'FOR SHARE',
        'FOR NO KEY UPDATE',
    ];

    /**
     * @return array{sql: string}|array{error: string}
     */
    public function validate(string $sql): array
    {
        $sql = trim($sql);

        if ($sql === '') {
            return $this->reject('No SQL query was generated. Try rephrasing your question.');
        }

        if (strlen($sql) > self::MAX_LENGTH) {
            return $this->reject('Generated SQL is too long.');
        }

        $check = trim($this->stripComments($sql));

        if ($check === '' || preg_match('/^\s*(WITH|SELECT)\b/i', $check) !== 1) {
            return $this->reject('Only a single SELECT query is allowed.');
        }

        if (str_contains(rtrim($this->maskLiterals($check), " \t\n\r;"), ';')) {
            return $this->reject('Only a single SELECT query is allowed.');
        }

        if (preg_match(self::FORBIDDEN_PATTERN, $check) === 1) {
            return $this->reject('Only read-only SELECT queries are allowed.');
        }

        $upper = strtoupper($check);

        foreach (self::FORBIDDEN_PHRASES as $phrase) {
            if (str_contains($upper, $phrase)) {
                return $this->reject('Only read-only SELECT queries are allowed.');
            }
        }

        $masked = $this->maskLiterals($check);

        if ($this->referencesConfiguredIdentifier($masked, 'asksql.forbidden_schemas')) {
            return $this->reject('Query may only use tables from the configured schema.');
        }

        if ($this->referencesConfiguredIdentifier($masked, 'asksql.excluded_tables')) {
            return $this->reject('Query may only use tables from the configured schema.');
        }

        return ['sql' => $this->enforceLimit($sql)];
    }

    /**
     * @return array{error: string}
     */
    private function reject(string $message): array
    {
        return ['error' => $message];
    }

    private function enforceLimit(string $sql): string
    {
        $sql = rtrim($sql, " \t\n\r;");
        $maxRows = max(1, (int) config('asksql.limits.max_rows', 1000));

        if (preg_match('/\bLIMIT\s+(\d+)\s*,\s*(\d+)\s*$/i', $sql, $matches) === 1) {
            $offset = (int) $matches[1];
            $count = min((int) $matches[2], $maxRows);

            return preg_replace(
                '/\bLIMIT\s+\d+\s*,\s*\d+\s*$/i',
                'LIMIT '.$count.' OFFSET '.$offset,
                $sql,
            ) ?? $sql;
        }

        if (preg_match('/\bLIMIT\s+(\d+)\s+OFFSET\s+(\d+)\s*$/i', $sql, $matches) === 1) {
            $count = min((int) $matches[1], $maxRows);
            $offset = (int) $matches[2];

            return preg_replace(
                '/\bLIMIT\s+\d+\s+OFFSET\s+\d+\s*$/i',
                'LIMIT '.$count.' OFFSET '.$offset,
                $sql,
            ) ?? $sql;
        }

        if (preg_match('/\bLIMIT\s+(\d+)\s*$/i', $sql, $matches) === 1) {
            $count = min((int) $matches[1], $maxRows);

            return preg_replace(
                '/\bLIMIT\s+\d+\s*$/i',
                'LIMIT '.$count,
                $sql,
            ) ?? $sql;
        }

        return $sql.' LIMIT '.$maxRows;
    }

    private function stripComments(string $sql): string
    {
        $sql = preg_replace('/--[^\n]*/', '', $sql) ?? $sql;

        return preg_replace('/\/\*.*?\*\//s', '', $sql) ?? $sql;
    }

    private function maskLiterals(string $sql): string
    {
        $sql = preg_replace("/'([^'\\\\]|\\\\.)*'/", "''", $sql) ?? $sql;

        return preg_replace('/"([^"\\\\]|\\\\.)*"/', '""', $sql) ?? $sql;
    }

    private function referencesConfiguredIdentifier(string $sql, string $configKey): bool
    {
        foreach ($this->configuredList($configKey) as $identifier) {
            if ($this->referencesIdentifier($sql, $identifier)) {
                return true;
            }
        }

        return false;
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

        $identifiers = [];

        foreach ($values as $value) {
            if (is_string($value) && $value !== '') {
                $identifiers[] = $value;
            }
        }

        return $identifiers;
    }

    private function referencesIdentifier(string $sql, string $identifier): bool
    {
        $quoted = preg_quote($identifier, '/');
        $pattern = '/(?:"'.$quoted.'"|\b'.$quoted.'\b)(\s*\.\s*|\b)/i';

        return preg_match($pattern, $sql) === 1;
    }
}
