<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Services\LeadAutoCaptureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatSessionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'channel' => ['nullable', 'string', 'max:30'],
            'visitor_id' => ['nullable', 'string', 'max:100'],
            'language' => ['nullable', 'in:en,fr'],
        ]);

        $conversation = Conversation::create([
            'channel' => $validated['channel'] ?? 'web',
            'visitor_id' => $validated['visitor_id'] ?? null,
            'status' => 'active',
            'started_at' => now(),
            'last_message_at' => now(),
            'metadata' => [
                'ip' => $request->ip(),
                'user_id' => $request->user()?->id,
                'language' => $validated['language'] ?? 'en',
            ],
        ]);

        $userEmail = trim((string) ($request->user()?->email ?? ''));
        if ($userEmail !== '' && filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
            app(LeadAutoCaptureService::class)->captureAndWelcome([
                'name' => (string) ($request->user()?->name ?? ''),
                'email' => $userEmail,
                'phone' => (string) ($request->user()?->phone ?? ''),
            ], 'ai_chat_lead', $conversation);
        }

        return response()->json([
            'conversation_id' => $conversation->id,
            'status' => $conversation->status,
        ], 201);
    }

    public function close(Conversation $conversation): JsonResponse
    {
        $conversation->forceFill([
            'status' => 'closed',
            'closed_at' => now(),
        ])->save();

        return response()->json([
            'conversation_id' => $conversation->id,
            'status' => $conversation->status,
        ]);
    }
}
