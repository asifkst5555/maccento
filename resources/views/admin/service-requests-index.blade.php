@extends('layouts.panel', [
  'title' => 'Service Requests',
  'heading' => 'Service Requests',
  'subheading' => 'Review client add-on requests and follow up.',
])

@section('content')
<div class="corp-admin-shell panel-stack">
  <section class="panel-card panel-stack">
    <div class="panel-form-row" style="justify-content: space-between; align-items: center;">
      <div>
        <span class="panel-badge">Requests</span>
        <h2 class="panel-section-title" style="margin-top: 12px;">Service Requests</h2>
        <p class="panel-muted">Track add-ons, revisions, and post-delivery requests in one place.</p>
      </div>
      <form method="get" action="{{ route('admin.service-requests.index') }}" class="panel-form-row" style="margin-bottom: 0;">
        <select class="panel-select" name="status">
          <option value="">All statuses</option>
          @foreach($statusOptions as $status)
            <option value="{{ $status }}" @selected($statusFilter === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
          @endforeach
        </select>
        <input class="panel-input" type="text" name="search" placeholder="Search client, service, or project" value="{{ $search }}">
        <button class="panel-btn panel-btn-primary" type="submit">Filter</button>
        @if($statusFilter !== '' || $search !== '')
          <a class="panel-btn" href="{{ route('admin.service-requests.index') }}">Clear</a>
        @endif
      </form>
    </div>
  </section>

  @if($editRequest)
  <section class="panel-card panel-stack" id="service-request-edit">
    <h2 class="panel-section-title" style="margin: 0;">Edit Service Request</h2>
    <form method="post" action="{{ route('admin.service-requests.update', $editRequest) }}" class="panel-stack">
      @csrf
      <input class="panel-input" type="text" name="requested_service" value="{{ $editRequest->requested_service }}" required>
      <input class="panel-input" type="text" name="subject" value="{{ $editRequest->subject }}" placeholder="Subject (optional)">
      <input class="panel-input" type="date" name="preferred_date" value="{{ $editRequest->preferred_date?->format('Y-m-d') }}">
      <textarea class="panel-textarea" name="details" placeholder="Details">{{ $editRequest->details }}</textarea>
      <div class="panel-form-row" style="justify-content: flex-end;">
        <a class="panel-btn" href="{{ route('admin.service-requests.index') }}">Cancel</a>
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
          @forelse($serviceRequests as $requestItem)
            <tr>
              <td data-label="Client">
                {{ $requestItem->client?->name ?: 'Client #' . $requestItem->client_id }}
                <div class="panel-muted">{{ $requestItem->client?->email ?: 'Email unavailable' }}</div>
              </td>
              <td data-label="Project">{{ $requestItem->project?->title ?: '-' }}</td>
              <td data-label="Service">
                {{ $requestItem->requested_service }}
                @if(!blank($requestItem->subject))
                  <div class="panel-muted">{{ $requestItem->subject }}</div>
                @endif
                @if(!blank($requestItem->details))
                  <div class="panel-muted">{{ \Illuminate\Support\Str::limit($requestItem->details, 90) }}</div>
                @endif
              </td>
              <td data-label="Preferred">{{ $requestItem->preferred_date?->format('Y-m-d') ?: '-' }}</td>
              <td data-label="Status"><span class="panel-badge">{{ $requestItem->status }}</span></td>
              <td data-label="Submitted">{{ $requestItem->created_at?->format('Y-m-d H:i') ?: '-' }}</td>
              <td data-label="Update" style="text-align: right; white-space: nowrap;">
                @if($canManagePipeline)
                <form method="post" action="{{ route('admin.service-requests.status', $requestItem) }}" class="panel-stack">
                  @csrf
                  <div class="panel-form-row">
                    <select class="panel-select" name="status">
                      @foreach($statusOptions as $status)
                        <option value="{{ $status }}" @selected($requestItem->status === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                      @endforeach
                    </select>
                    <label class="panel-muted inline-checkbox-label">
                      <input type="checkbox" name="create_invoice" value="1">
                      Create invoice
                    </label>
                  </div>
                  <div class="panel-form-row">
                    <input class="panel-input" type="number" step="0.01" min="0" name="invoice_amount" placeholder="Invoice amount">
                    <select class="panel-input" name="invoice_currency" data-select-flags="currency">
                      @foreach($currencyOptions ?? ['USD' => 'US Dollar'] as $code => $label)
                        <option value="{{ $code }}" @selected(($defaultCurrency ?? 'USD') === $code)>{{ $code }} - {{ $label }}</option>
                      @endforeach
                    </select>
                    <input class="panel-input" type="date" name="invoice_due_date">
                  </div>
                  <textarea class="panel-textarea" name="invoice_notes" placeholder="Invoice notes (optional)"></textarea>
                  <input class="panel-input" type="text" name="timeline_note" placeholder="Timeline note (optional)">
                  <button class="panel-btn panel-btn-primary" type="submit">Update</button>
                </form>
                <div class="panel-action-buttons panel-action-buttons-split" style="gap: 0.5rem; margin-top: 0.5rem; justify-content: flex-end;">
                  <form method="get" action="{{ route('admin.service-requests.index') }}" style="margin: 0;">
                    <input type="hidden" name="edit" value="{{ $requestItem->id }}">
                    <button class="panel-btn" type="submit">Edit</button>
                  </form>
                  <form method="post" action="{{ route('admin.service-requests.delete', $requestItem) }}" data-confirm="Delete this service request?">
                    @csrf
                    <button class="panel-btn panel-btn-danger" type="submit">Delete</button>
                  </form>
                </div>
                @else
                <span class="panel-muted">Read only</span>
                @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="7" class="panel-muted">No service requests found.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div>
      {{ $serviceRequests->links() }}
    </div>
  </section>
</div>
@endsection











