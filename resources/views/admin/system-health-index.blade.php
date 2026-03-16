@extends('layouts.panel', [
  'title' => 'System Health',
  'heading' => 'System Health',
  'subheading' => 'Monitor queue failures and email delivery health.',
])

@section('content')
<div class="corp-admin-shell panel-stack">
  <section class="panel-card panel-stack">
    <div class="panel-form-row" style="justify-content: space-between; align-items: center;">
      <div>
        <span class="panel-badge">Operations</span>
        <h2 class="panel-section-title" style="margin-top: 12px;">System Health</h2>
        <p class="panel-muted">Use this page to spot issues before they affect clients.</p>
      </div>
    </div>
  </section>

  <section class="panel-card panel-grid" style="grid-template-columns: repeat(3, minmax(0, 1fr));">
    <div class="panel-stat-card">
      <div class="panel-stat-label">Failed jobs (24h)</div>
      <div class="panel-stat-value">{{ $failedJobsCount ?? 'n/a' }}</div>
      <div class="panel-muted">Last failed job: {{ $latestFailedJobAt?->format('Y-m-d H:i') ?? 'none' }}</div>
    </div>
    <div class="panel-stat-card">
      <div class="panel-stat-label">Failed emails (24h)</div>
      <div class="panel-stat-value">{{ $failedEmailsCount ?? 'n/a' }}</div>
      <div class="panel-muted">Check the Email Center for delivery details.</div>
    </div>
    <div class="panel-stat-card">
      <div class="panel-stat-label">SendGrid failures (24h)</div>
      <div class="panel-stat-value">{{ $sendgridFailuresCount ?? 'n/a' }}</div>
      <div class="panel-muted">Bounces, drops, spam reports.</div>
    </div>
  </section>

  <section class="panel-card panel-stack">
    <h3 class="panel-section-title">Failed Jobs</h3>
    <div class="panel-table-wrap">
      <table class="panel-table">
        <thead>
          <tr>
            <th>When</th>
            <th>Connection</th>
            <th>Queue</th>
            <th>Exception</th>
          </tr>
        </thead>
        <tbody>
          @forelse($failedJobs as $job)
            <tr>
              <td data-label="When">{{ $job->failed_at ?? '-' }}</td>
              <td data-label="Connection">{{ $job->connection ?? '-' }}</td>
              <td data-label="Queue">{{ $job->queue ?? '-' }}</td>
              <td data-label="Exception">
                <div class="panel-muted">{{ \Illuminate\Support\Str::limit((string) ($job->exception ?? ''), 160) }}</div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="4" class="panel-muted">No failed jobs found.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>

  <section class="panel-card panel-stack">
    <h3 class="panel-section-title">Failed Emails</h3>
    <div class="panel-table-wrap">
      <table class="panel-table">
        <thead>
          <tr>
            <th>When</th>
            <th>Recipient</th>
            <th>Subject</th>
            <th>Error</th>
          </tr>
        </thead>
        <tbody>
          @forelse($failedEmails as $email)
            <tr>
              <td data-label="When">{{ $email->created_at?->format('Y-m-d H:i') ?? '-' }}</td>
              <td data-label="Recipient">{{ $email->recipient_email ?? '-' }}</td>
              <td data-label="Subject">{{ $email->subject ?? '-' }}</td>
              <td data-label="Error">
                <div class="panel-muted">{{ \Illuminate\Support\Str::limit((string) ($email->error_message ?? ''), 160) }}</div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="4" class="panel-muted">No failed emails found.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>

  <section class="panel-card panel-stack">
    <h3 class="panel-section-title">SendGrid Failures</h3>
    <div class="panel-table-wrap">
      <table class="panel-table">
        <thead>
          <tr>
            <th>When</th>
            <th>Event</th>
            <th>Email</th>
            <th>Message Id</th>
          </tr>
        </thead>
        <tbody>
          @forelse($sendgridFailures as $event)
            <tr>
              <td data-label="When">{{ $event->occurred_at?->format('Y-m-d H:i') ?? '-' }}</td>
              <td data-label="Event">{{ $event->event_type ?? '-' }}</td>
              <td data-label="Email">{{ $event->email ?? '-' }}</td>
              <td data-label="Message Id">{{ $event->sg_message_id ?? '-' }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="4" class="panel-muted">No SendGrid failures found.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>


</div>
@endsection
