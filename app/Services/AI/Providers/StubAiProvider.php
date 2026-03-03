<?php

namespace App\Services\AI\Providers;

class StubAiProvider implements AiProvider
{
    public function chat(array $messages): array
    {
        $lastUserMessage = '';
        foreach (array_reverse($messages) as $message) {
            if (($message['role'] ?? '') === 'user') {
                $lastUserMessage = (string) ($message['content'] ?? '');
                break;
            }
        }

        $content = 'Thanks, I captured that. Could you share one more detail?';
        if ($lastUserMessage !== '') {
            $content = 'Got it. ' . mb_substr(trim($lastUserMessage), 0, 120);
        }

        return [
            'content' => $content,
            'model' => 'stub-ai',
            'tokens_in' => 0,
            'tokens_out' => 0,
            'duration_ms' => 1,
        ];
    }

    public function name(): string
    {
        return 'stub';
    }
}
