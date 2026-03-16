from pathlib import Path
path = Path('app/Http/Controllers/DashboardController.php')
text = path.read_text(encoding='utf-8')

if 'RequestEditLog' not in text:
    text = text.replace('use App\\Models\\InboundEmail;\n', 'use App\\Models\\InboundEmail;\nuse App\\Models\\RequestEditLog;\n')

if 'logRequestEdit(' not in text:
    marker = 'private function resolvePortalClient'
    helper = """    private function logRequestEdit(string $type, int $requestId, ?int $clientId, ?\\App\\Models\\User $actor, array $changes): void
    {
        RequestEditLog::create([
            'request_type' => $type,
            'request_id' => $requestId,
            'client_id' => $clientId,
            'actor_user_id' => $actor?->id,
            'actor_role' => $actor?->role,
            'changes' => $changes,
        ]);
    }

"""
    text = text.replace(marker, helper + marker)

if 'ClientServiceRequest::create' in text and '$serviceRequest = ClientServiceRequest::create' not in text:
    text = text.replace('        ClientServiceRequest::create([\n', '        $serviceRequest = ClientServiceRequest::create([\n')

if '$serviceRequest = ClientServiceRequest::create' in text and "'action' => 'create'" not in text:
    text = text.replace("        $serviceRequest = ClientServiceRequest::create([\n            'client_id' => $client->id,\n            'client_project_id' => $projectId,\n            'requester_user_id' => $user->id,\n            'requested_service' => $validated['requested_service'],\n            'subject' => $validated['subject'] ?? null,\n            'details' => $validated['details'] ?? null,\n            'preferred_date' => $validated['preferred_date'] ?? null,\n            'status' => 'new',\n        ]);\n\n        $messagePrefix",
    "        $serviceRequest = ClientServiceRequest::create([\n            'client_id' => $client->id,\n            'client_project_id' => $projectId,\n            'requester_user_id' => $user->id,\n            'requested_service' => $validated['requested_service'],\n            'subject' => $validated['subject'] ?? null,\n            'details' => $validated['details'] ?? null,\n            'preferred_date' => $validated['preferred_date'] ?? null,\n            'status' => 'new',\n        ]);\n\n        $this->logRequestEdit('service', (int) $serviceRequest->id, $client->id, $user, [\n            'action' => 'create',\n            'snapshot' => $serviceRequest->only(['client_project_id', 'requested_service', 'subject', 'details', 'preferred_date', 'status']),\n        ]);\n\n        $messagePrefix")

if '$bookingRequest = BookingRequest::create' in text and "'action' => 'create'" not in text:
    text = text.replace("        $bookingRequest = BookingRequest::create([\n            'client_id' => $client->id,\n            'client_project_id' => $projectId,\n            'lead_profile_id' => $leadProfileId,\n            'requester_user_id' => $user->id,\n            'requested_service' => $validated['requested_service'],\n            'preferred_date' => $validated['preferred_date'] ?? null,\n            'preferred_time_window' => $validated['preferred_time_window'] ?? null,\n            'notes' => $validated['notes'] ?? null,\n            'status' => 'new',\n        ]);\n\n        $preferredBits = []",
    "        $bookingRequest = BookingRequest::create([\n            'client_id' => $client->id,\n            'client_project_id' => $projectId,\n            'lead_profile_id' => $leadProfileId,\n            'requester_user_id' => $user->id,\n            'requested_service' => $validated['requested_service'],\n            'preferred_date' => $validated['preferred_date'] ?? null,\n            'preferred_time_window' => $validated['preferred_time_window'] ?? null,\n            'notes' => $validated['notes'] ?? null,\n            'status' => 'new',\n        ]);\n\n        $this->logRequestEdit('booking', (int) $bookingRequest->id, $client->id, $user, [\n            'action' => 'create',\n            'snapshot' => $bookingRequest->only(['client_project_id', 'requested_service', 'preferred_date', 'preferred_time_window', 'notes', 'status']),\n        ]);\n\n        $preferredBits = []")

text = text.replace("        $validated = $request->validate([\n            'client_project_id' => ['nullable', 'integer'],\n            'requested_service' => ['required', 'string', 'max:160'],\n            'preferred_date' => ['nullable', 'date'],\n            'preferred_time_window' => ['nullable', 'string', 'max:80'],\n            'notes' => ['nullable', 'string', 'max:2000'],\n        ]);\n\n        $bookingRequest->client_project_id = $validated['client_project_id'] ?? null;",
"        $validated = $request->validate([\n            'client_project_id' => ['nullable', 'integer'],\n            'requested_service' => ['required', 'string', 'max:160'],\n            'preferred_date' => ['nullable', 'date'],\n            'preferred_time_window' => ['nullable', 'string', 'max:80'],\n            'notes' => ['nullable', 'string', 'max:2000'],\n        ]);\n\n        $before = $bookingRequest->only(['client_project_id', 'requested_service', 'preferred_date', 'preferred_time_window', 'notes']);\n\n        $bookingRequest->client_project_id = $validated['client_project_id'] ?? null;")

text = text.replace("        $bookingRequest->save();\n\n        return back()->with('status', 'Booking request updated.');",
"        $bookingRequest->save();\n\n        $this->logRequestEdit('booking', (int) $bookingRequest->id, $bookingRequest->client_id, $request->user(), [\n            'action' => 'update',\n            'before' => $before,\n            'after' => $bookingRequest->only(['client_project_id', 'requested_service', 'preferred_date', 'preferred_time_window', 'notes']),\n        ]);\n\n        return back()->with('status', 'Booking request updated.');")

text = text.replace("        $validated = $request->validate([\n            'client_project_id' => ['nullable', 'integer'],\n            'requested_service' => ['required', 'string', 'max:120'],\n            'subject' => ['nullable', 'string', 'max:255'],\n            'details' => ['nullable', 'string', 'max:2000'],\n            'preferred_date' => ['nullable', 'date'],\n        ]);\n\n        $serviceRequest->client_project_id = $validated['client_project_id'] ?? null;",
"        $validated = $request->validate([\n            'client_project_id' => ['nullable', 'integer'],\n            'requested_service' => ['required', 'string', 'max:120'],\n            'subject' => ['nullable', 'string', 'max:255'],\n            'details' => ['nullable', 'string', 'max:2000'],\n            'preferred_date' => ['nullable', 'date'],\n        ]);\n\n        $before = $serviceRequest->only(['client_project_id', 'requested_service', 'subject', 'details', 'preferred_date']);\n\n        $serviceRequest->client_project_id = $validated['client_project_id'] ?? null;")

text = text.replace("        $serviceRequest->save();\n\n        return back()->with('status', 'Service request updated.');",
"        $serviceRequest->save();\n\n        $this->logRequestEdit('service', (int) $serviceRequest->id, $serviceRequest->client_id, $request->user(), [\n            'action' => 'update',\n            'before' => $before,\n            'after' => $serviceRequest->only(['client_project_id', 'requested_service', 'subject', 'details', 'preferred_date']),\n        ]);\n\n        return back()->with('status', 'Service request updated.');")

text = text.replace("        $validated = $request->validate([\n            'client_project_id' => ['required', 'integer'],\n            'requested_service' => ['required', 'string', 'max:120'],\n            'subject' => ['nullable', 'string', 'max:255'],\n            'details' => ['nullable', 'string', 'max:2000'],\n            'preferred_date' => ['nullable', 'date'],\n        ]);\n\n        $serviceRequest->client_project_id = $validated['client_project_id'];",
"        $validated = $request->validate([\n            'client_project_id' => ['required', 'integer'],\n            'requested_service' => ['required', 'string', 'max:120'],\n            'subject' => ['nullable', 'string', 'max:255'],\n            'details' => ['nullable', 'string', 'max:2000'],\n            'preferred_date' => ['nullable', 'date'],\n        ]);\n\n        $before = $serviceRequest->only(['client_project_id', 'requested_service', 'subject', 'details', 'preferred_date']);\n\n        $serviceRequest->client_project_id = $validated['client_project_id'];")

text = text.replace("        $serviceRequest->save();\n\n        return back()->with('status', 'Service request updated.');",
"        $serviceRequest->save();\n\n        $this->logRequestEdit('service', (int) $serviceRequest->id, $serviceRequest->client_id, $request->user(), [\n            'action' => 'update',\n            'before' => $before,\n            'after' => $serviceRequest->only(['client_project_id', 'requested_service', 'subject', 'details', 'preferred_date']),\n        ]);\n\n        return back()->with('status', 'Service request updated.');")

text = text.replace("        $validated = $request->validate([\n            'requested_service' => ['required', 'string', 'max:160'],\n            'preferred_date' => ['nullable', 'date'],\n            'preferred_time_window' => ['nullable', 'string', 'max:80'],\n            'notes' => ['nullable', 'string', 'max:2000'],\n        ]);\n\n        $bookingRequest->requested_service = $validated['requested_service'];",
"        $validated = $request->validate([\n            'requested_service' => ['required', 'string', 'max:160'],\n            'preferred_date' => ['nullable', 'date'],\n            'preferred_time_window' => ['nullable', 'string', 'max:80'],\n            'notes' => ['nullable', 'string', 'max:2000'],\n        ]);\n\n        $before = $bookingRequest->only(['requested_service', 'preferred_date', 'preferred_time_window', 'notes']);\n\n        $bookingRequest->requested_service = $validated['requested_service'];")

text = text.replace("        $bookingRequest->save();\n\n        return back()->with('status', 'Booking request updated.');",
"        $bookingRequest->save();\n\n        $this->logRequestEdit('booking', (int) $bookingRequest->id, $bookingRequest->client_id, $request->user(), [\n            'action' => 'update',\n            'before' => $before,\n            'after' => $bookingRequest->only(['requested_service', 'preferred_date', 'preferred_time_window', 'notes']),\n        ]);\n\n        return back()->with('status', 'Booking request updated.');")

text = text.replace("        $bookingRequest->delete();\n\n        return back()->with('status', 'Booking request deleted.');",
"        $this->logRequestEdit('booking', (int) $bookingRequest->id, $bookingRequest->client_id, $request->user(), [\n            'action' => 'delete',\n            'snapshot' => $bookingRequest->only(['client_project_id', 'requested_service', 'preferred_date', 'preferred_time_window', 'notes', 'status']),\n        ]);\n\n        $bookingRequest->delete();\n\n        return back()->with('status', 'Booking request deleted.');")

text = text.replace("        $serviceRequest->delete();\n\n        return back()->with('status', 'Service request deleted.');",
"        $this->logRequestEdit('service', (int) $serviceRequest->id, $serviceRequest->client_id, $request->user(), [\n            'action' => 'delete',\n            'snapshot' => $serviceRequest->only(['client_project_id', 'requested_service', 'subject', 'details', 'preferred_date', 'status']),\n        ]);\n\n        $serviceRequest->delete();\n\n        return back()->with('status', 'Service request deleted.');")

path.write_text(text, encoding='utf-8')
