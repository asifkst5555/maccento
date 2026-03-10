@extends('layouts.panel', [
  'title' => 'Messages',
  'heading' => 'Messages',
  'subheading' => 'Keep service requests and team communication organized in one client timeline.',
])

@section('content')
@php
  $requestStatusClass = static function (?string $status): string {
    return 'client-status-chip status-' . \Illuminate\Support\Str::slug((string) $status);
  };
@endphp

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

  <div class="messages-chat-shell">
    <section class="panel-card messages-chat-layout">
      <aside class="messages-thread-panel">
        <div class="messages-thread-top">
          <div>
            <h2 class="messages-thread-title">Chats</h2>
            <p class="messages-thread-sub">Open any admin conversation or project thread and keep updates centralized.</p>
          </div>
        </div>

        <form method="get" class="messages-thread-search">
          <select class="panel-select" name="admin_id">
            @forelse($adminUsers as $adminUser)
              <option value="{{ $adminUser->id }}" @selected((int) ($activeAdmin?->id ?? 0) === (int) $adminUser->id)>
                {{ $adminUser->name ?: ('Admin #' . $adminUser->id) }} @if($adminUser->role) - {{ ucfirst($adminUser->role) }} @endif
              </option>
            @empty
              <option value="">No admins available</option>
            @endforelse
          </select>
          <div class="panel-form-row" style="margin-bottom:0;">
            <button class="panel-btn panel-btn-primary" type="submit">Open</button>
            <a class="messages-thread-clear" href="{{ route('user.messages.index') }}">Clear</a>
          </div>
        </form>

        <div class="messages-thread-list">
          @forelse($adminUsers as $adminUser)
            @php($summary = $adminThreadSummaries->firstWhere('thread_admin_id', $adminUser->id))
            <a
              href="{{ route('user.messages.index', ['admin_id' => $adminUser->id]) }}"
              class="messages-thread-item{{ (int) ($activeAdmin?->id ?? 0) === (int) $adminUser->id ? ' is-active' : '' }}"
            >
              <div class="messages-thread-avatar">{{ strtoupper(substr($adminUser->name ?: 'A', 0, 2)) }}</div>
              <div class="messages-thread-main">
                <div class="messages-thread-row">
                  <h3 class="messages-thread-name">{{ $adminUser->name ?: ('Admin #' . $adminUser->id) }}</h3>
                  <span class="messages-thread-time">{{ $summary?->sent_at?->diffForHumans() ?: 'No chat yet' }}</span>
                </div>
                <p class="messages-thread-meta">
                  {{ $adminUser->email ?: 'No email on file' }}
                  @if($adminUser->role)
                    &bull; {{ ucfirst($adminUser->role) }}
                  @endif
                </p>
                <p class="messages-thread-preview">
                  @if($summary)
                    <strong>{{ $summary->sender?->name ?: 'Admin' }}:</strong> {{ mb_strimwidth((string) $summary->message, 0, 90, '...') }}
                  @else
                    No direct conversation yet. Start the thread from CRM.
                  @endif
                </p>
              </div>
            </a>
          @empty
            <p class="panel-muted">No admins available yet.</p>
          @endforelse

          <div class="messages-thread-divider">Project Threads</div>
          @forelse($projects as $project)
            <a class="messages-thread-item" href="{{ route('user.projects.show', $project) }}">
              <div class="messages-thread-avatar">{{ strtoupper(substr($project->title ?: 'P', 0, 2)) }}</div>
              <div class="messages-thread-main">
                <div class="messages-thread-row">
                  <h3 class="messages-thread-name">{{ $project->title }}</h3>
                  <span class="messages-thread-time">{{ $project->messages_count }} msgs</span>
                </div>
                <p class="messages-thread-meta">{{ $project->service_requests_count }} service requests</p>
                <p class="messages-thread-preview">Open the project thread</p>
              </div>
            </a>
          @empty
            <p class="panel-muted">No project threads yet.</p>
          @endforelse
        </div>
      </aside>

      <section class="messages-chat-panel">
        @if($activeAdmin)
          <header class="messages-chat-head">
            <div>
              <h2>{{ $activeAdmin->name ?: ('Admin #' . $activeAdmin->id) }}</h2>
              <p>
                {{ $activeAdmin->email ?: 'No email on file' }}
                @if($activeAdmin->role)
                  &bull; {{ ucfirst($activeAdmin->role) }}
                @endif
              </p>
            </div>
            <div class="messages-chat-head-meta">
              <span class="panel-badge">Active</span>
            </div>
          </header>

          <div class="messages-chat-stream">
            @php($lastDate = null)
            @forelse($adminMessages as $message)
              @php($currentDate = optional($message->sent_at ?? $message->created_at)?->format('Y-m-d'))
              @if($currentDate !== $lastDate)
                <div class="messages-chat-date">{{ optional($message->sent_at ?? $message->created_at)?->format('M j, Y') }}</div>
                @php($lastDate = $currentDate)
              @endif
              <article class="messages-chat-row{{ (int) $message->sender_user_id === (int) ($currentUser?->id ?? 0) ? ' is-admin' : '' }}">
                <div class="messages-chat-bubble">{{ $message->message }}</div>
                <div class="messages-chat-note">
                  <span>{{ $message->sender?->name ?: 'Admin' }}</span>
                  <span>&bull;</span>
                  <span>{{ optional($message->sent_at ?? $message->created_at)?->format('M j, g:i A') }}</span>
                </div>
              </article>
            @empty
              <div class="messages-empty-state">
                <div>
                  <strong>No messages yet</strong>
                  Start the first direct conversation with this admin from the composer below.
                </div>
              </div>
            @endforelse
          </div>

          <form method="post" action="{{ route('user.messages.store') }}" class="messages-chat-compose">
            @csrf
            <input type="hidden" name="admin_user_id" value="{{ $activeAdmin->id }}">
            <div class="messages-chat-compose-top">
              <div class="panel-muted" style="display:grid;align-items:center;padding:0 0.25rem;">
                Send a direct message to the admin team. Optional project context keeps the thread organized.
              </div>
              <button class="panel-btn panel-btn-primary" type="submit">Send Message</button>
            </div>
            <textarea class="panel-textarea" name="message" placeholder="Write a clear message for the admin team" required>{{ old('message') }}</textarea>
          </form>
        @else
          <div class="messages-empty-state">
            <div>
              <strong>No admin selected</strong>
              Choose an admin to start a direct message.
            </div>
          </div>
        @endif
      </section>
    </section>
  </div>

  <section class="panel-grid" style="grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1rem;">
    <article class="panel-card panel-stack">
      <h2 class="panel-section-title">Send a New Request</h2>
      <p class="panel-muted">Use this to request additional services, revisions, or schedule updates.</p>
      <form method="post" action="{{ route('user.service-requests.store') }}" class="panel-stack">
        @csrf
        <select class="panel-select" name="client_project_id">
          <option value="">General request (not linked to a project)</option>
          @foreach($projects as $project)
            <option value="{{ $project->id }}">{{ $project->title }}</option>
          @endforeach
        </select>
        <input class="panel-input" type="text" name="requested_service" placeholder="Requested service" required>
        <input class="panel-input" type="text" name="subject" placeholder="Subject (optional)">
        <input class="panel-input" type="date" name="preferred_date">
        <textarea class="panel-textarea" name="details" placeholder="Add details for the team"></textarea>
        <button class="panel-btn panel-btn-primary" type="submit">Submit Request</button>
      </form>
    </article>

    <article class="panel-card panel-stack">
      <h2 class="panel-section-title">Team Message Timeline</h2>
      @forelse($messages as $message)
        <div class="panel-chat-item {{ $message->sender_role === 'client' ? 'is-user' : 'is-assistant' }}">
          <p class="panel-chat-role">
            {{ strtoupper($message->sender_role) }}
            @if($message->project)
              &bull; {{ $message->project->title }}
            @endif
          </p>
          <p class="panel-chat-text">{{ $message->message }}</p>
          <p class="panel-muted">{{ $message->sent_at?->format('Y-m-d H:i') ?: $message->created_at?->format('Y-m-d H:i') }}</p>
        </div>
      @empty
        <div class="panel-muted"><strong>No message history yet</strong>Team replies and client portal updates will appear in this timeline.</div>
      @endforelse
      <x-panel-pagination :paginator="$messages" />
    </article>
  </section>

  <section class="panel-card panel-stack">
    <h2 class="panel-section-title">Service Request History</h2>
    <div class="panel-table-wrap">
      <table class="panel-table">
        <thead>
          <tr>
            <th>Requested Service</th>
            <th>Project</th>
            <th>Status</th>
            <th>Preferred Date</th>
          </tr>
        </thead>
        <tbody>
          @forelse($serviceRequests as $requestItem)
            <tr>
              <td data-label="Requested Service">
                {{ $requestItem->requested_service }}
                @if(!blank($requestItem->subject))
                  <div class="panel-muted">{{ $requestItem->subject }}</div>
                @endif
              </td>
              <td data-label="Project">{{ $requestItem->project?->title ?: 'General request' }}</td>
              <td data-label="Status"><span class="{{ $requestStatusClass($requestItem->status) }}">{{ $requestItem->status }}</span></td>
              <td data-label="Preferred Date">{{ $requestItem->preferred_date?->format('Y-m-d') ?: '-' }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="4" class="panel-muted">
                <strong>No service requests yet</strong>Request history will appear here after you send your first portal request.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <x-panel-pagination :paginator="$serviceRequests" />
  </section>
</div>
@endsection
