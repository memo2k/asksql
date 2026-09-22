<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Anthropic
    |--------------------------------------------------------------------------
    |
    | The host application supplies the API key. Prefer ASKSQL_ANTHROPIC_API_KEY
    | when the app already uses Anthropic for something else; otherwise
    | ANTHROPIC_API_KEY is enough.
    |
    | api_version is Anthropic's Messages API version id (still 2023-06-01).
    | It is not a deprecation date.
    |
    */

    'anthropic' => [
        'api_key' => env('ASKSQL_ANTHROPIC_API_KEY') ?: env('ANTHROPIC_API_KEY'),
        'model' => env('ASKSQL_ANTHROPIC_MODEL') ?: env('ANTHROPIC_MODEL', 'claude-haiku-4-5'),
        'max_tokens' => (int) (env('ASKSQL_ANTHROPIC_MAX_TOKENS') ?: env('ANTHROPIC_MAX_TOKENS', 2048)),
        'api_version' => env('ASKSQL_ANTHROPIC_API_VERSION', '2023-06-01'),
        'base_url' => env('ASKSQL_ANTHROPIC_BASE_URL', 'https://api.anthropic.com/v1/messages'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Database connection
    |--------------------------------------------------------------------------
    |
    | Connection used to introspect schema and run generated SELECT queries.
    | null uses the host application's default connection.
    |
    */

    'connection' => env('ASKSQL_CONNECTION'),

    /*
    |--------------------------------------------------------------------------
    | Table visibility
    |--------------------------------------------------------------------------
    |
    | Leave allowed_tables empty to expose every table except excluded_tables.
    | Set an allowlist to expose only specific tables to the model.
    |
    */

    'allowed_tables' => [
        // 'orders',
        // 'products',
    ],

    'excluded_tables' => [
        'migrations',
        'users',
        'password_reset_tokens',
        'sessions',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'personal_access_tokens',
        'asksql_questions',
    ],

    'forbidden_schemas' => [
        'information_schema',
        'mysql',
        'performance_schema',
        'sys',
        'pg_catalog',
        'pg_toast',
    ],

    /*
    |--------------------------------------------------------------------------
    | Limits
    |--------------------------------------------------------------------------
    */

    'limits' => [
        'max_rows' => (int) env('ASKSQL_MAX_ROWS', 1000),
        'max_question_length' => (int) env('ASKSQL_MAX_QUESTION_LENGTH', 2000),
        'queries_per_hour' => (int) env('ASKSQL_QUERIES_PER_HOUR', 60),
        'statement_timeout_seconds' => (int) env('ASKSQL_STATEMENT_TIMEOUT', 5),
    ],

];
