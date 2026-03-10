@extends('layouts.panel', [
  'title' => 'Client Management',
  'heading' => 'Client Management',
  'subheading' => 'Create clients, track requests, and open full client workspace.',
])

@section('content')
<style>
  .clients-admin-shell {
    --clients-ink: #10233a;
    --clients-muted: #5b6c84;
    --clients-line: #d8e1ec;
    --clients-surface: #ffffff;
    --clients-soft: #f5f8fc;
    --clients-shadow: 0 14px 30px rgba(16, 35, 58, 0.07);
    display: grid;
    gap: 1rem;
  }

  .clients-admin-shell .panel-card {
    border: 1px solid var(--clients-line);
    border-radius: 16px;
    background: var(--clients-surface);
    box-shadow: var(--clients-shadow);
  }

  .clients-admin-shell .panel-section-title {
    color: var(--clients-ink);
    margin-bottom: 0.9rem;
  }

  .clients-admin-shell .panel-muted,
  .clients-admin-shell .section-kicker {
    color: var(--clients-muted);
  }

  .clients-admin-shell .panel-input,
  .clients-admin-shell .panel-select,
  .clients-admin-shell .panel-textarea {
    border-radius: 12px;
    border: 1px solid #cad7e6;
    background-color: #fff;
  }

  .clients-admin-shell .panel-btn {
    border-radius: 12px;
  }

  .clients-form-shell,
  .clients-table-shell {
    padding: 1rem;
  }

  .clients-form-shell .section-kicker,
  .clients-table-shell .section-kicker {
    display: block;
    margin: -0.3rem 0 0.9rem;
    font-size: 0.94rem;
  }

  .clients-form-grid {
    display: grid;
    gap: 0.85rem;
  }

  .clients-form-grid .panel-form-row,
  .clients-table-shell .panel-form-row {
    gap: 0.85rem;
    margin-bottom: 0;
  }

  .clients-form-grid .panel-form-row > * {
    flex: 1 1 220px;
    min-width: 0;
  }

  .clients-form-grid .panel-textarea {
    min-height: 86px;
  }

  .clients-form-actions {
    display: flex;
    justify-content: flex-end;
  }

  .clients-filters {
    display: grid;
    gap: 0.85rem;
    margin-bottom: 1rem;
  }

  .clients-filters .panel-form-row > .panel-input {
    flex: 1 1 420px;
  }

  .clients-filters .panel-form-row > .panel-select {
    flex: 0 0 220px;
  }

  .clients-filters .panel-form-row > .panel-btn,
  .clients-filters .panel-form-row > .panel-link {
    flex: 0 0 auto;
  }

  .clients-admin-shell .panel-table thead th {
    letter-spacing: 0.04em;
  }

  .clients-admin-shell .panel-table td {
    padding-top: 14px;
    padding-bottom: 14px;
  }

  .clients-action-cell {
    white-space: nowrap;
  }

  .clients-action-cell .panel-link,
  .clients-action-cell form {
    display: inline-flex;
    vertical-align: middle;
  }

  .clients-action-cell form {
    margin-left: 8px;
  }

  .clients-request-form {
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0;
  }

  .clients-request-form .panel-select {
    flex: 1 1 240px;
  }

  .clients-request-form .panel-btn {
    flex: 0 0 auto;
  }

  @media (max-width: 900px) {
    .clients-form-actions {
      justify-content: stretch;
    }

    .clients-form-actions .panel-btn {
      width: 100%;
    }
  }

  @media (max-width: 640px) {
    .clients-form-shell,
    .clients-table-shell {
      padding: 0.85rem;
    }

    .clients-form-grid,
    .clients-filters {
      gap: 0.75rem;
    }

    .clients-form-grid .panel-form-row,
    .clients-table-shell .panel-form-row,
    .clients-request-form {
      align-items: stretch;
    }

    .clients-filters .panel-form-row > .panel-input,
    .clients-filters .panel-form-row > .panel-select,
    .clients-filters .panel-form-row > .panel-btn,
    .clients-filters .panel-form-row > .panel-link,
    .clients-request-form .panel-select,
    .clients-request-form .panel-btn {
      flex: 1 1 100%;
      width: 100%;
    }

    .clients-action-cell {
      white-space: normal;
    }

    .clients-action-cell .panel-link,
    .clients-action-cell form {
      display: flex;
      width: 100%;
      margin-left: 0;
    }

    .clients-action-cell .panel-link {
      justify-content: center;
    }

    .clients-action-cell form .panel-btn {
      width: 100%;
      justify-content: center;
    }
  }
</style>

<div class="clients-admin-shell">
@php
  $canDeleteClients = in_array(strtolower(trim((string) auth()->user()?->role)), ['owner', 'admin'], true);
@endphp
<section class="panel-card clients-form-shell">
  <h2 class="panel-section-title">Add Client</h2>
  <span class="section-kicker">Create a clean client record with login, contact details, and notes.</span>
  <form method="post" action="{{ route('admin.clients.store') }}" class="clients-form-grid">
    @csrf
    <div class="panel-form-row">
      <input class="panel-input" type="text" name="name" placeholder="Client name" required>
      <input class="panel-input" type="email" name="email" placeholder="Email (used for login)" required>
      <input class="panel-input" type="text" name="password" placeholder="Login password (min 8 chars)" required>
    </div>
    <div class="panel-form-row">
      <input class="panel-input" type="text" name="phone" placeholder="Phone">
      <select class="panel-select" name="role" required>
        <option value="client">Client</option>
        
      </select>
      <input class="panel-input" type="text" name="company" placeholder="Company/Team">
      <select class="panel-select" name="status" required>
        @foreach(['active' => 'Active', 'vip' => 'VIP', 'inactive' => 'Inactive'] as $value => $label)
        <option value="{{ $value }}">{{ $label }}</option>
        @endforeach
      </select>
    </div>
    <textarea class="panel-textarea" name="notes" placeholder="Client notes"></textarea>
    <div class="clients-form-actions">
      <button class="panel-btn panel-btn-primary" type="submit">Create Client</button>
    </div>
  </form>
</section>

<section class="panel-card clients-table-shell">
  <h2 class="panel-section-title">Clients</h2>
  <span class="section-kicker">Search and open client records without crowding the table header.</span>
  <form method="get" class="clients-filters">
    <div class="panel-form-row">
      <input class="panel-input" type="text" name="search" value="{{ $filters['search'] }}" placeholder="Search client/email/company">
      <select class="panel-select" name="status">
        <option value="">All status</option>
        @foreach(['active' => 'Active', 'vip' => 'VIP', 'inactive' => 'Inactive'] as $value => $label)
        <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
        @endforeach
      </select>
      <button class="panel-btn panel-btn-primary" type="submit">Filter</button>
      <a class="panel-link" href="{{ route('admin.clients.index') }}">Clear</a>
    </div>
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
          <td class="clients-action-cell">
            <a class="panel-link panel-btn-icon" href="{{ route('admin.clients.show', $client) }}" title="Open client" aria-label="Open client"><span class="panel-icon" aria-hidden="true"><svg viewBox="0 0 20 20"><path d="M4 10h12M10 4l6 6-6 6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span></a>
            @if($canDeleteClients)
            <form method="post" action="{{ route('admin.clients.delete', $client) }}" data-confirm="Delete this client? This will remove related projects, invoices, messages, and requests.">
              @csrf
              <button class="panel-btn panel-btn-danger panel-btn-icon" type="submit" title="Delete client" aria-label="Delete client"><span class="panel-icon-trash" aria-hidden="true"><x-panel-icon name="trash" /></span></button>
            </form>
            @endif
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

<section class="panel-card clients-table-shell">
  <h2 class="panel-section-title">Recent Service Requests (Old Clients)</h2>
  <span class="section-kicker">Keep request status updates readable and aligned with the client operations table.</span>
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
            <form method="post" action="{{ route('admin.service-requests.status', $requestItem) }}" class="panel-form-row clients-request-form">
              @csrf
              <select class="panel-select" name="status">
                @foreach(['new','accepted','in_progress','completed','closed'] as $status)
                <option value="{{ $status }}" @selected($requestItem->status === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                @endforeach
              </select>
              <button class="panel-btn panel-btn-primary" type="submit">Update</button>
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
</div>
@endsection



