<?php

namespace App\Services\AI;

use App\Services\AI\Providers\AiProvider;
use App\Services\AI\Providers\GeminiProvider;
use App\Services\AI\Providers\OpenAiProvider;
use App\Services\AI\Providers\OpenRouterProvider;
use App\Services\AI\Providers\StubAiProvider;

class AiProviderManager
{
    public function provider(): AiProvider
    {
        $provider = (string) config('ai.provider', 'stub');

        if ($provider === 'openai' && filled(config('ai.openai.api_key'))) {
            return new OpenAiProvider(
                (string) config('ai.openai.api_key'),
                (string) config('ai.openai.base_url', 'https://api.openai.com/v1'),
                (int) config('ai.openai.timeout', 20),
            );
        }

        if ($provider === 'openrouter' && filled(config('ai.openrouter.api_key'))) {
            return new OpenRouterProvider(
                (string) config('ai.openrouter.api_key'),
                (string) config('ai.openrouter.base_url', 'https://openrouter.ai/api/v1'),
                (int) config('ai.openrouter.timeout', 20),
            );
        }

        if ($provider === 'gemini' && filled(config('ai.gemini.api_key'))) {
            return new GeminiProvider(
                (string) config('ai.gemini.api_key'),
                (string) config('ai.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta'),
                (int) config('ai.gemini.timeout', 20),
            );
        }

        return new StubAiProvider();
    }
}
