@extends('layouts.panel', [
  'title' => 'Lead #' . $lead->id,
  'heading' => 'Lead #' . $lead->id,
  'subheading' => ($lead->name ?: 'Unnamed lead') . ' - ' . ($lead->email ?: ($lead->phone ?: 'No contact')),
])

@section('content')
@php
  $canManagePipeline = in_array(strtolower((string) auth()->user()?->role), ['owner', 'admin', 'manager'], true);

  $humanizeValue = static function ($value): string {
    if (is_array($value)) {
      $items = array_values(array_filter(array_map(static function ($item): string {
        if (is_scalar($item) || $item === null) {
          return trim((string) $item);
        }

        return json_encode($item, JSON_UNESCAPED_SLASHES) ?: '';
      }, $value), static fn ($item): bool => $item !== ''));

      return count($items) > 0 ? implode(', ', $items) : '-';
    }

    if (is_bool($value)) {
      return $value ? 'yes' : 'no';
    }

    if (is_scalar($value) || $value === null) {
      $text = trim((string) $value);
      return $text !== '' ? $text : '-';
    }

    return json_encode($value, JSON_UNESCAPED_SLASHES) ?: '-';
  };

  $humanizePayload = static function ($payload) use ($humanizeValue): array {
    if (!is_array($payload) || count($payload) === 0) {
      return [];
    }

    $lines = [];
    foreach ($payload as $key => $value) {
      $label = \Illuminate\Support\Str::headline((string) $key);
      $lines[] = $label . ': ' . $humanizeValue($value);
    }

    return $lines;
  };

  $humanizeTranscriptMessage = static function (?string $content) use ($humanizePayload): string {
    $text = trim((string) $content);
    if ($text === '') {
      return '-';
    }

    $decoded = json_decode($text, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
      $lines = $humanizePayload($decoded);
      return count($lines) > 0 ? implode("\n", $lines) : $text;
    }

    return $text;
  };
@endphp
<section class="panel-two-col">
  <div class="panel-main-col">
    <article class="panel-card">
      <h2 class="panel-section-title">Lead Details</h2>
      <div class="panel-kv-grid">
        <div class="panel-kv-item"><span class="panel-kv-label">Status</span><span class="panel-kv-value"><span class="panel-badge">{{ $lead->status }}</span></span></div>
        <div class="panel-kv-item"><span class="panel-kv-label">Score</span><span class="panel-kv-value">{{ $lead->score }}</span></div>
        <div class="panel-kv-item"><span class="panel-kv-label">Service</span><span class="panel-kv-value">{{ $lead->service_type ?: '-' }}</span></div>
        <div class="panel-kv-item"><span class="panel-kv-label">Property</span><span class="panel-kv-value">{{ $lead->property_type ?: '-' }}</span></div>
        <div class="panel-kv-item"><span class="panel-kv-label">Location</span><span class="panel-kv-value">{{ $lead->location ?: '-' }}</span></div>
        <div class="panel-kv-item"><span class="panel-kv-label">Preferred Contact</span><span class="panel-kv-value">{{ $lead->preferred_contact ?: '-' }}</span></div>
      </div>
    </article>

    <article class="panel-card">
      <h2 class="panel-section-title">Conversation Transcript</h2>
      @php
        $allMessages = ($lead->conversation?->messages ?? collect())->values();
        $previewCount = 3;
        $previewMessages = $allMessages->count() > $previewCount ? $allMessages->slice(-$previewCount)->values() : $allMessages;
      @endphp
      <div class="panel-chat-list">
        @forelse($previewMessages as $message)
        <div class="panel-chat-item {{ $message->role === 'user' ? 'is-user' : 'is-assistant' }}">
          <p class="panel-chat-role">{{ strtoupper($message->role) }}</p>
          <p class="panel-chat-text">{!! nl2br(e($humanizeTranscriptMessage((string) $message->content))) !!}</p>
        </div>
        @empty
        <p class="panel-muted">No messages.</p>
        @endforelse
      </div>
      @if($allMessages->isNotEmpty())
      <div class="panel-form-row panel-transcript-actions">
        @if($allMessages->count() > $previewCount)
        <button type="button" class="panel-btn panel-btn-danger" data-transcript-open>See full conversation ({{ $allMessages->count() }})</button>
        @endif
        <a href="{{ route('admin.leads.conversation-pdf', $lead) }}" class="panel-btn panel-btn-danger panel-btn-export">Export Chat PDF</a>
      </div>
      @endif
    </article>

    <article class="panel-card">
      <h2 class="panel-section-title">Lead Timeline</h2>
      <div class="panel-table-wrap">
        <table class="panel-table">
          <thead><tr><th>Time</th><th>Event</th><th>By</th><th>Payload</th></tr></thead>
          <tbody>
            @forelse($lead->events as $event)
            <tr>
              <td>{{ $event->created_at?->format('Y-m-d H:i') }}</td>
              <td>{{ $event->event_type }}</td>
              <td>{{ $event->creator?->email ?: 'system' }}</td>
              <td>
                @php($payloadLines = $humanizePayload($event->payload))
                @if(count($payloadLines) > 0)
                  @foreach($payloadLines as $line)
                  <div class="panel-muted" style="margin:0 0 4px;">{{ $line }}</div>
                  @endforeach
                @else
                -
                @endif
              </td>
            </tr>
            @empty
            <tr><td colspan="4" class="panel-muted">No events yet.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </article>
  </div>

  <aside class="panel-side-col">
    <div class="panel-side-sticky">
      <article class="panel-card">
        <h2 class="panel-section-title">Update Status</h2>
        @if($canManagePipeline)
        <form method="post" action="{{ route('admin.leads.status', $lead) }}" class="panel-stack">
          @csrf
          <select class="panel-select" name="status" required>
            @foreach(['new','qualified','contacted','won','lost','nurturing'] as $status)
            <option value="{{ $status }}" @selected($lead->status === $status)>{{ ucfirst($status) }}</option>
            @endforeach
          </select>
          <textarea class="panel-textarea" name="note" placeholder="Optional note"></textarea>
          <button class="panel-btn panel-btn-primary" type="submit">Save status</button>
        </form>
        @else
        <p class="panel-muted">Read only access. Status updates are available for owner/admin/manager roles.</p>
        @endif
      </article>

      <article class="panel-card">
        <h2 class="panel-section-title">Schedule Follow-up</h2>
        @if($canManagePipeline)
        <form method="post" action="{{ route('admin.leads.follow-up', $lead) }}" class="panel-stack">
          @csrf
          <select class="panel-select" name="method" required>
            <option value="call">Call</option>
            <option value="email">Email</option>
            <option value="sms">SMS</option>
          </select>
          <input class="panel-input" type="datetime-local" name="due_at" required>
          <textarea class="panel-textarea" name="notes" placeholder="Notes"></textarea>
          <button class="panel-btn panel-btn-primary" type="submit">Add follow-up</button>
        </form>
        @else
        <p class="panel-muted">Read only access. Follow-up creation is disabled for this role.</p>
        @endif
      </article>

      @if($canManagePipeline)
      <article class="panel-card">
        <h2 class="panel-section-title">Danger Zone</h2>
        <form method="post" action="{{ route('admin.leads.delete', $lead) }}" onsubmit="return confirm('Delete this lead permanently?');">
          @csrf
          <button class="panel-btn panel-btn-danger panel-btn-icon" type="submit" title="Delete lead" aria-label="Delete lead"><span class="panel-icon-trash" aria-hidden="true"><svg viewBox="0 0 20 20"><path d="M5 6h10M8 6V4h4v2m-6 0l.5 9h7L14 6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span></button>
        </form>
      </article>
      @endif

      <article class="panel-card">
        <h2 class="panel-section-title">Follow-up Queue</h2>
        <div class="panel-table-wrap">
          <table class="panel-table">
            <thead><tr><th>Due</th><th>Method</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
              @forelse($lead->followUps as $followUp)
              <tr>
                <td>{{ $followUp->due_at?->format('Y-m-d H:i') ?: '-' }}</td>
                <td>{{ strtoupper($followUp->method) }}</td>
                <td><span class="panel-badge">{{ $followUp->status }}</span></td>
                <td>
                  @if($canManagePipeline)
                  <form method="post" action="{{ route('admin.follow-ups.status', $followUp) }}" class="panel-form-row">
                    @csrf
                    <select class="panel-select" name="status">
                      @foreach(['pending','completed','cancelled'] as $status)
                      <option value="{{ $status }}" @selected($followUp->status === $status)>{{ ucfirst($status) }}</option>
                      @endforeach
                    </select>
                    <button class="panel-btn" type="submit">Update</button>
                  </form>
                  @else
                  <span class="panel-badge">Read only</span>
                  @endif
                </td>
              </tr>
              @empty
              <tr><td colspan="4" class="panel-muted">No follow-ups yet.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </article>
    </div>
  </aside>
</section>

@if(($lead->conversation?->messages ?? collect())->count() > 3)
<div class="panel-modal" data-transcript-modal hidden>
  <div class="panel-modal-backdrop" data-transcript-close></div>
  <div class="panel-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="transcript-modal-title">
    <div class="panel-modal-head">
      <h3 id="transcript-modal-title" class="panel-modal-title">Full Conversation Transcript</h3>
      <button type="button" class="panel-modal-close" data-transcript-close aria-label="Close transcript">&times;</button>
    </div>
    <div class="panel-modal-body">
      <div class="panel-chat-list panel-chat-list-full">
        @foreach(($lead->conversation?->messages ?? collect()) as $message)
        <div class="panel-chat-item {{ $message->role === 'user' ? 'is-user' : 'is-assistant' }}">
          <p class="panel-chat-role">{{ strtoupper($message->role) }}</p>
          <p class="panel-chat-text">{!! nl2br(e($humanizeTranscriptMessage((string) $message->content))) !!}</p>
        </div>
        @endforeach
      </div>
    </div>
    <div class="panel-modal-foot">
      <button type="button" class="panel-btn panel-btn-primary" data-transcript-close>Close</button>
    </div>
  </div>
</div>

<script>
  (function () {
    const modal = document.querySelector('[data-transcript-modal]');
    const openBtn = document.querySelector('[data-transcript-open]');
    if (!modal || !openBtn) return;

    const closeButtons = modal.querySelectorAll('[data-transcript-close]');
    const open = function () {
      modal.hidden = false;
      document.body.classList.add('panel-modal-open');
    };
    const close = function () {
      modal.hidden = true;
      document.body.classList.remove('panel-modal-open');
    };

    openBtn.addEventListener('click', open);
    closeButtons.forEach(function (button) {
      button.addEventListener('click', close);
    });
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && !modal.hidden) {
        close();
      }
    });
  })();
</script>
@endif
@endsection
