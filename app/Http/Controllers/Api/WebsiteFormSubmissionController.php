<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WebsiteFormSubmission;
use App\Services\PanelNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebsiteFormSubmissionController extends Controller
{
    public function __construct(
        private readonly PanelNotificationService $panelNotificationService,
    ) {
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'company' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'service' => ['nullable', 'string', 'max:80'],
            'region' => ['nullable', 'string', 'max:80'],
            'message' => ['nullable', 'string', 'max:3000'],
            'page_url' => ['nullable', 'url', 'max:500'],
            'source' => ['nullable', 'string', 'max:60'],
        ]);

        if (blank($validated['email'] ?? null) && blank($validated['phone'] ?? null)) {
            return response()->json([
                'message' => 'Email or phone is required.',
                'errors' => [
                    'email' => ['Email or phone is required.'],
                ],
            ], 422);
        }

        $submission = WebsiteFormSubmission::create([
            'name' => $validated['name'],
            'company' => $validated['company'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'service' => $validated['service'] ?? null,
            'region' => $validated['region'] ?? null,
            'message' => $validated['message'] ?? null,
            'status' => 'new',
            'source' => $validated['source'] ?? 'website_contact_form',
            'page_url' => $validated['page_url'] ?? null,
            'ip_address' => $request->ip(),
            'submitted_at' => now(),
        ]);

        $this->panelNotificationService->notifyInternal(
            'website_form_submitted',
            'New website form submission',
            "{$submission->name} submitted a website inquiry.",
            route('admin.form-submissions.show', $submission),
            ['submission_id' => $submission->id]
        );

        return response()->json([
            'ok' => true,
            'message' => 'Form submitted successfully.',
        ]);
    }
}
