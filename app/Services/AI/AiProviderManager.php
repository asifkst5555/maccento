<?php

namespace App\Services\AI;

use App\Services\AI\Providers\AiProvider;
use App\Services\AI\Providers\GeminiProvider;
use App\Services\AI\Providers\OpenAiProvider;
use App\Services\AI\Providers\OpenRouterProvider;
use App\Services\AI\Providers\StubAiProvider;
use Illuminate\Support\Facades\Log;

class AiProviderManager
{
    public function provider(): AiProvider
    {
        $provider = strtolower(trim((string) config('ai.provider', 'auto')));

        if ($provider === 'openrouter') {
            return $this->openRouterOrStub();
        }

        if ($provider === 'openai') {
            return $this->openAiOrStub();
        }

        if ($provider === 'gemini') {
            return $this->geminiOrStub();
        }

        if ($provider === 'stub') {
            return new StubAiProvider();
        }

        // Auto mode (default): prefer OpenRouter, then OpenAI, then Gemini.
        if (filled(config('ai.openrouter.api_key'))) {
            return $this->openRouterOrStub();
        }
        if (filled(config('ai.openai.api_key'))) {
            return $this->openAiOrStub();
        }
        if (filled(config('ai.gemini.api_key'))) {
            return $this->geminiOrStub();
        }

        Log::warning('AI provider fallback to stub: no provider API key configured.', [
            'configured_provider' => $provider,
        ]);
        return new StubAiProvider();
    }

    private function openRouterOrStub(): AiProvider
    {
        if (!filled(config('ai.openrouter.api_key'))) {
            Log::warning('AI provider openrouter requested but OPENROUTER_API_KEY is missing. Falling back to stub.');
            return new StubAiProvider();
        }

        return new OpenRouterProvider(
            (string) config('ai.openrouter.api_key'),
            (string) config('ai.openrouter.base_url', 'https://openrouter.ai/api/v1'),
            (int) config('ai.openrouter.timeout', 20),
        );
    }

    private function openAiOrStub(): AiProvider
    {
        if (!filled(config('ai.openai.api_key'))) {
            Log::warning('AI provider openai requested but OPENAI_API_KEY is missing. Falling back to stub.');
            return new StubAiProvider();
        }

        return new OpenAiProvider(
            (string) config('ai.openai.api_key'),
            (string) config('ai.openai.base_url', 'https://api.openai.com/v1'),
            (int) config('ai.openai.timeout', 20),
        );
    }

    private function geminiOrStub(): AiProvider
    {
        if (!filled(config('ai.gemini.api_key'))) {
            Log::warning('AI provider gemini requested but GEMINI_API_KEY is missing. Falling back to stub.');
            return new StubAiProvider();
        }

        return new GeminiProvider(
            (string) config('ai.gemini.api_key'),
            (string) config('ai.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta'),
            (int) config('ai.gemini.timeout', 20),
        );
    }
}
