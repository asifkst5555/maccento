@extends('layouts.panel', [
  'title' => 'Booking Requests',
  'heading' => 'Booking Requests',
  'subheading' => 'Request a new project or session booking.',
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
        <h2 class="panel-section-title" style="margin: 0;">Request Booking</h2>
        <p class="panel-muted">Tell us about the new project you want to start.</p>
      </div>
    </div>
    <form method="post" action="{{ route('user.requests.store') }}" class="panel-stack">
      @csrf
      <input type="hidden" name="request_type" value="booking">
      <input class="panel-input" type="text" name="requested_service" placeholder="Project or service request" required>
      <input class="panel-input" type="date" name="preferred_date">
      <select class="panel-select" name="preferred_time_window">
        <option value="">Preferred time window (optional)</option>
        <option value="Morning (8-12)">Morning (8-12)</option>
        <option value="Midday (12-3)">Midday (12-3)</option>
        <option value="Afternoon (3-6)">Afternoon (3-6)</option>
        <option value="Evening (6-8)">Evening (6-8)</option>
        <option value="Any time">Any time</option>
      </select>
      <textarea class="panel-textarea" name="notes" placeholder="Share location, scope, or priorities"></textarea>
      <div class="panel-form-row" style="justify-content: flex-end;">
        <button class="panel-btn panel-btn-primary" type="submit">Submit Booking Request</button>
      </div>
    </form>
  </section>

  @if($editRequest)
  <section class="panel-card panel-stack" id="booking-request-edit">
    <h2 class="panel-section-title" style="margin: 0;">Edit Booking Request</h2>
    <form method="post" action="{{ route('user.booking-requests.update', $editRequest) }}" class="panel-stack">
      @csrf
      <input class="panel-input" type="text" name="requested_service" value="{{ $editRequest->requested_service }}" required>
      <input class="panel-input" type="date" name="preferred_date" value="{{ $editRequest->preferred_date?->format('Y-m-d') }}">
      <select class="panel-select" name="preferred_time_window">
        <option value="">Preferred time window (optional)</option>
        @foreach(['Morning (8-12)', 'Midday (12-3)', 'Afternoon (3-6)', 'Evening (6-8)', 'Any time'] as $slot)
          <option value="{{ $slot }}" @selected($editRequest->preferred_time_window === $slot)>{{ $slot }}</option>
        @endforeach
      </select>
      <textarea class="panel-textarea" name="notes" placeholder="Share location, scope, or priorities">{{ $editRequest->notes }}</textarea>
      <div class="panel-form-row" style="justify-content: flex-end;">
        <a class="panel-btn" href="{{ route('user.booking-requests.index') }}">Cancel</a>
        <button class="panel-btn panel-btn-primary" type="submit">Save Changes</button>
      </div>
    </form>
  </section>
  @endif

  <section class="panel-card panel-stack">
    <h2 class="panel-section-title" style="margin: 0;">Booking Request History</h2>
    <div class="panel-table-wrap">
      <table class="panel-table">
        <thead>
          <tr>
            <th>Service</th>
            <th>Preferred</th>
            <th>Status</th>
            <th>Submitted</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse($bookingRequests as $requestItem)
            <tr>
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
              <td data-label="Action" style="text-align: right; white-space: nowrap;">
                <div class="panel-action-buttons panel-action-buttons-split" style="gap: 0.5rem; justify-content: flex-end;">
                  <form method="get" action="{{ route('user.booking-requests.index') }}" style="margin: 0;">
                    <input type="hidden" name="edit" value="{{ $requestItem->id }}">
                    <button class="panel-btn" type="submit">Edit</button>
                  </form>
                  <form method="post" action="{{ route('user.booking-requests.delete', $requestItem) }}" data-confirm="Delete this booking request?">
                    @csrf
                    <button class="panel-btn panel-btn-danger" type="submit">Delete</button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="5" class="panel-muted">No booking requests yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <x-panel-pagination :paginator="$bookingRequests" />
  </section>
</div>
@endsection










