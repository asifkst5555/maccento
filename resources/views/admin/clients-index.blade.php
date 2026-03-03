@extends('layouts.panel', [
  'title' => 'Client Management',
  'heading' => 'Client Management',
  'subheading' => 'Create clients, track requests, and open full client workspace.',
])

@section('content')
<section class="panel-card">
  <h2 class="panel-section-title">Add Client</h2>
  <form method="post" action="{{ route('admin.clients.store') }}" class="panel-form-row">
    @csrf
    <input class="panel-input" type="text" name="name" placeholder="Client name" required>
    <input class="panel-input" type="email" name="email" placeholder="Email (used for login)" required>
    <input class="panel-input" type="text" name="password" placeholder="Login password (min 8 chars)" required>
    <input class="panel-input" type="text" name="phone" placeholder="Phone">
    <select class="panel-select" name="role" required>
      <option value="client">Client</option>
      <option value="agent">Agent</option>
    </select>
    <input class="panel-input" type="text" name="company" placeholder="Company/Team">
    <select class="panel-select" name="status" required>
      @foreach(['active' => 'Active', 'vip' => 'VIP', 'inactive' => 'Inactive'] as $value => $label)
      <option value="{{ $value }}">{{ $label }}</option>
      @endforeach
    </select>
    <button class="panel-btn panel-btn-primary" type="submit">Create Client</button>
    <textarea class="panel-textarea" name="notes" placeholder="Client notes"></textarea>
  </form>
</section>

<section class="panel-card">
  <h2 class="panel-section-title">Clients</h2>
  <form method="get" class="panel-form-row">
    <input class="panel-input" type="text" name="search" value="{{ $filters['search'] }}" placeholder="Search client/email/company">
    <select class="panel-select" name="status">
      <option value="">All status</option>
      @foreach(['active' => 'Active', 'vip' => 'VIP', 'inactive' => 'Inactive'] as $value => $label)
      <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
      @endforeach
    </select>
    <button class="panel-btn panel-btn-primary" type="submit">Filter</button>
    <a class="panel-link" href="{{ route('admin.clients.index') }}">Clear</a>
  </form>
  <div class="panel-table-wrap">
    <table class="panel-table">
      <thead><tr><th>ID</th><th>Name</th><th>Contact</th><th>Status</th><th>Projects</th><th>Invoices</th><th>Requests</th><th>Action</th></tr></thead>
      <tbody>
        @forelse($clients as $client)
        <tr>
          <td>#{{ $client->id }}</td>
          <td>{{ $client->name }}</td>
          <td>{{ $client->email ?: ($client->phone ?: '-') }}</td>
          <td><span class="panel-badge">{{ $client->status }}</span></td>
          <td>{{ $client->projects_count }}</td>
          <td>{{ $client->invoices_count }}</td>
          <td>{{ $client->service_requests_count }}</td>
          <td>
            <a class="panel-link" href="{{ route('admin.clients.show', $client) }}">Open</a>
            <form method="post" action="{{ route('admin.clients.delete', $client) }}" style="display:inline-block; margin-left:8px;" onsubmit="return confirm('Delete this client? This will remove related projects, invoices, messages, and requests.');">
              @csrf
              <button class="panel-btn panel-btn-danger" type="submit">Delete</button>
            </form>
          </td>
        </tr>
        @empty
        <tr><td colspan="8" class="panel-muted">No clients yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <x-panel-pagination :paginator="$clients" />
</section>

<section class="panel-card">
  <h2 class="panel-section-title">Recent Service Requests (Old Clients)</h2>
  <div class="panel-table-wrap">
    <table class="panel-table">
      <thead><tr><th>Client</th><th>Service</th><th>Preferred Date</th><th>Status</th><th>Action</th></tr></thead>
      <tbody>
        @forelse($recentRequests as $requestItem)
        <tr>
          <td>{{ $requestItem->client?->name ?: ('Client #' . $requestItem->client_id) }}</td>
          <td>{{ $requestItem->requested_service }}</td>
          <td>{{ $requestItem->preferred_date?->format('Y-m-d') ?: '-' }}</td>
          <td><span class="panel-badge">{{ $requestItem->status }}</span></td>
          <td>
            <form method="post" action="{{ route('admin.service-requests.status', $requestItem) }}" class="panel-form-row">
              @csrf
              <select class="panel-select" name="status">
                @foreach(['new','accepted','in_progress','completed','closed'] as $status)
                <option value="{{ $status }}" @selected($requestItem->status === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                @endforeach
              </select>
              <button class="panel-btn" type="submit">Update</button>
            </form>
          </td>
        </tr>
        @empty
        <tr><td colspan="5" class="panel-muted">No service requests yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</section>
@endsection
