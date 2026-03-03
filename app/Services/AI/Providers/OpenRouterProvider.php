<?php

namespace App\Services\AI\Providers;

use GuzzleHttp\Client;
use RuntimeException;

class OpenRouterProvider implements AiProvider
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
        $model = (string) config('ai.openrouter.model', config('ai.default_model', 'openai/gpt-4o-mini'));
        $startedAt = microtime(true);

        $response = $this->client->post('chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'HTTP-Referer' => (string) config('app.url', 'http://localhost'),
                'X-Title' => (string) config('app.name', 'maccento'),
            ],
            'json' => [
                'model' => $model,
                'messages' => $messages,
                'temperature' => 0.2,
            ],
        ]);

        $durationMs = (int) ((microtime(true) - $startedAt) * 1000);
        $payload = json_decode((string) $response->getBody(), true);

        $content = (string) data_get($payload, 'choices.0.message.content', '');
        if ($content === '') {
            throw new RuntimeException('OpenRouter response content is empty.');
        }

        return [
            'content' => trim($content),
            'model' => (string) data_get($payload, 'model', $model),
            'tokens_in' => (int) data_get($payload, 'usage.prompt_tokens', 0),
            'tokens_out' => (int) data_get($payload, 'usage.completion_tokens', 0),
            'duration_ms' => $durationMs,
        ];
    }

    public function name(): string
    {
        return 'openrouter';
    }
}
