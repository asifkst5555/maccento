<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FollowUp;
use App\Models\LeadEvent;
use App\Models\LeadProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminLeadController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = LeadProfile::query()->with('conversation:id,status,started_at,last_message_at');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('service_type')) {
            $query->where('service_type', $request->string('service_type'));
        }
        if ($request->filled('min_score')) {
            $query->where('score', '>=', (int) $request->input('min_score'));
        }

        $leads = $query->latest('id')->paginate(20);

        return response()->json($leads);
    }

    public function show(LeadProfile $lead): JsonResponse
    {
        $lead->load([
            'events',
            'followUps',
            'conversation.messages:id,conversation_id,role,content,metadata,created_at',
        ]);

        return response()->json($lead);
    }

    public function updateStatus(Request $request, LeadProfile $lead): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:new,qualified,contacted,won,lost,nurturing'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $lead->status = $validated['status'];
        $lead->save();

        LeadEvent::create([
            'lead_profile_id' => $lead->id,
            'event_type' => 'status_updated',
            'payload' => ['status' => $lead->status, 'note' => $validated['note'] ?? null],
            'created_by' => $request->user()?->id,
        ]);

        return response()->json([
            'id' => $lead->id,
            'status' => $lead->status,
        ]);
    }

    public function scheduleFollowUp(Request $request, LeadProfile $lead): JsonResponse
    {
        $validated = $request->validate([
            'method' => ['required', 'in:call,email,sms'],
            'due_at' => ['required', 'date'],
            'owner_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $followUp = FollowUp::create([
            'lead_profile_id' => $lead->id,
            'owner_user_id' => $validated['owner_user_id'] ?? $request->user()?->id,
            'method' => $validated['method'],
            'due_at' => $validated['due_at'],
            'status' => 'pending',
            'result_notes' => $validated['notes'] ?? null,
        ]);

        LeadEvent::create([
            'lead_profile_id' => $lead->id,
            'event_type' => 'follow_up_scheduled',
            'payload' => ['follow_up_id' => $followUp->id, 'due_at' => $followUp->due_at],
            'created_by' => $request->user()?->id,
        ]);

        return response()->json($followUp, 201);
    }
}
