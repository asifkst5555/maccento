@extends('layouts.panel', [
  'title' => 'Booking Requests',
  'heading' => 'Booking Requests',
  'subheading' => 'Track incoming booking requests and confirm schedules.',
])

@section('content')
<div class="corp-admin-shell panel-stack">
  <section class="panel-card panel-stack">
    <div class="panel-form-row" style="justify-content: space-between; align-items: center;">
      <div>
        <span class="panel-badge">Scheduling</span>
        <h2 class="panel-section-title" style="margin-top: 12px;">Booking Requests</h2>
        <p class="panel-muted">Review client booking requests, propose schedules, and confirm projects.</p>
      </div>
      <form method="get" action="{{ route('admin.booking-requests.index') }}" class="panel-form-row" style="margin-bottom: 0;">
        <select class="panel-select" name="status">
          <option value="">All statuses</option>
          @foreach($statusOptions as $status)
            <option value="{{ $status }}" @selected($statusFilter === $status)>{{ ucfirst($status) }}</option>
          @endforeach
        </select>
        <input class="panel-input" type="text" name="search" placeholder="Search client, service, or project" value="{{ $search }}">
        <button class="panel-btn panel-btn-primary" type="submit">Filter</button>
        @if($statusFilter !== '' || $search !== '')
          <a class="panel-btn" href="{{ route('admin.booking-requests.index') }}">Clear</a>
        @endif
      </form>
    </div>
  </section>

  @if($editRequest)
  <section class="panel-card panel-stack" id="booking-request-edit">
    <h2 class="panel-section-title" style="margin: 0;">Edit Booking Request</h2>
    <form method="post" action="{{ route('admin.booking-requests.update', $editRequest) }}" class="panel-stack">
      @csrf
      <input class="panel-input" type="text" name="requested_service" value="{{ $editRequest->requested_service }}" required>
      <input class="panel-input" type="date" name="preferred_date" value="{{ $editRequest->preferred_date?->format('Y-m-d') }}">
      <select class="panel-select" name="preferred_time_window">
        <option value="">Preferred time window (optional)</option>
        @foreach(['Morning (8-12)', 'Midday (12-3)', 'Afternoon (3-6)', 'Evening (6-8)', 'Any time'] as $slot)
          <option value="{{ $slot }}" @selected($editRequest->preferred_time_window === $slot)>{{ $slot }}</option>
        @endforeach
      </select>
      <textarea class="panel-textarea" name="notes" placeholder="Notes">{{ $editRequest->notes }}</textarea>
      <div class="panel-form-row" style="justify-content: flex-end;">
        <a class="panel-btn" href="{{ route('admin.booking-requests.index') }}">Cancel</a>
        <button class="panel-btn panel-btn-primary" type="submit">Save Changes</button>
      </div>
    </form>
  </section>
  @endif

  <section class="panel-card panel-stack">
    <div class="panel-table-wrap">
      <table class="panel-table">
        <thead>
          <tr>
            <th>Client</th>
            <th>Project</th>
            <th>Service</th>
            <th>Preferred</th>
            <th>Status</th>
            <th>Submitted</th>
            <th>Update</th>
          </tr>
        </thead>
        <tbody>
          @forelse($bookingRequests as $requestItem)
            <tr>
              <td data-label="Client">
                {{ $requestItem->client?->name ?: 'Client #' . $requestItem->client_id }}
                <div class="panel-muted">{{ $requestItem->client?->email ?: 'Email unavailable' }}</div>
              </td>
              <td data-label="Project">
                @if($requestItem->project)
                  {{ $requestItem->project->title }}
                  <div class="panel-muted">Scheduled: {{ $requestItem->project->scheduled_at?->format('Y-m-d H:i') ?: '-' }}</div>
                @else
                  -
                @endif
              </td>
              <td data-label="Service">
                {{ $requestItem->requested_service }}
                @if(!blank($requestItem->notes))
                  <div class="panel-muted">{{ \Illuminate\Support\Str::limit($requestItem->notes, 90) }}</div>
                @endif
              </td>
              <td data-label="Preferred">
                {{ $requestItem->preferred_date?->format('Y-m-d') ?: '-' }}
                @if(!blank($requestItem->preferred_time_window))
                  <div class="panel-muted">{{ $requestItem->preferred_time_window }}</div>
                @endif
              </td>
              <td data-label="Status"><span class="panel-badge">{{ $requestItem->status }}</span></td>
              <td data-label="Submitted">{{ $requestItem->created_at?->format('Y-m-d H:i') ?: '-' }}</td>
              <td data-label="Update" style="text-align: right; white-space: nowrap;">
                <form method="post" action="{{ route('admin.booking-requests.status', $requestItem) }}" class="panel-stack" style="gap: 0.5rem;">
                  @csrf
                  <select class="panel-select" name="status">
                    @foreach($statusOptions as $status)
                      <option value="{{ $status }}" @selected($requestItem->status === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                  </select>
                  <input class="panel-input" type="datetime-local" name="scheduled_at" value="{{ $requestItem->project?->scheduled_at?->format('Y-m-d\\TH:i') }}">
                  <input class="panel-input" type="text" name="admin_note" placeholder="Optional note">
                  <button class="panel-btn panel-btn-primary" type="submit">Update</button>
                </form>
                <div class="panel-action-buttons panel-action-buttons-split" style="gap: 0.5rem; margin-top: 0.5rem; justify-content: flex-end;">
                  <form method="get" action="{{ route('admin.booking-requests.index') }}" style="margin: 0;">
                    <input type="hidden" name="edit" value="{{ $requestItem->id }}">
                    <button class="panel-btn" type="submit">Edit</button>
                  </form>
                  <form method="post" action="{{ route('admin.booking-requests.delete', $requestItem) }}" data-confirm="Delete this booking request?">
                    @csrf
                    <button class="panel-btn panel-btn-danger" type="submit">Delete</button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="7" class="panel-muted">No booking requests found.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div>
      {{ $bookingRequests->links() }}
    </div>
  </section>
</div>
@endsection










