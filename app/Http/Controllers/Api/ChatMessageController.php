<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Services\ChatOrchestrator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatMessageController extends Controller
{
    public function store(Request $request, Conversation $conversation, ChatOrchestrator $chatOrchestrator): JsonResponse
    {
        $validated = $request->validate([
            'content' => ['required', 'string', 'max:' . config('ai.limits.max_input_chars', 2000)],
            'language' => ['nullable', 'in:en,fr'],
        ]);

        if ($conversation->status !== 'active') {
            return response()->json([
                'message' => 'Conversation is not active.',
            ], 422);
        }

        $result = $chatOrchestrator->handleUserMessage(
            $conversation,
            $validated['content'],
            $validated['language'] ?? null,
        );

        return response()->json([
            'conversation_id' => $conversation->id,
            'assistant' => [
                'id' => $result['assistant_message']->id,
                'content' => $result['assistant_message']->content,
                'metadata' => $result['assistant_message']->metadata,
                'created_at' => $result['assistant_message']->created_at,
            ],
            'lead' => [
                'id' => $result['lead']->id,
                'status' => $result['lead']->status,
                'score' => $result['lead']->score,
                'qualified_at' => $result['lead']->qualified_at,
            ],
            'completed' => $result['completed'],
            'missing_fields' => $result['missing_fields'],
        ]);
    }
}
