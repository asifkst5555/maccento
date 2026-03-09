@extends('layouts.panel', [
  'title' => 'User Messages',
  'heading' => 'User Messages',
  'subheading' => 'Central client communication workspace for direct CRM messaging and timeline review.',
])

@section('content')
<style>
  .messages-admin-shell {
    --messages-ink: #10233a;
    --messages-muted: #5b6c84;
    --messages-line: #d8e1ec;
    --messages-surface: #ffffff;
    --messages-shadow: 0 14px 30px rgba(16, 35, 58, 0.07);
    display: grid;
    gap: 1rem;
  }

  .messages-admin-shell .panel-card {
    border: 1px solid var(--messages-line);
    border-radius: 16px;
    background: var(--messages-surface);
    box-shadow: var(--messages-shadow);
  }

  .messages-admin-shell .messages-form-shell,
  .messages-admin-shell .messages-table-shell {
    padding: 1rem;
  }

  .messages-admin-shell .section-kicker,
  .messages-admin-shell .panel-muted {
    color: var(--messages-muted);
  }

  .messages-admin-shell .section-kicker {
    display: block;
    margin: -0.3rem 0 0.9rem;
    font-size: 0.94rem;
  }

  .messages-admin-shell .panel-input,
  .messages-admin-shell .panel-select,
  .messages-admin-shell .panel-textarea,
  .messages-admin-shell .panel-btn {
    border-radius: 12px;
    background-color: #fff;
  }

  .messages-form-grid {
    display: grid;
    gap: 0.85rem;
  }

  .messages-form-grid .panel-form-row,
  .messages-filters .panel-form-row {
    gap: 0.85rem;
    margin-bottom: 0;
  }

  .messages-form-actions {
    display: flex;
    justify-content: flex-end;
  }

  .messages-filters {
    display: grid;
    gap: 0.85rem;
    margin-bottom: 1rem;
  }

  .messages-table-shell .panel-table td {
    vertical-align: top;
  }

  @media (max-width: 980px) {
    .messages-admin-layout {
      grid-template-columns: 1fr !important;
    }
  }
</style>

<div class="messages-admin-shell">
  <section class="panel-grid panel-grid-kpi-compact">
    <article class="client-portal-kpi">
      <span class="panel-kpi-label">Total Messages</span>
      <p class="client-portal-kpi-value">{{ number_format((int) ($messageStats['total_messages'] ?? 0)) }}</p>
    </article>
    <article class="client-portal-kpi">
      <span class="panel-kpi-label">Client Threads</span>
      <p class="client-portal-kpi-value">{{ number_format((int) ($messageStats['client_threads'] ?? 0)) }}</p>
    </article>
    <article class="client-portal-kpi">
      <span class="panel-kpi-label">Admin Sent</span>
      <p class="client-portal-kpi-value">{{ number_format((int) ($messageStats['admin_sent'] ?? 0)) }}</p>
    </article>
    <article class="client-portal-kpi">
      <span class="panel-kpi-label">Client Sent</span>
      <p class="client-portal-kpi-value">{{ number_format((int) ($messageStats['client_sent'] ?? 0)) }}</p>
    </article>
  </section>

  <section class="panel-grid messages-admin-layout" style="grid-template-columns: minmax(320px, 0.95fr) minmax(0, 1.45fr); align-items:start;">
    <article class="panel-card messages-form-shell">
      <h2 class="panel-section-title">Send Direct Message</h2>
      <span class="section-kicker">Write to a user directly from CRM with optional project context.</span>

      <form method="post" action="{{ route('admin.messages.store') }}" class="messages-form-grid">
        @csrf
        <div class="panel-form-row">
          <select class="panel-select" name="client_id" required>
            <option value="">Select client</option>
            @foreach($clients as $client)
              <option value="{{ $client->id }}" @selected((int) old('client_id', $filters['client_id'] ?? 0) === (int) $client->id)>{{ $client->name ?: ('Client #' . $client->id) }} @if($client->email) - {{ $client->email }} @endif</option>
            @endforeach
          </select>
          <select class="panel-select" name="client_project_id">
            <option value="">Project context (optional)</option>
            @foreach($clients as $client)
              @foreach($client->projects as $project)
                <option value="{{ $project->id }}" @selected((int) old('client_project_id', 0) === (int) $project->id)>{{ $client->name ?: ('Client #' . $client->id) }} - {{ $project->title }}</option>
              @endforeach
            @endforeach
          </select>
        </div>
        <textarea class="panel-textarea" name="message" placeholder="Write a direct message for the client workspace" required>{{ old('message') }}</textarea>
        <div class="messages-form-actions">
          <button class="panel-btn panel-btn-primary" type="submit">Send Message</button>
        </div>
      </form>
    </article>

    <article class="panel-card messages-form-shell">
      <h2 class="panel-section-title">Active Threads</h2>
      <span class="section-kicker">Recent client conversations across the CRM.</span>
      <div class="panel-stack">
        @forelse($latestThreads as $item)
          <article class="client-portal-list-row">
            <div class="client-portal-list-main">
              <h3 class="client-portal-title">{{ $item->client?->name ?: 'Unknown client' }}</h3>
              <p class="client-portal-meta">
                {{ strtoupper($item->sender_role) }}
                @if($item->project?->title)
                  &bull; {{ $item->project->title }}
                @endif
                @if($item->client?->email)
                  &bull; {{ $item->client->email }}
                @endif
              </p>
              <p class="panel-muted">{{ \Illuminate\Support\Str::limit($item->message, 140) }}</p>
            </div>
            <div class="client-portal-actions">
              @if($item->client)
                <a class="panel-btn" href="{{ route('admin.clients.show', ['client' => $item->client_id, 'project_id' => $item->client_project_id]) }}">Open Client</a>
              @endif
            </div>
          </article>
        @empty
          <p class="panel-muted">No client message threads yet.</p>
        @endforelse
      </div>
    </article>
  </section>

  <section class="panel-card messages-table-shell">
    <h2 class="panel-section-title">Message Timeline</h2>
    <div class="messages-filters">
      <form method="get" class="panel-form-row">
        <input class="panel-input" type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search client, project, or message text">
        <select class="panel-select" name="client_id">
          <option value="">All clients</option>
          @foreach($clients as $client)
            <option value="{{ $client->id }}" @selected((int) ($filters['client_id'] ?? 0) === (int) $client->id)>{{ $client->name ?: ('Client #' . $client->id) }}</option>
          @endforeach
        </select>
        <select class="panel-select" name="sender_role">
          <option value="">All senders</option>
          <option value="admin" @selected(($filters['sender_role'] ?? '') === 'admin')>Admin</option>
          <option value="client" @selected(($filters['sender_role'] ?? '') === 'client')>Client</option>
        </select>
        <button class="panel-btn panel-btn-primary" type="submit">Filter</button>
        <a class="panel-link" href="{{ route('admin.messages.index') }}">Clear</a>
      </form>
    </div>

    <div class="panel-table-wrap">
      <table class="panel-table">
        <thead>
          <tr>
            <th>Client</th>
            <th>Context</th>
            <th>Sender</th>
            <th>Message</th>
            <th>Sent</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse($messages as $message)
            <tr>
              <td>
                {{ $message->client?->name ?: 'Unknown client' }}
                @if($message->client?->email)
                  <div class="panel-muted">{{ $message->client->email }}</div>
                @endif
              </td>
              <td>
                @if($message->project)
                  {{ $message->project->title }}
                  <div class="panel-muted">{{ $message->project->status }}</div>
                @else
                  <span class="panel-badge">General message</span>
                @endif
              </td>
              <td>
                <span class="panel-badge @if($message->sender_role === 'admin') panel-badge-danger @endif">{{ strtoupper($message->sender_role) }}</span>
                @if($message->sender)
                  <div class="panel-muted">{{ $message->sender->name }}</div>
                @endif
              </td>
              <td>{{ \Illuminate\Support\Str::limit($message->message, 160) }}</td>
              <td>{{ $message->sent_at?->format('Y-m-d H:i') ?: $message->created_at?->format('Y-m-d H:i') }}</td>
              <td>
                @if($message->client)
                  <a class="panel-btn" href="{{ route('admin.clients.show', ['client' => $message->client_id, 'project_id' => $message->client_project_id]) }}">Open</a>
                @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="6" class="panel-muted">No client messages match the current filters.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <x-panel-pagination :paginator="$messages" />
  </section>
</div>
@endsection
