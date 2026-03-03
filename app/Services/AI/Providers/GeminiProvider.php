<?php

namespace App\Services\AI\Providers;

use GuzzleHttp\Client;
use RuntimeException;

class GeminiProvider implements AiProvider
{
    private Client $client;

    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl,
        private readonly int $timeout,
    ) {
        $this->client = new Client([
            'base_uri' => rtrim($this->baseUrl, '/') . '/',
            'timeout' => $this->timeout,
        ]);
    }

    public function chat(array $messages): array
    {
        $model = (string) config('ai.gemini.model', 'gemini-1.5-flash');
        $startedAt = microtime(true);

        $systemInstruction = '';
        $contents = [];
        foreach ($messages as $message) {
            $role = $message['role'] ?? 'user';
            $text = trim((string) ($message['content'] ?? ''));
            if ($text === '') {
                continue;
            }

            if ($role === 'system') {
                $systemInstruction .= ($systemInstruction === '' ? '' : "\n") . $text;
                continue;
            }

            $contents[] = [
                'role' => $role === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => $text]],
            ];
        }

        $body = [
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.2,
            ],
        ];

        if ($systemInstruction !== '') {
            $body['system_instruction'] = [
                'parts' => [['text' => $systemInstruction]],
            ];
        }

        $response = $this->client->post("models/{$model}:generateContent", [
            'query' => ['key' => $this->apiKey],
            'headers' => ['Content-Type' => 'application/json'],
            'json' => $body,
        ]);

        $durationMs = (int) ((microtime(true) - $startedAt) * 1000);
        $payload = json_decode((string) $response->getBody(), true);

        $content = (string) data_get($payload, 'candidates.0.content.parts.0.text', '');
        if ($content === '') {
            throw new RuntimeException('Gemini response content is empty.');
        }

        return [
            'content' => trim($content),
            'model' => $model,
            'tokens_in' => (int) data_get($payload, 'usageMetadata.promptTokenCount', 0),
            'tokens_out' => (int) data_get($payload, 'usageMetadata.candidatesTokenCount', 0),
            'duration_ms' => $durationMs,
        ];
    }

    public function name(): string
    {
        return 'gemini';
    }
}
