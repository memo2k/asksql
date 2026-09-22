<?php

declare(strict_types=1);

namespace AskSql\AskSql\Generators;

use AskSql\AskSql\Contracts\SqlGenerator;
use AskSql\AskSql\GeneratedSql;
use AskSql\AskSql\Support\SqlValidator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AnthropicSqlGenerator implements SqlGenerator
{
    public function __construct(private readonly SqlValidator $sqlValidator) {}

    public function generate(string $question, string $schemaPrompt): GeneratedSql
    {
        $apiKey = $this->apiKey();

        if ($apiKey === null) {
            Log::error('AskSQL Anthropic API key is not configured.');

            return GeneratedSql::failure('Something went wrong. Try again later.');
        }

        $response = Http::timeout(60)
            ->withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => $this->stringConfig('asksql.anthropic.api_version', '2023-06-01'),
                'content-type' => 'application/json',
            ])
            ->post($this->stringConfig('asksql.anthropic.base_url', 'https://api.anthropic.com/v1/messages'), [
                'model' => $this->stringConfig('asksql.anthropic.model', 'claude-haiku-4-5'),
                'max_tokens' => $this->intConfig('asksql.anthropic.max_tokens', 2048),
                'system' => $this->systemPrompt(),
                'messages' => [
                    ['role' => 'user', 'content' => "Schema:\n{$schemaPrompt}\n\nQuestion: {$question}"],
                ],
            ]);

        if ($response->failed()) {
            Log::error('AskSQL Anthropic request failed.', ['status' => $response->status()]);

            if ($response->status() === 429) {
                return GeneratedSql::failure('AI service is busy. Try again in a moment.');
            }

            return GeneratedSql::failure('Could not reach the AI service. Try again.');
        }

        $text = $this->responseText($response->json());

        if ($text === null) {
            Log::error('AskSQL Anthropic returned an invalid response.');

            return GeneratedSql::failure('The model returned an invalid response. Try again.');
        }

        $payload = $this->decodeJsonPayload($text);

        if ($payload === null) {
            Log::error('AskSQL Anthropic returned an invalid response.');

            return GeneratedSql::failure('The model returned an invalid response. Try again.');
        }

        $sql = $payload['sql'] ?? '';

        if (! is_string($sql)) {
            $sql = '';
        }

        $validated = $this->sqlValidator->validate($sql);

        if (isset($validated['error'])) {
            Log::error('AskSQL rejected generated SQL.');

            return GeneratedSql::failure($validated['error']);
        }

        $explanation = $payload['explanation'] ?? '';

        if (! is_string($explanation)) {
            $explanation = '';
        }

        return GeneratedSql::success($validated['sql'], $explanation);
    }

    private function apiKey(): ?string
    {
        $apiKey = config('asksql.anthropic.api_key');

        return is_string($apiKey) && $apiKey !== '' ? $apiKey : null;
    }

    private function stringConfig(string $key, string $default): string
    {
        $value = config($key, $default);

        return is_string($value) && $value !== '' ? $value : $default;
    }

    private function intConfig(string $key, int $default): int
    {
        $value = config($key, $default);

        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && is_numeric($value)) {
            return (int) $value;
        }

        return $default;
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
You are an expert SQL analyst. Generate a single read-only SQL query that answers the user's question.

Rules:
- Output ONLY valid JSON with keys "sql" and "explanation" (no markdown fences).
- sql must be ONE statement: SELECT or WITH ... SELECT only.
- Never use INSERT, UPDATE, DELETE, DDL, or multiple statements.
- Use exact table and column names from the schema.
- The schema names the database dialect. Use that dialect, and double-quote identifiers only when required.
- Prefer explicit JOINs.
- Include LIMIT (default 100) unless the question needs aggregation over all rows.
- Use LIMIT count OFFSET offset for pagination, not LIMIT offset, count.
- explanation is 1-2 sentences in plain English.
- If the question is not clear, return an empty string for both sql and explanation.
- If the question is not related to the schema, return an empty string for both sql and explanation.
PROMPT;
    }

    private function responseText(mixed $body): ?string
    {
        if (! is_array($body)) {
            return null;
        }

        $content = $body['content'] ?? null;

        if (! is_array($content) || ! isset($content[0]) || ! is_array($content[0])) {
            return null;
        }

        $text = $content[0]['text'] ?? null;

        return is_string($text) && trim($text) !== '' ? $text : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeJsonPayload(string $text): ?array
    {
        $cleaned = trim($text);
        $cleaned = trim($cleaned, "\"'");
        $cleaned = trim($cleaned);

        if (preg_match('/^```(?:json)?\s*(.*?)\s*```$/is', $cleaned, $matches) === 1) {
            $cleaned = trim($matches[1]);
        }

        $decoded = $this->decodeJsonObject($cleaned);

        if ($decoded !== null) {
            return $decoded;
        }

        if (preg_match('/\{.*\}/s', $cleaned, $matches) === 1) {
            return $this->decodeJsonObject($matches[0]);
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeJsonObject(string $json): ?array
    {
        $decoded = json_decode($json, true);

        if (! is_array($decoded) || array_is_list($decoded)) {
            return null;
        }

        $payload = [];

        foreach ($decoded as $key => $value) {
            if (is_string($key)) {
                $payload[$key] = $value;
            }
        }

        return $payload;
    }
}
