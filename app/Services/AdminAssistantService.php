<?php

namespace App\Services;

use App\Models\AiUsageLog;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\AI\AiProviderManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AdminAssistantService
{
    private const CHANNEL = 'admin_panel_assistant';

    public function __construct(
        private readonly AiProviderManager $aiProviderManager,
        private readonly MaccentoKnowledgeService $knowledgeService,
    ) {
    }

    public function sessionForUser(User $user): Conversation
    {
        $conversation = Conversation::query()
            ->where('channel', self::CHANNEL)
            ->where('visitor_id', $this->visitorId($user))
            ->latest('updated_at')
            ->first();

        if ($conversation !== null) {
            return $conversation;
        }

        $conversation = Conversation::query()->create([
            'channel' => self::CHANNEL,
            'visitor_id' => $this->visitorId($user),
            'status' => 'active',
            'started_at' => now(),
            'last_message_at' => now(),
            'metadata' => [
                'user_id' => $user->id,
                'role' => strtolower((string) $user->role),
                'context' => 'admin_panel',
            ],
        ]);

        $conversation->messages()->create([
            'role' => 'assistant',
            'content' => 'I can help with CRM workflows, invoices, clients, media delivery, leads, and email center. Ask a direct question and I will keep the answer practical.',
            'metadata' => [
                'type' => 'welcome',
            ],
        ]);

        return $conversation->fresh(['messages']);
    }

    public function recentMessages(Conversation $conversation, int $limit = 14): Collection
    {
        return $conversation->messages()
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }

    /**
     * @param array{page_title?:string,current_path?:string,page_heading?:string} $pageContext
     * @return array{conversation:Conversation,assistant_message:Message}
     */
    public function reply(User $user, string $content, array $pageContext = [], ?string $conversationId = null): array
    {
        $conversation = $this->resolveConversation($user, $conversationId);

        $trimmedContent = trim($content);
        $conversation->messages()->create([
            'role' => 'user',
            'content' => $trimmedContent,
            'metadata' => [
                'page_title' => $this->cleanPageValue($pageContext['page_title'] ?? null),
                'page_heading' => $this->cleanPageValue($pageContext['page_heading'] ?? null),
                'current_path' => $this->cleanPageValue($pageContext['current_path'] ?? null),
            ],
        ]);

        $conversation->forceFill([
            'last_message_at' => now(),
            'metadata' => array_merge(is_array($conversation->metadata) ? $conversation->metadata : [], [
                'last_page_title' => $this->cleanPageValue($pageContext['page_title'] ?? null),
                'last_page_heading' => $this->cleanPageValue($pageContext['page_heading'] ?? null),
                'last_path' => $this->cleanPageValue($pageContext['current_path'] ?? null),
            ]),
        ])->save();

        $provider = $this->aiProviderManager->provider();
        $prompt = $this->buildPrompt($conversation, $user, $pageContext);
        $durationMs = 0;
        $usage = null;

        try {
            $start = microtime(true);
            $usage = $provider->chat($prompt);
            $durationMs = (int) ((microtime(true) - $start) * 1000);
            $assistantContent = trim((string) ($usage['content'] ?? ''));
        } catch (\Throwable $exception) {
            report($exception);
            $assistantContent = '';
        }

        if ($assistantContent === '') {
            $assistantContent = $this->fallbackReply($pageContext);
        }

        $assistantMessage = $conversation->messages()->create([
            'role' => 'assistant',
            'content' => Str::limit($assistantContent, 5000, ''),
            'model' => $usage['model'] ?? null,
            'tokens_in' => $usage['tokens_in'] ?? null,
            'tokens_out' => $usage['tokens_out'] ?? null,
            'latency_ms' => max($durationMs, (int) ($usage['duration_ms'] ?? 0)),
            'metadata' => [
                'type' => 'admin_assistant_reply',
                'provider' => $provider->name(),
            ],
        ]);

        if ($usage !== null) {
            AiUsageLog::query()->create([
                'conversation_id' => $conversation->id,
                'provider' => $provider->name(),
                'model' => (string) ($usage['model'] ?? ''),
                'tokens_in' => (int) ($usage['tokens_in'] ?? 0),
                'tokens_out' => (int) ($usage['tokens_out'] ?? 0),
                'estimated_cost' => 0,
                'duration_ms' => max($durationMs, (int) ($usage['duration_ms'] ?? 0)),
            ]);
        }

        return [
            'conversation' => $conversation->fresh(),
            'assistant_message' => $assistantMessage,
        ];
    }

    private function resolveConversation(User $user, ?string $conversationId = null): Conversation
    {
        if (filled($conversationId)) {
            $conversation = Conversation::query()
                ->whereKey($conversationId)
                ->where('channel', self::CHANNEL)
                ->where('visitor_id', $this->visitorId($user))
                ->first();

            if ($conversation !== null) {
                return $conversation;
            }
        }

        return $this->sessionForUser($user);
    }

    /**
     * @param array{page_title?:string,current_path?:string,page_heading?:string} $pageContext
     * @return array<int,array{role:string,content:string}>
     */
    private function buildPrompt(Conversation $conversation, User $user, array $pageContext): array
    {
        $messages = [[
            'role' => 'system',
            'content' => implode("\n\n", [
                'You are the Maccento CRM internal admin assistant.',
                'Audience: internal staff using the CRM panel.',
                'Your job: explain workflows, suggest next steps, clarify where things live in the CRM, and answer operational questions in a concise professional tone.',
                'Do not claim to have performed actions, changed data, sent emails, or edited records unless the user explicitly asks you to draft instructions only.',
                'If the answer depends on live data you do not have, say what to check inside the CRM.',
                'Prefer short answers with clear steps or bullets when helpful.',
                'If the question is outside CRM operations or company services, say so plainly and redirect to the relevant CRM area only if appropriate.',
                'If a role is mentioned, respect role boundaries and only suggest actions available to that role.',
                'Company, service, and CRM knowledge:' . "\n" . $this->knowledgeService->adminContextText(),
                'Current admin context:' . "\n"
                    . 'Role: ' . strtolower((string) $user->role) . "\n"
                    . 'Page title: ' . $this->cleanPageValue($pageContext['page_title'] ?? null) . "\n"
                    . 'Page heading: ' . $this->cleanPageValue($pageContext['page_heading'] ?? null) . "\n"
                    . 'Current path: ' . $this->cleanPageValue($pageContext['current_path'] ?? null),
            ]),
        ]];

        foreach ($this->recentMessages($conversation, 10) as $message) {
            $messages[] = [
                'role' => in_array($message->role, ['user', 'assistant'], true) ? $message->role : 'user',
                'content' => Str::limit(trim((string) $message->content), 2000, ''),
            ];
        }

        return $messages;
    }

    /**
     * @param array{page_title?:string,current_path?:string,page_heading?:string} $pageContext
     */
    private function fallbackReply(array $pageContext): string
    {
        $heading = $this->cleanPageValue($pageContext['page_heading'] ?? $pageContext['page_title'] ?? null);

        return implode("\n", array_filter([
            'I could not reach the assistant model right now.',
            $heading !== 'Not provided' ? 'You are currently on: ' . $heading . '.' : null,
            'Try a more specific question such as:',
            '- How do I update a project status?',
            '- Where can I check client invoices?',
            '- How does media delivery work for unpaid projects?',
        ]));
    }

    private function visitorId(User $user): string
    {
        return 'admin-user-' . $user->id;
    }

    private function cleanPageValue(mixed $value): string
    {
        $text = trim((string) $value);

        return $text !== '' ? Str::limit($text, 180, '') : 'Not provided';
    }
}
