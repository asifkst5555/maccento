<?php

namespace App\Http\Controllers;

use App\Services\AdminAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminAssistantController extends Controller
{
    public function __construct(
        private readonly AdminAssistantService $assistantService,
    ) {
    }

    public function session(Request $request): JsonResponse
    {
        $conversation = $this->assistantService->sessionForUser($request->user());

        return response()->json([
            'ok' => true,
            'conversation_id' => $conversation->id,
            'messages' => $this->assistantService->recentMessages($conversation)->map(static function ($message): array {
                return [
                    'id' => $message->id,
                    'role' => $message->role,
                    'content' => (string) $message->content,
                    'created_at' => $message->created_at?->toIso8601String(),
                ];
            })->values()->all(),
        ]);
    }

    public function reset(Request $request): JsonResponse
    {
        $conversation = $this->assistantService->resetSession($request->user());

        return response()->json([
            'ok' => true,
            'conversation_id' => (string) $conversation->id,
            'messages' => $this->assistantService->recentMessages($conversation)->map(static function ($message): array {
                return [
                    'id' => $message->id,
                    'role' => $message->role,
                    'content' => (string) $message->content,
                    'created_at' => $message->created_at?->toIso8601String(),
                ];
            })->values()->all(),
        ]);
    }

    public function message(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'conversation_id' => ['nullable', 'uuid'],
            'message' => ['required', 'string', 'max:1500'],
            'page_title' => ['nullable', 'string', 'max:180'],
            'page_heading' => ['nullable', 'string', 'max:180'],
            'current_path' => ['nullable', 'string', 'max:255'],
        ]);

        $result = $this->assistantService->reply(
            $request->user(),
            (string) $validated['message'],
            [
                'page_title' => (string) ($validated['page_title'] ?? ''),
                'page_heading' => (string) ($validated['page_heading'] ?? ''),
                'current_path' => (string) ($validated['current_path'] ?? ''),
            ],
            $validated['conversation_id'] ?? null,
        );

        return response()->json([
            'ok' => true,
            'conversation_id' => $result['conversation']->id,
            'message' => [
                'id' => $result['assistant_message']->id,
                'role' => $result['assistant_message']->role,
                'content' => (string) $result['assistant_message']->content,
                'created_at' => $result['assistant_message']->created_at?->toIso8601String(),
            ],
        ]);
    }
}
