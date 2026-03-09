@extends('layouts.panel', [
  'title' => 'User Messages',
  'heading' => 'User Messages',
  'subheading' => 'Direct CRM messaging workspace for client conversations, updates, and follow-up communication.',
])

@section('content')
<style>
  .messages-chat-shell {
    --messages-bg: linear-gradient(180deg, #f7f9fc 0%, #eef3f8 100%);
    --messages-ink: #11263f;
    --messages-muted: #60738d;
    --messages-line: #d7e1ec;
    --messages-panel: #ffffff;
    --messages-panel-alt: #f8fbff;
    --messages-shadow: 0 18px 40px rgba(13, 31, 53, 0.08);
    --messages-danger: #bf1e2e;
    --messages-danger-dark: #971826;
    display: grid;
    gap: 1rem;
  }

  .messages-chat-shell .panel-card {
    border: 1px solid var(--messages-line);
    border-radius: 20px;
    background: var(--messages-panel);
    box-shadow: var(--messages-shadow);
  }

  .messages-chat-shell .panel-input,
  .messages-chat-shell .panel-select,
  .messages-chat-shell .panel-textarea,
  .messages-chat-shell .panel-btn {
    border-radius: 14px;
    background-color: #fff;
  }

  .messages-chat-layout {
    display: grid;
    grid-template-columns: minmax(300px, 360px) minmax(0, 1fr);
    gap: 1rem;
    min-height: 72vh;
  }

  .messages-thread-panel {
    padding: 1rem;
    display: grid;
    grid-template-rows: auto auto minmax(0, 1fr);
    gap: 0.9rem;
    background: linear-gradient(180deg, #0f1d30 0%, #162841 100%);
    color: #ffffff;
  }

  .messages-thread-panel .panel-kpi-label,
  .messages-thread-panel .panel-muted {
    color: rgba(232, 238, 247, 0.72);
  }

  .messages-thread-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
  }

  .messages-thread-title {
    margin: 0;
    font-size: 1.55rem;
    line-height: 1.1;
    color: #ffffff;
  }

  .messages-thread-sub {
    margin: 0.3rem 0 0;
    color: rgba(232, 238, 247, 0.74);
    font-size: 0.94rem;
  }

  .messages-thread-search {
    display: grid;
    gap: 0.7rem;
  }

  .messages-thread-search .panel-input,
  .messages-thread-search .panel-select {
    background: rgba(255, 255, 255, 0.08);
    border-color: rgba(255, 255, 255, 0.16);
    color: #ffffff;
  }

  .messages-thread-search .panel-input::placeholder {
    color: rgba(232, 238, 247, 0.48);
  }

  .messages-thread-search .panel-link {
    color: rgba(232, 238, 247, 0.82);
  }

  .messages-thread-list {
    display: grid;
    gap: 0.55rem;
    overflow-y: auto;
    padding-right: 0.2rem;
  }

  .messages-thread-item {
    display: grid;
    grid-template-columns: 44px minmax(0, 1fr);
    gap: 0.8rem;
    padding: 0.82rem;
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 16px;
    background: rgba(255, 255, 255, 0.04);
    transition: border-color 0.18s ease, background-color 0.18s ease, transform 0.18s ease;
  }

  .messages-thread-item:hover {
    border-color: rgba(255, 255, 255, 0.18);
    background: rgba(255, 255, 255, 0.08);
    transform: translateY(-1px);
  }

  .messages-thread-item.is-active {
    border-color: rgba(255, 255, 255, 0.24);
    background: linear-gradient(135deg, rgba(191, 30, 46, 0.28), rgba(118, 24, 42, 0.24));
    box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.08);
  }

  .messages-thread-avatar {
    width: 44px;
    height: 44px;
    display: grid;
    place-items: center;
    border-radius: 14px;
    background: rgba(255, 255, 255, 0.12);
    color: #ffffff;
    font-weight: 700;
    font-size: 0.95rem;
  }

  .messages-thread-main {
    min-width: 0;
  }

  .messages-thread-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.65rem;
    margin-bottom: 0.18rem;
  }

  .messages-thread-name {
    margin: 0;
    color: #ffffff;
    font-size: 0.98rem;
    line-height: 1.3;
  }

  .messages-thread-time {
    color: rgba(232, 238, 247, 0.58);
    font-size: 0.75rem;
    white-space: nowrap;
  }

  .messages-thread-meta,
  .messages-thread-preview {
    margin: 0;
    font-size: 0.84rem;
    line-height: 1.45;
  }

  .messages-thread-meta {
    color: rgba(232, 238, 247, 0.7);
  }

  .messages-thread-preview {
    color: rgba(232, 238, 247, 0.84);
  }

  .messages-chat-panel {
    display: grid;
    grid-template-rows: auto minmax(0, 1fr) auto;
    overflow: hidden;
    background: var(--messages-bg);
  }

  .messages-chat-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem 1.1rem;
    border-bottom: 1px solid var(--messages-line);
    background: linear-gradient(135deg, #ffffff 0%, #f5f8fc 100%);
  }

  .messages-chat-head h2 {
    margin: 0;
    font-size: 1.26rem;
    color: var(--messages-ink);
  }

  .messages-chat-head p {
    margin: 0.28rem 0 0;
    color: var(--messages-muted);
  }

  .messages-chat-head-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.55rem;
    justify-content: flex-end;
  }

  .messages-chat-head-meta .panel-badge {
    border-radius: 999px;
  }

  .messages-chat-stream {
    overflow-y: auto;
    padding: 1.1rem;
    display: grid;
    gap: 0.85rem;
    align-content: start;
  }

  .messages-chat-date {
    justify-self: center;
    padding: 0.3rem 0.75rem;
    border: 1px solid rgba(17, 38, 63, 0.08);
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.86);
    color: var(--messages-muted);
    font-size: 0.76rem;
    font-weight: 600;
  }

  .messages-chat-row {
    display: grid;
    gap: 0.3rem;
    justify-items: start;
  }

  .messages-chat-row.is-admin {
    justify-items: end;
  }

  .messages-chat-bubble {
    max-width: min(700px, 82%);
    padding: 0.9rem 1rem;
    border-radius: 18px;
    border: 1px solid #dbe4ef;
    background: #ffffff;
    color: var(--messages-ink);
    box-shadow: 0 8px 18px rgba(16, 35, 58, 0.05);
    white-space: pre-wrap;
    word-break: break-word;
    line-height: 1.55;
  }

  .messages-chat-row.is-admin .messages-chat-bubble {
    border-color: rgba(191, 30, 46, 0.14);
    background: linear-gradient(135deg, #b91e2f 0%, #8c1624 100%);
    color: #ffffff;
  }

  .messages-chat-note {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    color: var(--messages-muted);
    font-size: 0.77rem;
  }

  .messages-chat-row.is-admin .messages-chat-note {
    justify-content: flex-end;
  }

  .messages-chat-compose {
    padding: 1rem 1.1rem;
    border-top: 1px solid var(--messages-line);
    background: #ffffff;
    display: grid;
    gap: 0.8rem;
  }

  .messages-chat-compose-top {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 220px auto;
    gap: 0.7rem;
  }

  .messages-chat-compose .panel-textarea {
    min-height: 112px;
  }

  .messages-chat-compose-actions {
    display: flex;
    justify-content: flex-end;
  }

  .messages-empty-state {
    min-height: 320px;
    display: grid;
    place-items: center;
    text-align: center;
    padding: 2rem;
    color: var(--messages-muted);
  }

  .messages-empty-state strong {
    display: block;
    margin-bottom: 0.35rem;
    color: var(--messages-ink);
    font-size: 1.05rem;
  }

  @media (max-width: 1180px) {
    .messages-chat-layout {
      grid-template-columns: 320px minmax(0, 1fr);
    }

    .messages-chat-compose-top {
      grid-template-columns: 1fr;
    }
  }

  @media (max-width: 920px) {
    .messages-chat-layout {
      grid-template-columns: 1fr;
      min-height: auto;
    }

    .messages-thread-panel,
    .messages-chat-panel {
      min-height: auto;
    }

    .messages-thread-list {
      max-height: 360px;
    }

    .messages-chat-bubble {
      max-width: 100%;
    }
  }
</style>

<div class="messages-chat-shell">
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

  <section class="panel-card messages-chat-layout">
    <aside class="messages-thread-panel">
      <div class="messages-thread-top">
        <div>
          <h2 class="messages-thread-title">Chats</h2>
          <p class="messages-thread-sub">Open any client conversation and continue the thread directly from CRM.</p>
        </div>
      </div>

      <form method="get" class="messages-thread-search">
        <input class="panel-input" type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search client, project, or message text">
        <div class="panel-form-row" style="margin-bottom:0;">
          <select class="panel-select" name="sender_role">
            <option value="">All senders</option>
            <option value="admin" @selected(($filters['sender_role'] ?? '') === 'admin')>Admin</option>
            <option value="client" @selected(($filters['sender_role'] ?? '') === 'client')>Client</option>
          </select>
          <button class="panel-btn panel-btn-primary" type="submit">Filter</button>
          <a class="panel-link" href="{{ route('admin.messages.index') }}">Clear</a>
        </div>
      </form>

      <div class="messages-thread-list">
        @forelse($clients as $client)
          @php($summary = $threadSummaries->firstWhere('client_id', $client->id))
          <a
            href="{{ route('admin.messages.index', ['client_id' => $client->id, 'search' => $filters['search'] ?? null, 'sender_role' => $filters['sender_role'] ?? null]) }}"
            class="messages-thread-item{{ (int) ($activeClient?->id ?? 0) === (int) $client->id ? ' is-active' : '' }}"
          >
            <div class="messages-thread-avatar">{{ \\Illuminate\\Support\\Str::upper(\\Illuminate\\Support\\Str::substr($client->name ?: 'C', 0, 2)) }}</div>
            <div class="messages-thread-main">
              <div class="messages-thread-row">
                <h3 class="messages-thread-name">{{ $client->name ?: ('Client #' . $client->id) }}</h3>
                <span class="messages-thread-time">{{ $summary?->sent_at?->diffForHumans() ?: 'No chat yet' }}</span>
              </div>
              <p class="messages-thread-meta">
                {{ $client->email ?: 'No email on file' }}
                @if($summary?->project?->title)
                  &bull; {{ $summary->project->title }}
                @endif
              </p>
              <p class="messages-thread-preview">
                @if($summary)
                  <strong>{{ strtoupper($summary->sender_role) }}:</strong> {{ \\Illuminate\\Support\\Str::limit($summary->message, 90) }}
                @else
                  No direct conversation yet. Start the thread from CRM.
                @endif
              </p>
            </div>
          </a>
        @empty
          <p class="panel-muted">No clients available yet.</p>
        @endforelse
      </div>
    </aside>

    <section class="messages-chat-panel">
      @if($activeClient)
        <header class="messages-chat-head">
          <div>
            <h2>{{ $activeClient->name ?: ('Client #' . $activeClient->id) }}</h2>
            <p>
              {{ $activeClient->email ?: 'No email on file' }}
              @if($activeClient->company)
                &bull; {{ $activeClient->company }}
              @endif
              @if($activeClient->phone)
                &bull; {{ $activeClient->phone }}
              @endif
            </p>
          </div>
          <div class="messages-chat-head-meta">
            <span class="panel-badge">{{ ucfirst($activeClient->status ?: 'active') }}</span>
            <a class="panel-btn" href="{{ route('admin.clients.show', $activeClient) }}">Open Client</a>
          </div>
        </header>

        <div class="messages-chat-stream">
          @php($lastDate = null)
          @forelse($activeMessages as $message)
            @php($currentDate = optional($message->sent_at ?? $message->created_at)?->format('Y-m-d'))
            @if($currentDate !== $lastDate)
              <div class="messages-chat-date">{{ optional($message->sent_at ?? $message->created_at)?->format('M j, Y') }}</div>
              @php($lastDate = $currentDate)
            @endif
            <article class="messages-chat-row{{ $message->sender_role === 'admin' ? ' is-admin' : '' }}">
              <div class="messages-chat-bubble">{{ $message->message }}</div>
              <div class="messages-chat-note">
                <span>{{ strtoupper($message->sender_role) }}</span>
                @if($message->project?->title)
                  <span>&bull;</span>
                  <span>{{ $message->project->title }}</span>
                @endif
                <span>&bull;</span>
                <span>{{ optional($message->sent_at ?? $message->created_at)?->format('M j, g:i A') }}</span>
              </div>
            </article>
          @empty
            <div class="messages-empty-state">
              <div>
                <strong>No messages yet</strong>
                Start the first direct conversation with this client from the composer below.
              </div>
            </div>
          @endforelse
        </div>

        <form method="post" action="{{ route('admin.messages.store') }}" class="messages-chat-compose">
          @csrf
          <input type="hidden" name="client_id" value="{{ $activeClient->id }}">
          <div class="messages-chat-compose-top">
            <select class="panel-select" name="client_project_id">
              <option value="">General client message</option>
              @foreach($activeClient->projects as $project)
                <option value="{{ $project->id }}" @selected((int) old('client_project_id', 0) === (int) $project->id)>{{ $project->title }} @if($project->status) - {{ ucfirst($project->status) }} @endif</option>
              @endforeach
            </select>
            <div class="panel-muted" style="display:grid;align-items:center;padding:0 0.25rem;">
              Send as a direct CRM message. Optional project context keeps the conversation organized.
            </div>
            <div class="messages-chat-compose-actions">
              <button class="panel-btn panel-btn-primary" type="submit">Send Message</button>
            </div>
          </div>
          <textarea class="panel-textarea" name="message" placeholder="Write a clear message for the client thread" required>{{ old('message') }}</textarea>
        </form>
      @else
        <div class="messages-empty-state">
          <div>
            <strong>No client selected</strong>
            Pick a client from the left column to open the conversation workspace.
          </div>
        </div>
      @endif
    </section>
  </section>
</div>
@endsection
