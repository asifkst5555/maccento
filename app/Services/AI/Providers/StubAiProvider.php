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

        $content = 'Thanks for your message. I can help with services, package options, and booking details. Could you share your property type and required service?';
        if ($lastUserMessage !== '') {
            $normalized = strtolower(trim($lastUserMessage));
            if (str_contains($normalized, 'bonjour') || str_contains($normalized, 'salut')) {
                $content = 'Merci pour votre message. Je peux vous aider avec les services, les forfaits et la reservation. Quel est votre type de propriete et le service souhaite?';
            }
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
