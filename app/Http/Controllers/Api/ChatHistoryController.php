<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use Illuminate\Http\JsonResponse;

class ChatHistoryController extends Controller
{
    public function show(Conversation $conversation): JsonResponse
    {
        $conversation->load([
            'messages:id,conversation_id,role,content,metadata,created_at',
            'leadProfile',
        ]);

        return response()->json([
            'conversation' => [
                'id' => $conversation->id,
                'status' => $conversation->status,
                'channel' => $conversation->channel,
                'started_at' => $conversation->started_at,
            ],
            'messages' => $conversation->messages,
            'lead' => $conversation->leadProfile,
        ]);
    }
}
