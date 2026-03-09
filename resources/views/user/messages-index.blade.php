@extends('layouts.panel', [
  'title' => 'Messages',
  'heading' => 'Messages',
  'subheading' => 'Keep service requests and team communication organized in one client timeline.',
])

@section('content')
<div class="client-portal-shell">
  @php
    $requestStatusClass = static function (?string $status): string {
      return 'client-status-chip status-' . \Illuminate\Support\Str::slug((string) $status);
    };
  @endphp
  <section class="panel-card client-portal-stack client-portal-card-accent">
    <div class="client-portal-section-head">
      <div class="client-portal-section-copy">
        <h2 class="panel-section-title" style="margin: 0;">Project Threads</h2>
        <p class="client-portal-subtle" style="margin: 8px 0 0;">Open a project thread to review service activity, project conversation, and delivery progress together.</p>
      </div>
      <a class="panel-btn" href="{{ route('user.projects.index') }}">Open Projects</a>
    </div>
    @if($projects->isNotEmpty())
      <div class="client-portal-list">
        @foreach($projects as $project)
          <div class="client-portal-list-row">
            <div class="client-portal-list-main">
              <h3 class="client-portal-title">{{ $project->title }}</h3>
              <p class="client-portal-meta">{{ $project->messages_count }} messages &bull; {{ $project->service_requests_count }} service requests</p>
            </div>
            <div class="client-portal-side">
              <a class="panel-btn panel-btn-primary" href="{{ route('user.projects.show', $project) }}">Open Thread</a>
            </div>
          </div>
        @endforeach
      </div>
    @else
      <div class="client-portal-empty"><strong>No project threads yet</strong>Once projects are linked to your account, their message threads will appear here.</div>
    @endif
  </section>

  <section class="client-portal-grid-two">
    <article class="panel-card client-portal-stack">
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

    <article class="panel-card client-portal-stack">
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
        <div class="client-portal-empty"><strong>No message history yet</strong>Team replies and client portal updates will appear in this timeline.</div>
      @endforelse
      <x-panel-pagination :paginator="$messages" />
    </article>
  </section>

  <section class="panel-card client-portal-table">
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
              <td>
                {{ $requestItem->requested_service }}
                @if(!blank($requestItem->subject))
                  <div class="panel-muted">{{ $requestItem->subject }}</div>
                @endif
              </td>
              <td>{{ $requestItem->project?->title ?: 'General request' }}</td>
              <td><span class="{{ $requestStatusClass($requestItem->status) }}">{{ $requestItem->status }}</span></td>
              <td>{{ $requestItem->preferred_date?->format('Y-m-d') ?: '-' }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="4">
                <div class="client-portal-empty"><strong>No service requests yet</strong>Request history will appear here after you send your first portal request.</div>
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
