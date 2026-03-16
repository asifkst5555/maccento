@extends('layouts.panel', [
  'title' => 'Service Requests',
  'heading' => 'Service Requests',
  'subheading' => 'Request additional services on your active projects.',
])

@section('content')
<div class="corp-admin-shell panel-stack">
  <section class="panel-grid panel-grid-kpi">
    <article class="panel-card">
      <span class="panel-kpi-label">Active Projects</span>
      <p class="panel-kpi-value">{{ $portalStats['active_projects'] }}</p>
    </article>
    <article class="panel-card">
      <span class="panel-kpi-label">Deliveries Ready</span>
      <p class="panel-kpi-value">{{ $portalStats['deliveries_ready'] }}</p>
    </article>
    <article class="panel-card">
      <span class="panel-kpi-label">Portal Messages</span>
      <p class="panel-kpi-value">{{ $portalStats['message_count'] }}</p>
    </article>
  </section>

  <section class="panel-card panel-stack">
    <div class="panel-form-row" style="justify-content: space-between; align-items: center;">
      <div>
        <h2 class="panel-section-title" style="margin: 0;">Request Service</h2>
        <p class="panel-muted">Choose an active project and tell us what you need.</p>
      </div>
    </div>
    <form method="post" action="{{ route('user.requests.store') }}" class="panel-stack">
      @csrf
      <input type="hidden" name="request_type" value="service">
      @if($projects->isEmpty())
        <p class="panel-muted">No active projects are linked yet. Start with a booking request first.</p>
      @else
        <select class="panel-select" name="client_project_id" required>
          <option value="">Select a project</option>
          @foreach($projects as $project)
            <option value="{{ $project->id }}">{{ $project->title }}</option>
          @endforeach
        </select>
        <input class="panel-input" type="text" name="requested_service" placeholder="Requested service" required>
        <input class="panel-input" type="text" name="subject" placeholder="Short subject (optional)">
        <input class="panel-input" type="date" name="preferred_date">
        <textarea class="panel-textarea" name="details" placeholder="Describe the additional service"></textarea>
        <div class="panel-form-row" style="justify-content: flex-end;">
          <button class="panel-btn panel-btn-primary" type="submit">Submit Service Request</button>
        </div>
      @endif
    </form>
  </section>

  @if($editRequest)
  <section class="panel-card panel-stack" id="service-request-edit">
    <h2 class="panel-section-title" style="margin: 0;">Edit Service Request</h2>
    <form method="post" action="{{ route('user.service-requests.update', $editRequest) }}" class="panel-stack">
      @csrf
      <select class="panel-select" name="client_project_id" required>
        <option value="">Select a project</option>
        @foreach($projects as $project)
          <option value="{{ $project->id }}" @selected((int) $editRequest->client_project_id === (int) $project->id)>{{ $project->title }}</option>
        @endforeach
      </select>
      <input class="panel-input" type="text" name="requested_service" value="{{ $editRequest->requested_service }}" required>
      <input class="panel-input" type="text" name="subject" value="{{ $editRequest->subject }}" placeholder="Short subject (optional)">
      <input class="panel-input" type="date" name="preferred_date" value="{{ $editRequest->preferred_date?->format('Y-m-d') }}">
      <textarea class="panel-textarea" name="details" placeholder="Describe the additional service">{{ $editRequest->details }}</textarea>
      <div class="panel-form-row" style="justify-content: flex-end;">
        <a class="panel-btn" href="{{ route('user.service-requests.index') }}">Cancel</a>
        <button class="panel-btn panel-btn-primary" type="submit">Save Changes</button>
      </div>
    </form>
  </section>
  @endif

  <section class="panel-card panel-stack">
    <h2 class="panel-section-title" style="margin: 0;">Service Request History</h2>
    <div class="panel-table-wrap">
      <table class="panel-table">
        <thead>
          <tr>
            <th>Project</th>
            <th>Service</th>
            <th>Status</th>
            <th>Submitted</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse($serviceRequests as $requestItem)
            <tr>
              <td data-label="Project">{{ $requestItem->project?->title ?: 'Project #' . $requestItem->client_project_id }}</td>
              <td data-label="Service">
                {{ $requestItem->requested_service }}
                @if(!blank($requestItem->details))
                  <div class="panel-muted">{{ \Illuminate\Support\Str::limit($requestItem->details, 90) }}</div>
                @endif
              </td>
              <td data-label="Status"><span class="panel-badge">{{ $requestItem->status }}</span></td>
              <td data-label="Submitted">{{ $requestItem->created_at?->format('Y-m-d H:i') ?: '-' }}</td>
              <td data-label="Action" style="text-align: right; white-space: nowrap;">
                <div class="panel-action-buttons panel-action-buttons-split" style="gap: 0.5rem; justify-content: flex-end;">
                  <form method="get" action="{{ route('user.service-requests.index') }}" style="margin: 0;">
                    <input type="hidden" name="edit" value="{{ $requestItem->id }}">
                    <button class="panel-btn" type="submit">Edit</button>
                  </form>
                  <form method="post" action="{{ route('user.service-requests.delete', $requestItem) }}" data-confirm="Delete this service request?">
                    @csrf
                    <button class="panel-btn panel-btn-danger" type="submit">Delete</button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="5" class="panel-muted">No service requests yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <x-panel-pagination :paginator="$serviceRequests" />
  </section>
</div>
@endsection











