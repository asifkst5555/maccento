@extends('layouts.panel', [
  'title' => 'Email Center',
  'heading' => 'Email Center',
  'subheading' => 'Professional one-click and custom email dispatch for CRM operations.',
])

@section('content')
<section class="panel-card crm-email-hero">
  <div>
    <h2 class="panel-section-title">CRM Email Operations</h2>
    <p class="panel-muted">Send high-priority notifications instantly, or craft custom outbound emails with CC, BCC, and reply-to routing.</p>
  </div>
  <div class="crm-email-kpis">
    <article class="panel-card">
      <span class="panel-kpi-label">New Leads</span>
      <p class="panel-kpi-value">{{ number_format((int) $pipelineSummary['leads_new']) }}</p>
    </article>
    <article class="panel-card">
      <span class="panel-kpi-label">Qualified Leads</span>
      <p class="panel-kpi-value">{{ number_format((int) $pipelineSummary['leads_qualified']) }}</p>
    </article>
    <article class="panel-card">
      <span class="panel-kpi-label">Booked Quotes</span>
      <p class="panel-kpi-value">{{ number_format((int) $pipelineSummary['quotes_booked']) }}</p>
    </article>
    <article class="panel-card">
      <span class="panel-kpi-label">Overdue Invoices</span>
      <p class="panel-kpi-value">{{ number_format((int) $pipelineSummary['invoices_overdue']) }}</p>
    </article>
  </div>
</section>

<section class="panel-card panel-stack">
  <h2 class="panel-section-title">One-Click Send</h2>
  <p class="panel-muted">Each quick action uses a prebuilt professional template and sends immediately with one click.</p>

  <div class="crm-email-quick-grid">
    @foreach($quickTemplates as $template)
    <form method="post" action="{{ route('admin.emails.send') }}" class="crm-email-quick-card">
      @csrf
      <input type="hidden" name="mode" value="template">
      <input type="hidden" name="template_key" value="{{ $template['key'] }}">

      <div>
        <h3>{{ $template['title'] }}</h3>
        <p class="panel-muted">{{ $template['description'] }}</p>
      </div>

      <label>
        <span>Recipient</span>
        <input class="panel-input" type="email" name="recipient_email" value="{{ old('recipient_email', $defaultRecipient) }}" required>
      </label>

      <label>
        <span>Reply-to (optional)</span>
        <input class="panel-input" type="email" name="reply_to" value="{{ old('reply_to', $defaultRecipient) }}">
      </label>

      <button class="panel-btn panel-btn-primary" type="submit">Send Now</button>
    </form>
    @endforeach
  </div>
</section>

<section class="crm-email-compose-layout">
  <article class="panel-card panel-stack">
    <div>
      <h2 class="panel-section-title">Compose Custom Email</h2>
      <p class="panel-muted">For client communication, follow-ups, or team notifications with full control.</p>
    </div>

    <form method="post" action="{{ route('admin.emails.send') }}" class="panel-stack">
      @csrf
      <input type="hidden" name="mode" value="custom">

      <div class="panel-form-row">
        <label>
          <span>To</span>
          <input class="panel-input" type="email" name="recipient_email" value="{{ old('recipient_email', $defaultRecipient) }}" required>
        </label>
        <label>
          <span>Reply-to</span>
          <input class="panel-input" type="email" name="reply_to" value="{{ old('reply_to', $defaultRecipient) }}">
        </label>
      </div>

      <div class="panel-form-row">
        <label>
          <span>CC (comma separated)</span>
          <input class="panel-input" type="text" name="cc" value="{{ old('cc') }}" placeholder="team@maccento.ca, ops@maccento.ca">
        </label>
        <label>
          <span>BCC (comma separated)</span>
          <input class="panel-input" type="text" name="bcc" value="{{ old('bcc') }}" placeholder="archive@maccento.ca">
        </label>
      </div>

      <label>
        <span>Subject</span>
        <input class="panel-input" type="text" name="subject" value="{{ old('subject') }}" maxlength="180" required>
      </label>

      <label>
        <span>Message</span>
        <textarea class="panel-textarea" name="message" rows="12" required>{{ old('message') }}</textarea>
      </label>

      <div class="panel-form-row" style="justify-content: space-between; align-items: center;">
        <div class="panel-badge">Outbound channel: SendGrid SMTP</div>
        <button class="panel-btn panel-btn-primary" type="submit">Send Custom Email</button>
      </div>
    </form>
  </article>

  <aside class="panel-card panel-stack crm-email-side">
    <h2 class="panel-section-title">Execution Checklist</h2>
    <ul>
      <li>Use an accurate subject line and include context in first sentence.</li>
      <li>Keep one call-to-action per email for better response rates.</li>
      <li>Use CC for collaborators and BCC for silent internal archive only.</li>
      <li>Delivery errors appear as panel alerts after submit.</li>
    </ul>

    <div class="panel-badge">Default notification inbox: {{ $defaultRecipient }}</div>
  </aside>
</section>

<section class="panel-card panel-stack">
  <div class="panel-form-row" style="justify-content: space-between; align-items: center;">
    <h2 class="panel-section-title" style="margin: 0;">Email History Log</h2>
    <span class="panel-badge">{{ number_format((int) $emailLogs->total()) }} records</span>
  </div>

  <div class="panel-table-wrap">
    <table class="panel-table crm-email-log-table">
      <thead>
        <tr>
          <th>Date</th>
          <th>Status</th>
          <th>Mode</th>
          <th>Recipient</th>
          <th>Subject</th>
          <th>Sender</th>
          <th>Notes</th>
        </tr>
      </thead>
      <tbody>
        @forelse($emailLogs as $log)
        @php
          $timeline = $emailEventTimeline->get($log->id, collect());
        @endphp
        <tr>
          <td>
            <strong>{{ $log->created_at?->format('Y-m-d H:i') ?: '-' }}</strong>
            @if($log->sent_at)
            <p class="panel-muted" style="margin: 4px 0 0;">Sent {{ $log->sent_at->diffForHumans() }}</p>
            @endif
          </td>
          <td>
            <span class="panel-badge @if($log->status === 'failed') panel-badge-danger @endif">{{ strtoupper($log->status) }}</span>
          </td>
          <td>
            <p style="margin: 0; text-transform: capitalize;">{{ $log->mode }}</p>
            <p class="panel-muted" style="margin: 4px 0 0;">{{ $log->template_key ?: 'custom' }}</p>
          </td>
          <td>
            <p style="margin: 0;">{{ $log->recipient_email }}</p>
            @if($log->cc)
            <p class="panel-muted" style="margin: 4px 0 0;">CC: {{ $log->cc }}</p>
            @endif
            @if($log->bcc)
            <p class="panel-muted" style="margin: 4px 0 0;">BCC: {{ $log->bcc }}</p>
            @endif
          </td>
          <td>
            <p style="margin: 0;">{{ $log->subject }}</p>
            @if($log->body_preview)
            <p class="panel-muted" style="margin: 4px 0 0;">{{ \Illuminate\Support\Str::limit($log->body_preview, 130) }}</p>
            @endif
            @if($log->provider_status)
            <p class="panel-muted" style="margin: 4px 0 0;">Provider: {{ strtoupper($log->provider_status) }}</p>
            @endif
          </td>
          <td>
            <p style="margin: 0;">{{ $log->creator?->name ?: 'System' }}</p>
            <p class="panel-muted" style="margin: 4px 0 0;">{{ $log->creator?->email ?: '-' }}</p>
          </td>
          <td>
            @if($log->status === 'failed')
            <span class="panel-badge panel-badge-danger">{{ $log->error_message ?: 'Unknown transport error' }}</span>
            @else
            <span class="panel-muted">Delivered to transport</span>
            @endif

            @if($timeline->count() > 0)
            <div class="crm-email-timeline">
              @foreach($timeline as $eventItem)
              <div class="crm-email-timeline-item">
                <strong>{{ strtoupper((string) $eventItem->event_type) }}</strong>
                <span>{{ $eventItem->occurred_at?->format('Y-m-d H:i:s') ?: $eventItem->created_at?->format('Y-m-d H:i:s') }}</span>
              </div>
              @endforeach
            </div>
            @endif
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="7" class="panel-muted">No email logs yet. Send from this tab to start tracking history.</td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <x-panel-pagination :paginator="$emailLogs" />
</section>

<style>
  .crm-email-hero {
    display: grid;
    gap: 16px;
  }

  .crm-email-kpis {
    display: grid;
    gap: 12px;
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }

  .crm-email-quick-grid {
    display: grid;
    gap: 14px;
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }

  .crm-email-quick-card {
    border: 1px solid rgba(15, 30, 56, 0.12);
    border-radius: 14px;
    padding: 14px;
    display: grid;
    gap: 10px;
    background: linear-gradient(160deg, #f7f9fc 0%, #ffffff 100%);
  }

  .crm-email-quick-card h3 {
    margin: 0 0 6px;
    font-size: 1rem;
    color: #17304f;
  }

  .crm-email-compose-layout {
    display: grid;
    gap: 14px;
    grid-template-columns: 2fr 1fr;
  }

  .crm-email-side ul {
    margin: 0;
    padding-left: 1rem;
    display: grid;
    gap: 8px;
  }

  .crm-email-log-table td {
    vertical-align: top;
  }

  .crm-email-timeline {
    margin-top: 8px;
    border-left: 2px solid rgba(20, 42, 74, 0.16);
    padding-left: 8px;
    display: grid;
    gap: 6px;
  }

  .crm-email-timeline-item {
    display: flex;
    gap: 8px;
    align-items: baseline;
    font-size: 0.8rem;
    color: #1f3554;
  }

  @media (max-width: 1100px) {
    .crm-email-kpis,
    .crm-email-quick-grid,
    .crm-email-compose-layout {
      grid-template-columns: 1fr;
    }
  }
</style>
@endsection
