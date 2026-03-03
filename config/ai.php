<?php

return [
    'provider' => env('AI_PROVIDER', 'auto'),
    'default_model' => env('AI_MODEL', 'gpt-4o-mini'),

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'timeout' => (int) env('OPENAI_TIMEOUT', 20),
    ],

    'openrouter' => [
        'api_key' => env('OPENROUTER_API_KEY'),
        'base_url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),
        'model' => env('OPENROUTER_MODEL', 'openai/gpt-4o-mini'),
        'timeout' => (int) env('OPENROUTER_TIMEOUT', 20),
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
        'model' => env('GEMINI_MODEL', 'gemini-1.5-flash'),
        'timeout' => (int) env('GEMINI_TIMEOUT', 20),
    ],

    'limits' => [
        'max_turns' => (int) env('AI_MAX_TURNS', 20),
        'max_input_chars' => (int) env('AI_MAX_INPUT_CHARS', 2000),
    ],
];
