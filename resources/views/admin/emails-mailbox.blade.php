@extends('layouts.panel', [
  'title' => 'Email Center',
  'heading' => 'Email Center',
  'subheading' => 'Inbox, sent, drafts, AI-assisted writing, and CRM compose in one workspace.',
])

@section('content')
@php
  $totalItems = (int) $mailboxItems->total();
  $inboxCount = (int) ($folderCounts['inbox'] ?? 0);
  $sentCount = (int) ($folderCounts['sent'] ?? 0);
  $draftCount = (int) ($folderCounts['drafts'] ?? 0);
  $composeRequested = (string) request()->query('compose', '') === '1';
  $hasDraftContext = request()->filled('draft') || ((int) ($compose['draft_id'] ?? 0) > 0);
  $composeShouldOpen = $composeRequested || $hasDraftContext || $errors->any();
  $detailShouldOpen = $selectedMessage !== null;
@endphp

<section class="crm-mailbox-v2 panel-stack">
  <article class="panel-card crm-mailbox-hero">
    <div class="crm-mailbox-hero__copy">
      <p class="crm-mailbox-eyebrow">Email Operations Hub</p>
      <h2 class="crm-mailbox-title">Unified inbox, sent timeline, and draft management</h2>
      <p class="crm-mailbox-sub">Track message flow and review thread context in a focused mailbox workspace.</p>
      <div class="crm-mailbox-hero__actions">
        <button class="panel-btn panel-btn-primary crm-btn-icon" type="button" data-compose-open><span class="crm-ui-icon"><svg viewBox="0 0 20 20" aria-hidden="true"><path d="M10 4v12M4 10h12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>Compose Email</button>
      </div>
    </div>
  </article>

  <section class="crm-mailbox-grid">
  <section class="panel-stack">
    <article class="panel-card panel-stack crm-mailbox-main-card">
      <div class="panel-form-row crm-mailbox-main-head" style="align-items:center;">
        <h2 class="panel-section-title" style="margin:0; text-transform:capitalize;">{{ $activeFolder }}</h2>
      </div>

      <nav class="crm-mail-tabs" aria-label="Mailbox folders">
        <a class="crm-mail-tab @if($activeFolder === 'inbox') is-active @endif" href="{{ route('admin.emails.inbox') }}">Inbox <span>{{ number_format($inboxCount) }}</span></a>
        <a class="crm-mail-tab @if($activeFolder === 'sent') is-active @endif" href="{{ route('admin.emails.sent') }}">Sent <span>{{ number_format($sentCount) }}</span></a>
        <a class="crm-mail-tab @if($activeFolder === 'drafts') is-active @endif" href="{{ route('admin.emails.drafts') }}">Drafts <span>{{ number_format($draftCount) }}</span></a>
      </nav>

      <div class="crm-mailbox-split">
        <section class="crm-mailbox-list-col">
          <div class="panel-table-wrap">
            @php
              $baseQuery = request()->query();
            @endphp
            <table class="panel-table crm-mailbox-table">
              @if($activeFolder === 'inbox')
              <thead><tr><th>From</th><th>Subject</th><th>Linked CRM</th><th>Received</th><th>Actions</th></tr></thead>
              <tbody>
                @forelse($mailboxItems as $item)
                <tr class="crm-mail-row @if((int) $openMessageId === (int) $item->id) is-active @endif" data-open-href="{{ route('admin.emails.inbox', array_merge($baseQuery, ['open_id' => $item->id])) }}">
                  <td class="crm-cell-truncate">{{ $item->from_name ?: $item->from_email }}</td>
                  <td class="crm-cell-truncate">{{ $item->subject ?: '(No subject)' }}</td>
                  <td class="crm-cell-truncate">
                    @if($item->client)
                    {{ $item->client->name }} @if($item->project?->title) - {{ $item->project->title }} @endif
                    @else
                    Unmatched
                    @endif
                  </td>
                  <td>{{ $item->received_at?->format('Y-m-d H:i') ?: ($item->created_at?->format('Y-m-d H:i') ?: '-') }}</td>
                  <td class="crm-mail-actions">
                    <a class="panel-btn crm-btn-icon" href="{{ route('admin.emails.inbox', array_merge($baseQuery, ['open_id' => $item->id])) }}" title="View email" aria-label="View email"><span class="crm-ui-icon"><svg viewBox="0 0 20 20" aria-hidden="true"><path d="M2.5 10s2.8-4.5 7.5-4.5 7.5 4.5 7.5 4.5-2.8 4.5-7.5 4.5S2.5 10 2.5 10zm7.5 2.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z" fill="none" stroke="currentColor" stroke-width="1.5"/></svg></span></a>
                    <form method="post" action="{{ route('admin.emails.inbox.delete', $item) }}" data-app-confirm="1" data-confirm-message="Delete this inbox email?">
                      @csrf
                      <button class="panel-btn panel-btn-danger crm-btn-icon" type="submit" title="Delete email" aria-label="Delete email"><span class="crm-ui-icon"><svg viewBox="0 0 20 20" aria-hidden="true"><path d="M5 6h10M8 6V4h4v2m-6 0l.5 9h7L14 6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span></button>
                    </form>
                  </td>
                </tr>
                @empty
                <tr><td colspan="5" class="panel-muted">No inbound emails yet. Configure SendGrid inbound parse to populate Inbox.</td></tr>
                @endforelse
              </tbody>
              @elseif($activeFolder === 'drafts')
              <thead><tr><th>Recipient</th><th>Subject</th><th>Updated</th><th>Actions</th></tr></thead>
              <tbody>
                @forelse($mailboxItems as $item)
                <tr class="crm-mail-row @if((int) $openMessageId === (int) $item->id) is-active @endif" data-open-href="{{ route('admin.emails.drafts', array_merge($baseQuery, ['open_id' => $item->id, 'draft' => $item->id])) }}">
                  <td>{{ $item->recipient_email ?: '-' }}</td>
                  <td>{{ $item->subject ?: '(No subject)' }}</td>
                  <td>{{ $item->updated_at?->format('Y-m-d H:i') ?: '-' }}</td>
                  <td class="crm-mail-actions crm-mail-actions--draft">
                    <a class="panel-btn crm-btn-icon" href="{{ route('admin.emails.drafts', ['open_id' => $item->id, 'draft' => $item->id]) }}" title="View draft" aria-label="View draft"><span class="crm-ui-icon"><svg viewBox="0 0 20 20" aria-hidden="true"><path d="M2.5 10s2.8-4.5 7.5-4.5 7.5 4.5 7.5 4.5-2.8 4.5-7.5 4.5S2.5 10 2.5 10zm7.5 2.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z" fill="none" stroke="currentColor" stroke-width="1.5"/></svg></span></a>
                    <a class="panel-link crm-btn-icon" href="{{ route('admin.emails.drafts', ['draft' => $item->id]) }}" title="Edit draft" aria-label="Edit draft"><span class="crm-ui-icon"><svg viewBox="0 0 20 20" aria-hidden="true"><path d="M4 14.8h2.4L14 7.2 11.8 5 4.2 12.6V15zM10.9 6l2.2 2.2" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span></a>
                    <form method="post" action="{{ route('admin.emails.drafts.send', $item) }}">
                      @csrf
                      <button class="panel-btn crm-btn-icon" type="submit" title="Send draft" aria-label="Send draft"><span class="crm-ui-icon"><svg viewBox="0 0 20 20" aria-hidden="true"><path d="M3 10l13-6-3.4 12-3.1-4.1L6 14l-3-4z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg></span></button>
                    </form>
                    <form method="post" action="{{ route('admin.emails.drafts.delete', $item) }}" data-app-confirm="1" data-confirm-message="Delete this draft?">
                      @csrf
                      <button class="panel-btn panel-btn-danger crm-btn-icon" type="submit" title="Delete draft" aria-label="Delete draft"><span class="crm-ui-icon"><svg viewBox="0 0 20 20" aria-hidden="true"><path d="M5 6h10M8 6V4h4v2m-6 0l.5 9h7L14 6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span></button>
                    </form>
                  </td>
                </tr>
                @empty
                <tr><td colspan="4" class="panel-muted">No drafts yet.</td></tr>
                @endforelse
              </tbody>
              @else
              <thead><tr><th>Recipient</th><th>Subject</th><th>Status</th><th>Sent</th><th>Provider Timeline</th><th>Actions</th></tr></thead>
              <tbody>
                @forelse($mailboxItems as $item)
                @php
                  $timeline = $emailEventTimeline->get($item->id, collect());
                @endphp
                <tr class="crm-mail-row @if((int) $openMessageId === (int) $item->id) is-active @endif" data-open-href="{{ route('admin.emails.sent', array_merge($baseQuery, ['open_id' => $item->id])) }}">
                  <td class="crm-cell-truncate">{{ $item->recipient_email }}</td>
                  <td class="crm-cell-truncate">{{ $item->subject }}</td>
                  <td><span class="panel-badge @if($item->status === 'failed') panel-badge-danger @endif">{{ strtoupper($item->status) }}</span></td>
                  <td>{{ $item->sent_at?->format('Y-m-d H:i') ?: '-' }}</td>
                  <td>
                    @if($timeline->count() > 0)
                    <div class="crm-timeline-mini">
                      @foreach($timeline as $event)
                      <div><strong>{{ strtoupper((string) $event->event_type) }}</strong> <span>{{ $event->occurred_at?->format('m-d H:i') ?: '' }}</span></div>
                      @endforeach
                    </div>
                    @else
                    <span class="panel-muted">-</span>
                    @endif
                  </td>
                  <td class="crm-mail-actions">
                    <a class="panel-btn crm-btn-icon" href="{{ route('admin.emails.sent', array_merge($baseQuery, ['open_id' => $item->id])) }}" title="View sent email" aria-label="View sent email"><span class="crm-ui-icon"><svg viewBox="0 0 20 20" aria-hidden="true"><path d="M2.5 10s2.8-4.5 7.5-4.5 7.5 4.5 7.5 4.5-2.8 4.5-7.5 4.5S2.5 10 2.5 10zm7.5 2.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z" fill="none" stroke="currentColor" stroke-width="1.5"/></svg></span></a>
                    <form method="post" action="{{ route('admin.emails.sent.delete', $item) }}" data-app-confirm="1" data-confirm-message="Delete this sent email record?">
                      @csrf
                      <button class="panel-btn panel-btn-danger crm-btn-icon" type="submit" title="Delete sent email" aria-label="Delete sent email"><span class="crm-ui-icon"><svg viewBox="0 0 20 20" aria-hidden="true"><path d="M5 6h10M8 6V4h4v2m-6 0l.5 9h7L14 6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span></button>
                    </form>
                  </td>
                </tr>
                @empty
                <tr><td colspan="6" class="panel-muted">No sent emails yet.</td></tr>
                @endforelse
              </tbody>
              @endif
            </table>
          </div>
          <x-panel-pagination :paginator="$mailboxItems" />
        </section>

      </div>
    </article>

    <div id="detailModal" class="crm-detail-modal @if($detailShouldOpen) is-open @endif" role="dialog" aria-modal="true" aria-labelledby="detailModalTitle" aria-hidden="@if($detailShouldOpen)false @else true @endif">
      <div class="crm-detail-modal__backdrop" data-detail-close></div>
      <article class="crm-detail-modal__card panel-card panel-stack">
        <section class="crm-thread-pane">
          <div class="crm-thread-pane__header">
            <h3 id="detailModalTitle">{{ data_get($threadMessages->first(), 'subject', 'Message detail') }}</h3>
            <div class="panel-form-row" style="gap:8px; align-items:center;">
              @php
                $replyToEmail = '';
                $replySubject = '';
                $replyProjectId = '';

                if ($selectedMessage) {
                  if ($activeFolder === 'inbox') {
                    $replyToEmail = (string) ($selectedMessage->from_email ?? '');
                    $replySubject = (string) ($selectedMessage->subject ?? '');
                    $replyProjectId = (string) ($selectedMessage->client_project_id ?? '');
                  } elseif ($activeFolder === 'sent') {
                    $replyToEmail = (string) ($selectedMessage->recipient_email ?? '');
                    $replySubject = (string) ($selectedMessage->subject ?? '');
                  } else {
                    $replyToEmail = (string) ($selectedMessage->recipient_email ?? '');
                    $replySubject = (string) ($selectedMessage->subject ?? '');
                    $replyProjectId = (string) ($selectedMessage->client_project_id ?? '');
                  }
                }
              @endphp
              @if($selectedMessage && $replyToEmail !== '')
              <button class="panel-btn panel-btn-primary crm-btn-icon" type="button" id="detailReplyBtn" data-reply-to="{{ $replyToEmail }}" data-reply-subject="{{ $replySubject }}" data-reply-project-id="{{ $replyProjectId }}"><span class="crm-ui-icon"><svg viewBox="0 0 20 20" aria-hidden="true"><path d="M8 6L3 10l5 4M4 10h9a4 4 0 0 1 4 4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>Reply</button>
              @endif
              <button class="panel-btn" type="button" data-detail-close>Close</button>
            </div>
          </div>

          @if($selectedMessage)
            @if($activeFolder === 'inbox')
            <div class="panel-form-row" style="gap:8px; align-items:center; flex-wrap:wrap;">
              <span class="panel-badge">Inbound</span>
              @if($selectedMessage->client)
              <span class="panel-badge">Client: {{ $selectedMessage->client->name }}</span>
              @endif
              @if($selectedMessage->project)
              <span class="panel-badge">Project: {{ $selectedMessage->project->title }}</span>
              @endif
            </div>
            @elseif($activeFolder === 'sent')
            <div class="panel-form-row" style="gap:8px; align-items:center; flex-wrap:wrap;">
              <span class="panel-badge">Sent</span>
              <span class="panel-badge">Status: {{ strtoupper((string) ($selectedMessage->status ?? 'sent')) }}</span>
              <span class="panel-badge">To: {{ $selectedMessage->recipient_email }}</span>
            </div>
            @if(($selectedEmailEventTimeline ?? collect())->count() > 0)
            <div class="crm-thread-events">
              @foreach(($selectedEmailEventTimeline ?? collect()) as $event)
              <div><strong>{{ strtoupper((string) $event->event_type) }}</strong> <span>{{ $event->occurred_at?->format('Y-m-d H:i') ?: '-' }}</span></div>
              @endforeach
            </div>
            @endif
            @else
            <div class="panel-form-row" style="gap:8px; align-items:center; flex-wrap:wrap;">
              <span class="panel-badge">Draft</span>
              <span class="panel-badge">To: {{ $selectedMessage->recipient_email ?: '-' }}</span>
            </div>
            @endif

            <div class="crm-thread-list">
              @foreach($threadMessages as $thread)
              <article class="crm-thread-message @if((bool) ($thread['is_selected'] ?? false)) is-selected @endif">
                <div class="crm-thread-message__meta">
                  <strong>{{ strtoupper((string) ($thread['direction'] ?? 'message')) }}</strong>
                  <span>{{ (string) ($thread['display_at'] ?? '-') }}</span>
                </div>
                <p class="crm-thread-message__from">
                  From: {{ (string) ($thread['from_label'] ?? '-') }}
                  @if(!blank($thread['from_email'] ?? null))
                  <span class="panel-muted">({{ (string) $thread['from_email'] }})</span>
                  @endif
                </p>
                <p class="crm-thread-message__to">To: {{ (string) ($thread['to_email'] ?? '-') }}</p>
                <h4>{{ (string) ($thread['subject'] ?? '(No subject)') }}</h4>
                <pre>{{ (string) (($thread['body'] ?? '') !== '' ? $thread['body'] : '(No message body stored)') }}</pre>
              </article>
              @endforeach
            </div>
          @else
            <p class="panel-muted" style="margin:0;">Click any row to open message details.</p>
          @endif
        </section>
      </article>
    </div>

    <div id="composeModal" class="crm-compose-modal @if($composeShouldOpen) is-open @endif" role="dialog" aria-modal="true" aria-labelledby="composeModalTitle" aria-hidden="@if($composeShouldOpen)false @else true @endif">
    <div class="crm-compose-modal__backdrop" data-compose-close></div>
    <article id="composeCard" class="panel-card panel-stack crm-compose-card crm-compose-modal__card">
      <div class="panel-form-row crm-compose-head" style="justify-content:space-between; align-items:center;">
        <h2 id="composeModalTitle" class="panel-section-title" style="margin:0;">Compose Email</h2>
        <div class="crm-compose-badges">
          <span class="panel-badge">OpenRouter AI Assist</span>
          <span id="autosaveBadge" class="panel-badge">Autosave idle</span>
          <span id="autosaveDirtyBadge" class="panel-badge">All changes saved</span>
          <button class="panel-btn" type="button" data-compose-close>Close</button>
        </div>
      </div>

      <section class="crm-compose-ai-panel panel-stack" aria-label="AI assistant tools">
        <div class="crm-compose-section-head">
          <h3>AI Assistant</h3>
          <p>Describe your intent and let AI draft a structured first version.</p>
        </div>
        <div class="crm-compose-ai-row" style="align-items:flex-end;">
        <label>
          <span>AI Template</span>
          <select id="aiTemplate" class="panel-select" aria-label="AI template">
            <option value="custom" @selected((string) ($compose['ai_template'] ?? 'custom') === 'custom')>Custom</option>
            <option value="cold_followup" @selected((string) ($compose['ai_template'] ?? 'custom') === 'cold_followup')>Cold Follow-up</option>
            <option value="quote_reminder" @selected((string) ($compose['ai_template'] ?? 'custom') === 'quote_reminder')>Quote Reminder</option>
            <option value="no_response_nudge" @selected((string) ($compose['ai_template'] ?? 'custom') === 'no_response_nudge')>No-response Nudge</option>
          </select>
        </label>
        <label style="flex:2;">
          <span>AI Goal / Instruction</span>
          <input id="aiGoal" class="panel-input" type="text" value="{{ (string) ($compose['ai_goal'] ?? '') }}" placeholder="Example: Follow up warmly and propose two meeting time slots this week">
        </label>
        <label>
          <span>Tone</span>
          <select id="aiTone" class="panel-select">
            <option value="professional">Professional</option>
            <option value="friendly">Friendly</option>
            <option value="urgent">Urgent</option>
            <option value="consultative">Consultative</option>
          </select>
        </label>
        <button id="aiWriteBtn" class="panel-btn panel-btn-primary crm-btn-icon crm-ai-write-btn" type="button" aria-label="Generate email with AI">
          <span class="crm-ui-icon"><svg viewBox="0 0 20 20" aria-hidden="true"><path d="M10 2l1.8 3.8L16 7l-3.1 3 0.7 4.2L10 12.2l-3.6 2 0.7-4.2L4 7l4.2-1.2L10 2z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg></span>
          <span id="aiWriteBtnLabel">Generate with AI</span>
        </button>
      </div>
      </section>

      <form id="composeForm" method="post" action="{{ route('admin.emails.send') }}" class="panel-stack crm-compose-form">
        @csrf
        <input type="hidden" name="mode" value="custom">
        <input id="composeDraftId" type="hidden" name="draft_id" value="{{ $compose['draft_id'] }}">

        <div class="crm-compose-section-head">
          <h3>Recipients</h3>
          <p>Set your recipient and optional response routing fields.</p>
        </div>
        <div class="crm-compose-grid-2">
          <label>
            <span>To <small class="panel-muted">(required)</small></span>
            <input id="composeTo" class="panel-input" type="email" name="recipient_email" value="{{ $compose['recipient_email'] }}" placeholder="lead@example.com" required>
          </label>
          <label>
            <span>Reply-to Address</span>
            <input class="panel-input" type="email" name="reply_to" value="{{ $compose['reply_to'] }}" placeholder="crm@reply.maccento.ca">
          </label>
          <label>
            <span>CC <small class="panel-muted">(optional)</small></span>
            <input class="panel-input" type="text" name="cc" value="{{ $compose['cc'] }}" placeholder="team@maccento.ca">
          </label>
          <label>
            <span>BCC <small class="panel-muted">(optional)</small></span>
            <input class="panel-input" type="text" name="bcc" value="{{ $compose['bcc'] }}" placeholder="archive@maccento.ca">
          </label>
        </div>

        <div class="crm-compose-section-head">
          <h3>Message Setup</h3>
          <p>Choose project context, confirm subject line, then write your message.</p>
        </div>
        <div class="crm-compose-grid-2">
          <label>
            <span>Thread Project</span>
            <select id="composeProject" class="panel-select" name="client_project_id">
              <option value="">Auto-detect from recipient (if exactly one)</option>
              @foreach($projectOptions as $projectOption)
              <option value="{{ $projectOption['id'] }}" @selected((string) $compose['client_project_id'] === (string) $projectOption['id'])>{{ $projectOption['label'] }}</option>
              @endforeach
            </select>
            <small class="panel-muted">Used for thread linking and automatic subject tag insertion.</small>
          </label>

          <label>
            <span>Subject</span>
            <input id="composeSubject" class="panel-input" type="text" name="subject" value="{{ $compose['subject'] }}" maxlength="180" placeholder="Example: Next Steps for Your Project" required>
            <small class="panel-muted">Final outgoing subject: <strong id="composeSubjectPreview">-</strong></small>
          </label>
        </div>

        <label>
          <span>Message</span>
          <small class="panel-muted">Write your final message body here before sending.</small>
          <textarea id="composeMessage" class="panel-textarea" name="message" rows="10" required>{{ $compose['message'] }}</textarea>
        </label>

        <div class="panel-form-row" style="justify-content:space-between; align-items:center;">
          <div class="crm-compose-actions">
            <button id="manualSaveDraftBtn" class="panel-btn crm-btn-icon" formaction="{{ route('admin.emails.drafts.save') }}" formmethod="post" type="submit"><span class="crm-ui-icon"><svg viewBox="0 0 20 20" aria-hidden="true"><path d="M4 4h10l2 2v10H4V4zm2 0v4h8V5.2L12.8 4H6zm1 9h6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg></span>Save Draft</button>
            <button class="panel-btn panel-btn-primary crm-btn-icon" type="submit"><span class="crm-ui-icon"><svg viewBox="0 0 20 20" aria-hidden="true"><path d="M3 10l13-6-3.4 12-3.1-4.1L6 14l-3-4z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg></span>Send Email</button>
            @if((int) ($compose['draft_id'] ?? 0) > 0)
            <button class="panel-btn panel-btn-danger panel-btn-icon crm-btn-icon" type="submit" form="draftDeleteForm" title="Delete draft" aria-label="Delete draft"><span class="crm-ui-icon"><svg viewBox="0 0 20 20" aria-hidden="true"><path d="M5 6h10M8 6V4h4v2m-6 0l.5 9h7L14 6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span></button>
            @endif
          </div>
          <span id="autosaveStatus" class="panel-muted">Lead ID: {{ $compose['lead_id'] !== '' ? $compose['lead_id'] : 'n/a' }}</span>
        </div>
      </form>
      @if((int) ($compose['draft_id'] ?? 0) > 0)
      <form id="draftDeleteForm" method="post" action="{{ route('admin.emails.drafts.delete', ['draft' => (int) $compose['draft_id']]) }}" data-app-confirm="1" data-confirm-message="Delete this draft?">
        @csrf
      </form>
      @endif
    </article>
    </div>
  </section>
</section>
</section>

  <div id="appConfirmModal" class="crm-confirm-modal" role="dialog" aria-modal="true" aria-labelledby="appConfirmTitle" aria-hidden="true">
    <div class="crm-confirm-modal__backdrop" data-confirm-close></div>
    <article class="crm-confirm-modal__card panel-card panel-stack">
      <h3 id="appConfirmTitle" class="panel-section-title" style="margin:0;">Confirm Action</h3>
      <p id="appConfirmMessage" class="panel-muted" style="margin:0;">Are you sure?</p>
      <div class="panel-form-row" style="justify-content:flex-end; gap:8px; margin-top:8px;">
        <button id="appConfirmCancel" class="panel-btn" type="button" data-confirm-close>Cancel</button>
        <button id="appConfirmOk" class="panel-btn panel-btn-danger panel-btn-icon" type="button" title="Confirm delete" aria-label="Confirm delete"><span class="panel-icon-trash" aria-hidden="true"><svg viewBox="0 0 20 20"><path d="M5 6h10M8 6V4h4v2m-6 0l.5 9h7L14 6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span></button>
      </div>
    </article>
  </div>

<script>
(function () {
  const projectOptions = @json($projectOptions);
  const composeTo = document.getElementById('composeTo');
  const composeProject = document.getElementById('composeProject');
  const composeSubject = document.getElementById('composeSubject');
  const detailReplyBtn = document.getElementById('detailReplyBtn');
  const composeSubjectPreview = document.getElementById('composeSubjectPreview');
  const composeForm = document.getElementById('composeForm');
  const composeDraftId = document.getElementById('composeDraftId');
  const manualSaveDraftBtn = document.getElementById('manualSaveDraftBtn');
  const sendEmailBtn = composeForm ? composeForm.querySelector('button.panel-btn.panel-btn-primary[type="submit"]') : null;
  const aiWriteBtn = document.getElementById('aiWriteBtn');
  const aiWriteBtnLabel = document.getElementById('aiWriteBtnLabel');
  const aiTemplate = document.getElementById('aiTemplate');
  const aiGoal = document.getElementById('aiGoal');
  const aiTone = document.getElementById('aiTone');
  const composeMessage = document.getElementById('composeMessage');
  const autosaveStatus = document.getElementById('autosaveStatus');
  const autosaveBadge = document.getElementById('autosaveBadge');
  const autosaveDirtyBadge = document.getElementById('autosaveDirtyBadge');
  const rowLinks = Array.from(document.querySelectorAll('.crm-mail-row[data-open-href]'));
  const composeCard = document.getElementById('composeCard');
  const composeModal = document.getElementById('composeModal');
  const composeOpeners = Array.from(document.querySelectorAll('[data-compose-open]'));
  const composeClosers = Array.from(document.querySelectorAll('[data-compose-close]'));
  const detailModal = document.getElementById('detailModal');
  const detailClosers = Array.from(document.querySelectorAll('[data-detail-close]'));
  const confirmModal = document.getElementById('appConfirmModal');
  const confirmMessage = document.getElementById('appConfirmMessage');
  const confirmOkBtn = document.getElementById('appConfirmOk');
  const confirmCloseBtns = Array.from(document.querySelectorAll('[data-confirm-close]'));
  const confirmForms = Array.from(document.querySelectorAll('form[data-app-confirm="1"]'));
  let pendingConfirmForm = null;

  const aiGoalDefaults = {
    cold_followup: 'Send a short warm follow-up and ask for a 10-minute call this week.',
    quote_reminder: 'Remind this lead about their quote and offer help with any questions.',
    no_response_nudge: 'Write a polite nudge with two easy options to continue.',
  };

  const projectTagPattern = /\[(?:project|proj|p)\s*[-:#]?\s*\d+\]/i;
  const normalizeEmail = (value) => String(value || '').trim().toLowerCase();

  const resolveProjectId = () => {
    const selectedId = Number(composeProject?.value || 0);
    if (selectedId > 0) {
      return selectedId;
    }

    const recipient = normalizeEmail(composeTo?.value || '');
    if (recipient === '') {
      return null;
    }

    const ids = projectOptions
      .filter((item) => normalizeEmail(item.client_email) === recipient)
      .map((item) => Number(item.id || 0))
      .filter((id) => id > 0);

    const unique = [...new Set(ids)];
    return unique.length === 1 ? unique[0] : null;
  };

  const appendTag = (subject, projectId) => {
    const trimmed = String(subject || '').trim();
    if (trimmed === '' || !projectId || Number(projectId) <= 0) {
      return trimmed;
    }
    if (projectTagPattern.test(trimmed)) {
      return trimmed;
    }
    return `${trimmed} [P#${projectId}]`;
  };

  const updatePreview = () => {
    const finalSubject = appendTag(composeSubject?.value || '', resolveProjectId());
    composeSubjectPreview.textContent = finalSubject === '' ? '-' : finalSubject;
  };

  composeTo?.addEventListener('input', updatePreview);
  composeProject?.addEventListener('change', updatePreview);
  composeSubject?.addEventListener('input', updatePreview);
  updatePreview();

  rowLinks.forEach((row) => {
    row.style.cursor = 'pointer';
    row.addEventListener('click', (event) => {
      const target = event.target;
      if (target && target.closest('a,button,form,input,select,textarea')) {
        return;
      }
      const href = row.getAttribute('data-open-href');
      if (href) {
        window.location.href = href;
      }
    });
  });

  const openComposeModal = () => {
    if (!composeModal) {
      return;
    }
    composeModal.classList.add('is-open');
    composeModal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('crm-compose-modal-open');
    const url = new URL(window.location.href);
    url.searchParams.set('compose', '1');
    window.history.replaceState({}, '', `${url.pathname}?${url.searchParams.toString()}`);
    composeCard?.classList.add('crm-compose-focus');
    setTimeout(() => composeCard?.classList.remove('crm-compose-focus'), 1300);
  };

  const closeComposeModal = () => {
    if (!composeModal) {
      return;
    }
    composeModal.classList.remove('is-open');
    composeModal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('crm-compose-modal-open');
    const url = new URL(window.location.href);
    url.searchParams.delete('compose');
    const query = url.searchParams.toString();
    window.history.replaceState({}, '', query ? `${url.pathname}?${query}` : url.pathname);
  };

  const closeDetailModal = () => {
    if (!detailModal) {
      return;
    }

    detailModal.classList.remove('is-open');
    detailModal.setAttribute('aria-hidden', 'true');
    const url = new URL(window.location.href);
    url.searchParams.delete('open_id');
    url.searchParams.delete('draft');
    const query = url.searchParams.toString();
    window.history.replaceState({}, '', query ? `${url.pathname}?${query}` : url.pathname);
  };

  composeOpeners.forEach((button) => {
    button.addEventListener('click', (event) => {
      event.preventDefault();
      openComposeModal();
    });
  });

  composeClosers.forEach((button) => {
    button.addEventListener('click', (event) => {
      event.preventDefault();
      closeComposeModal();
    });
  });

  const normalizeReplySubject = (subject) => {
    const text = String(subject || '').trim();
    if (text === '') {
      return 'Re: Your email';
    }
    return /^re:/i.test(text) ? text : `Re: ${text}`;
  };

  detailReplyBtn?.addEventListener('click', () => {
    if (!composeTo || !composeSubject) {
      return;
    }

    const replyTo = String(detailReplyBtn.getAttribute('data-reply-to') || '').trim();
    const replySubject = normalizeReplySubject(detailReplyBtn.getAttribute('data-reply-subject') || '');
    const replyProjectId = String(detailReplyBtn.getAttribute('data-reply-project-id') || '').trim();

    composeTo.value = replyTo;
    composeSubject.value = replySubject;
    if (composeProject && replyProjectId !== '') {
      composeProject.value = replyProjectId;
    }

    updatePreview();
    openComposeModal();
    closeDetailModal();
    composeMessage?.focus();
  });

  detailClosers.forEach((button) => {
    button.addEventListener('click', (event) => {
      event.preventDefault();
      closeDetailModal();
    });
  });

  const openConfirmModal = (message, form) => {
    if (!confirmModal || !confirmMessage) {
      return;
    }
    pendingConfirmForm = form;
    confirmMessage.textContent = String(message || 'Are you sure?');
    confirmModal.classList.add('is-open');
    confirmModal.setAttribute('aria-hidden', 'false');
  };

  const closeConfirmModal = () => {
    if (!confirmModal) {
      return;
    }
    confirmModal.classList.remove('is-open');
    confirmModal.setAttribute('aria-hidden', 'true');
    pendingConfirmForm = null;
  };

  confirmForms.forEach((form) => {
    form.addEventListener('submit', (event) => {
      if (form.dataset.confirmed === '1') {
        delete form.dataset.confirmed;
        return;
      }

      event.preventDefault();
      openConfirmModal(form.getAttribute('data-confirm-message') || 'Are you sure?', form);
    });
  });

  confirmCloseBtns.forEach((button) => {
    button.addEventListener('click', (event) => {
      event.preventDefault();
      closeConfirmModal();
    });
  });

  confirmOkBtn?.addEventListener('click', () => {
    if (!pendingConfirmForm) {
      return;
    }

    pendingConfirmForm.dataset.confirmed = '1';
    pendingConfirmForm.requestSubmit();
    closeConfirmModal();
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && detailModal?.classList.contains('is-open')) {
      closeDetailModal();
    }
    if (event.key === 'Escape' && composeModal?.classList.contains('is-open')) {
      closeComposeModal();
    }
    if (event.key === 'Escape' && confirmModal?.classList.contains('is-open')) {
      closeConfirmModal();
    }
  });

  if (composeModal?.classList.contains('is-open')) {
    document.body.classList.add('crm-compose-modal-open');
  }

  aiTemplate?.addEventListener('change', () => {
    const selected = String(aiTemplate.value || 'custom');
    if (selected !== 'custom' && String(aiGoal?.value || '').trim() === '') {
      aiGoal.value = aiGoalDefaults[selected] || '';
    }
  });

  let lastAutosaveFingerprint = '';
  let lastSavedFingerprint = '';
  let autosaveBusy = false;
  let userDirty = false;

  const updateAutosaveStatus = (text) => {
    if (!autosaveStatus) {
      return;
    }
    autosaveStatus.textContent = text;
  };

  const updateAutosaveBadge = (text) => {
    if (!autosaveBadge) {
      return;
    }
    autosaveBadge.textContent = text;
  };

  const updateDirtyBadge = (isDirty) => {
    if (!autosaveDirtyBadge) {
      return;
    }

    autosaveDirtyBadge.textContent = isDirty ? 'Unsaved changes' : 'All changes saved';
    autosaveDirtyBadge.classList.toggle('crm-badge-warn', isDirty);
  };

  const buildAutosaveFingerprint = () => {
    return JSON.stringify({
      draft: String(composeDraftId?.value || ''),
      to: String(composeTo?.value || '').trim(),
      subject: String(composeSubject?.value || '').trim(),
      message: String(composeMessage?.value || '').trim(),
      project: String(composeProject?.value || ''),
    });
  };

  const autosaveDraft = async () => {
    if (!composeForm || autosaveBusy) {
      return;
    }

    const fingerprint = buildAutosaveFingerprint();
    if (fingerprint === lastAutosaveFingerprint) {
      return;
    }

    const snapshot = JSON.parse(fingerprint);
    if (snapshot.to === '' && snapshot.subject === '' && snapshot.message === '') {
      return;
    }

    autosaveBusy = true;
    updateAutosaveStatus('Autosaving draft...');

    try {
      const payload = new FormData(composeForm);
      const response = await fetch('{{ route('admin.emails.drafts.save') }}', {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
          'Accept': 'application/json',
        },
        body: payload,
      });

      if (!response.ok) {
        throw new Error('Autosave failed');
      }

      const data = await response.json();
      if (composeDraftId && data.draft_id) {
        composeDraftId.value = String(data.draft_id);
      }
      lastAutosaveFingerprint = buildAutosaveFingerprint();
      lastSavedFingerprint = lastAutosaveFingerprint;
      userDirty = false;
      updateDirtyBadge(false);
      const ts = new Date();
      updateAutosaveBadge('Autosave active');
      updateAutosaveStatus(`Draft autosaved at ${ts.toLocaleTimeString()}`);
    } catch (error) {
      updateAutosaveBadge('Autosave retrying');
      updateAutosaveStatus('Autosave paused. Continue typing and use Save Draft if needed.');
    } finally {
      autosaveBusy = false;
    }
  };

  const recalculateDirty = () => {
    const current = buildAutosaveFingerprint();
    userDirty = current !== lastSavedFingerprint;
    updateDirtyBadge(userDirty);
  };

  const trackedFields = [composeTo, composeProject, composeSubject, composeMessage];
  trackedFields.forEach((field) => {
    if (!field) {
      return;
    }
    const evtName = field.tagName === 'SELECT' ? 'change' : 'input';
    field.addEventListener(evtName, recalculateDirty);
  });

  composeForm?.addEventListener('submit', (event) => {
    const submitter = event.submitter;
    if (submitter === manualSaveDraftBtn) {
      updateAutosaveBadge('Saving draft...');
      updateAutosaveStatus('Saving draft...');
      return;
    }

    updateAutosaveBadge('Sending email...');
    updateAutosaveStatus('Sending email...');
  });

  const isComposeFocused = () => {
    const active = document.activeElement;
    return !!(active && composeForm && composeForm.contains(active));
  };

  document.addEventListener('keydown', (event) => {
    if (!composeForm) {
      return;
    }

    if (!isComposeFocused()) {
      return;
    }

    const key = String(event.key || '').toLowerCase();
    const isCtrlLike = event.ctrlKey || event.metaKey;

    if (isCtrlLike && key === 's') {
      event.preventDefault();
      if (manualSaveDraftBtn && !manualSaveDraftBtn.disabled) {
        manualSaveDraftBtn.click();
      }
      return;
    }

    if (isCtrlLike && key === 'enter') {
      event.preventDefault();
      if (sendEmailBtn && !sendEmailBtn.disabled) {
        sendEmailBtn.click();
      }
    }
  });

  lastSavedFingerprint = buildAutosaveFingerprint();
  lastAutosaveFingerprint = lastSavedFingerprint;
  updateAutosaveBadge('Autosave active');
  updateDirtyBadge(false);

  setInterval(autosaveDraft, 20000);

  aiWriteBtn?.addEventListener('click', async () => {
    const selectedTemplate = String(aiTemplate?.value || 'custom');
    const goal = String(aiGoal?.value || '').trim() || String(aiGoalDefaults[selectedTemplate] || '');

    aiWriteBtn.disabled = true;
    if (aiWriteBtnLabel) {
      aiWriteBtnLabel.textContent = 'Generating...';
    }

    try {
      const response = await fetch('{{ route('admin.emails.ai-write') }}', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          goal,
          tone: aiTone?.value || 'professional',
          template: selectedTemplate,
          recipient_name: '{{ addslashes((string) $compose['recipient_name']) }}',
          context: String(composeMessage?.value || '').trim(),
        })
      });

      if (!response.ok) {
        throw new Error('AI write request failed.');
      }

      const data = await response.json();
      if (data.subject) {
        composeSubject.value = data.subject;
      }
      if (data.message) {
        composeMessage.value = data.message;
      }
      updatePreview();
    } catch (error) {
      alert('AI writing is temporarily unavailable. Please try again.');
    } finally {
      aiWriteBtn.disabled = false;
      if (aiWriteBtnLabel) {
        aiWriteBtnLabel.textContent = 'Generate with AI';
      }
    }
  });
})();
</script>

<style>
.crm-mailbox-v2 {
  --crm-ink: #0f2748;
  --crm-muted: #5d7493;
  --crm-line: #d7e3f1;
  --crm-soft: #f5f9ff;
  --crm-soft-2: #fbfdff;
  --crm-accent: #173f70;
  --crm-danger: #b91c2f;
}
.crm-mailbox-hero {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  gap: 16px;
  border: 1px solid #ccdaeb;
  background:
    radial-gradient(110% 160% at 100% 0%, rgba(45, 111, 193, .18) 0%, rgba(45, 111, 193, 0) 58%),
    linear-gradient(145deg, #f8fbff 0%, #f1f7ff 100%);
}
.crm-mailbox-eyebrow {
  margin: 0 0 8px;
  color: #2b5689;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: .11em;
  text-transform: uppercase;
}
.crm-mailbox-title {
  margin: 0;
  color: var(--crm-ink);
  font-size: 28px;
  line-height: 1.2;
  letter-spacing: -.02em;
}
.crm-mailbox-sub {
  margin: 10px 0 0;
  color: var(--crm-muted);
  line-height: 1.55;
}
.crm-mailbox-hero__stats {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 10px;
}
.crm-mailbox-stat {
  border: 1px solid #cfdced;
  border-radius: 12px;
  padding: 11px;
  background: #fff;
  box-shadow: 0 8px 16px rgba(16, 34, 62, .05);
}
.crm-mailbox-stat__label {
  display: block;
  color: #5f7698;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: .05em;
  margin-bottom: 7px;
}
.crm-mailbox-stat__value {
  color: var(--crm-ink);
  font-size: 28px;
  line-height: 1;
}
.crm-mailbox-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  gap: 14px;
}
.crm-mailbox-left {
  position: sticky;
  top: 12px;
  align-self: start;
  border: 1px solid #cfdded;
  background: linear-gradient(180deg, #ffffff 0%, #f7fbff 100%);
}
.crm-mailbox-section-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}
.crm-mailbox-section-head .panel-section-title {
  margin: 0;
}
.crm-mailbox-subtitle {
  margin: 0;
  font-size: 1rem;
  color: #1b3c65;
}
.crm-folder-list {
  display: grid;
  gap: 8px;
}
.crm-folder-link {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border: 1px solid #d5e0ee;
  border-radius: 10px;
  padding: 9px 11px;
  text-decoration: none;
  color: #1f3554;
  font-weight: 600;
  background: #fff;
  transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease, background .18s ease;
}
.crm-folder-link:hover {
  transform: translateY(-1px);
  border-color: #b7cae2;
  box-shadow: 0 7px 14px rgba(15, 39, 72, .08);
}
.crm-folder-link-main {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}
.crm-folder-compose {
  border-color: #c4d8f4;
  color: #18406f;
  background: linear-gradient(180deg, #f8fbff 0%, #edf5ff 100%);
}
.crm-folder-compose:hover {
  border-color: #aac8ec;
}
.crm-mailbox-hero__actions {
  margin-top: 14px;
}
.crm-folder-link.is-active {
  background: linear-gradient(180deg, #eaf3ff 0%, #dfeeff 100%);
  border-color: #a7c0e3;
  color: #123a68;
}
.crm-ui-icon {
  width: 15px;
  height: 15px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}
.crm-ui-icon svg {
  width: 15px;
  height: 15px;
}
.crm-btn-icon {
  display: inline-flex;
  align-items: center;
  gap: 7px;
}
.crm-quick-template-card {
  border: 1px solid #d4dfed;
  border-radius: 10px;
  padding: 10px;
  display: grid;
  gap: 8px;
  background: #fff;
}
.crm-quick-template-card h4 {
  margin: 0;
  color: #123a68;
}
.crm-mailbox-main-card {
  border: 1px solid #d1deee;
  background: linear-gradient(180deg, #ffffff 0%, #f9fcff 100%);
  gap: 10px;
  align-content: start;
}
.crm-mailbox-main-head {
  margin-bottom: 0;
}
.crm-mail-tabs {
  display: flex !important;
  flex-direction: row !important;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px;
  margin: 0;
  padding: 6px;
  border: 1px solid #d5e1ef;
  border-radius: 12px;
  background: #f8fbff;
}
.crm-mail-tab {
  display: inline-flex !important;
  flex: 0 0 auto !important;
  align-items: center;
  justify-content: center;
  gap: 8px;
  min-height: 36px;
  padding: 6px 12px;
  border-radius: 999px;
  text-decoration: none;
  color: #28466f;
  font-weight: 700;
  font-size: 0.88rem;
  line-height: 1;
}
.crm-mail-tab span {
  display: inline-flex;
  min-width: 20px;
  height: 20px;
  border-radius: 999px;
  align-items: center;
  justify-content: center;
  font-size: 0.72rem;
  background: #e8f0fb;
  color: #173d6b;
}
.crm-mail-tab.is-active {
  background: #dfeeff;
  color: #143a68;
}
.crm-mailbox-table td {
  vertical-align: top;
}
.crm-mailbox-table {
  table-layout: fixed;
}
.crm-mailbox-table th,
.crm-mailbox-table td {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.crm-mailbox-table tbody tr {
  transition: background-color .15s ease;
}
.crm-mailbox-table tbody tr:hover {
  background: #f2f7ff;
}
.crm-mailbox-table th,
.crm-mailbox-table td {
  padding-top: 8px;
  padding-bottom: 8px;
}
.crm-cell-truncate {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.crm-mail-actions {
  white-space: nowrap;
  width: 118px;
  text-align: right;
  display: inline-flex;
  align-items: center;
  justify-content: flex-end;
  gap: 8px;
  overflow: visible !important;
  text-overflow: clip !important;
}
.crm-mail-actions form {
  display: inline-flex;
  margin: 0;
}
.crm-mail-actions--draft {
  width: auto;
  min-width: 260px;
}
.crm-mail-actions .panel-btn,
.crm-mail-actions .panel-link {
  min-width: 29px;
  width: 29px;
  height: 29px;
  padding: 0;
  justify-content: center;
  border-radius: 10px;
}
.crm-mailbox-split {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  gap: 8px;
  align-items: start;
  min-height: 0;
}
.crm-mailbox-list-col {
  min-width: 0;
  border: 1px solid #d6e1ef;
  border-radius: 12px;
  background: #fff;
  padding: 8px;
  display: grid;
  gap: 8px;
  align-content: start;
}
.crm-mailbox-list-col .panel-table-wrap {
  margin: 0;
  border: 1px solid #e2eaf5;
  border-radius: 10px;
  overflow: auto;
}
.crm-mailbox-detail-col {
  position: sticky;
  top: 12px;
  max-height: calc(100vh - 88px);
  overflow: auto;
  align-self: start;
}
.crm-mail-row.is-active {
  background: #edf5ff;
}
.crm-compose-badges {
  display: flex;
  gap: 8px;
  align-items: center;
  flex-wrap: wrap;
}
.crm-compose-focus {
  border-color: #87a9d2;
  box-shadow: 0 0 0 2px rgba(120, 161, 211, .28), 0 10px 28px rgba(16, 34, 62, .14);
}
.crm-badge-warn {
  background: #fff5d6;
  color: #6b4c00;
  border: 1px solid #efd68a;
}
.crm-timeline-mini {
  display: grid;
  gap: 5px;
  font-size: .78rem;
}
.crm-thread-pane {
  border: 1px solid #d4e0ef;
  border-radius: 12px;
  margin-top: 0;
  padding: 12px;
  display: grid;
  gap: 10px;
  background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
}
.crm-thread-pane__header {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  gap: 10px;
}
.crm-thread-pane__header h3 {
  margin: 0;
  font-size: 1rem;
}
.crm-thread-pane__header p {
  margin: 0;
}
.crm-thread-events {
  display: grid;
  gap: 5px;
  border: 1px dashed #ccd5e4;
  border-radius: 10px;
  padding: 8px;
  font-size: .82rem;
}
.crm-thread-list {
  display: grid;
  gap: 10px;
  max-height: 460px;
  overflow: auto;
}
.crm-thread-message {
  border: 1px solid #d8deea;
  border-radius: 10px;
  padding: 10px;
  display: grid;
  gap: 7px;
  background: #fff;
}
.crm-thread-message.is-selected {
  border-color: #a7c0e3;
  box-shadow: inset 0 0 0 1px #c9dcf6;
}
.crm-thread-message__meta {
  display: flex;
  justify-content: space-between;
  font-size: .82rem;
  color: #375374;
}
.crm-thread-message__from,
.crm-thread-message__to {
  margin: 0;
  font-size: .84rem;
  color: #2f4664;
}
.crm-thread-message h4 {
  margin: 0;
  font-size: .95rem;
}
.crm-thread-message pre {
  margin: 0;
  white-space: pre-wrap;
  word-break: break-word;
  font-family: inherit;
  font-size: .9rem;
  line-height: 1.45;
}
.crm-compose-card {
  --crm-control-h: 44px;
  border: 1px solid #ceddec;
  background:
    radial-gradient(120% 200% at 100% 0%, rgba(38, 94, 167, .1) 0%, rgba(38, 94, 167, 0) 55%),
    linear-gradient(180deg, #ffffff 0%, #f6faff 100%);
}
.crm-compose-ai-panel {
  border: 1px solid #d2e0f1;
  border-radius: 12px;
  padding: 12px;
  background: linear-gradient(180deg, #f9fcff 0%, #f2f8ff 100%);
}
.crm-compose-ai-row {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
  gap: 12px;
  align-items: end;
  margin-bottom: 0;
}
.crm-compose-form {
  border: 1px solid #dbe6f3;
  border-radius: 12px;
  padding: 12px;
  background: #fff;
}
.crm-compose-grid-2 {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
  gap: 12px;
  align-items: end;
}
.crm-compose-section-head {
  display: grid;
  gap: 3px;
  margin-bottom: 2px;
}
.crm-compose-section-head h3 {
  margin: 0;
  font-size: .98rem;
  color: #163b67;
}
.crm-compose-section-head p {
  margin: 0;
  font-size: .82rem;
  color: #5c7495;
}
.crm-ai-write-btn {
  box-shadow: 0 10px 18px rgba(15, 59, 113, .22);
  width: 100%;
  min-height: var(--crm-control-h);
  justify-content: center;
}
.crm-compose-ai-row > label,
.crm-compose-grid-2 > label {
  display: grid;
  gap: 6px;
  min-width: 0;
  align-content: start;
}
.crm-compose-form label {
  display: grid;
  gap: 8px;
  min-width: 0;
}
.crm-compose-card label > span {
  min-height: 20px;
}
.crm-compose-form label > span {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-weight: 700;
  color: #1b3d66;
}
.crm-compose-card .panel-input,
.crm-compose-card .panel-select {
  height: var(--crm-control-h);
  line-height: 1.2;
}
.crm-compose-card .panel-input,
.crm-compose-card .panel-select,
.crm-compose-card .panel-textarea {
  width: 100%;
  min-width: 0;
  box-sizing: border-box;
}
.crm-compose-form .panel-input,
.crm-compose-form .panel-select,
.crm-compose-form .panel-textarea {
  border-color: #c8d9ee;
}
.crm-compose-form .panel-textarea {
  min-height: 180px;
}
.crm-compose-form label > small.panel-muted {
  display: block;
  margin-top: -2px;
  margin-bottom: 6px;
  line-height: 1.35;
}
.crm-compose-actions {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}
.crm-compose-actions .panel-btn {
  min-height: var(--crm-control-h);
}
.crm-compose-modal {
  position: fixed;
  inset: 0;
  z-index: 1200;
  display: none;
}
.crm-compose-modal.is-open {
  display: block;
}
.crm-compose-modal__backdrop {
  position: absolute;
  inset: 0;
  background: rgba(10, 23, 44, .55);
  backdrop-filter: blur(2px);
}
.crm-compose-modal__card {
  position: relative;
  margin: 24px auto;
  width: min(1080px, calc(100% - 28px));
  max-height: calc(100vh - 48px);
  overflow: auto;
  box-shadow: 0 24px 48px rgba(10, 23, 44, .28);
}
.crm-detail-modal {
  position: fixed;
  inset: 0;
  z-index: 1225;
  display: none;
}
.crm-detail-modal.is-open {
  display: block;
}
.crm-detail-modal__backdrop {
  position: absolute;
  inset: 0;
  background: rgba(10, 23, 44, .55);
  backdrop-filter: blur(2px);
}
.crm-detail-modal__card {
  position: relative;
  margin: 24px auto;
  width: min(920px, calc(100% - 28px));
  max-height: calc(100vh - 48px);
  overflow: auto;
  box-shadow: 0 24px 48px rgba(10, 23, 44, .28);
}
.crm-confirm-modal {
  position: fixed;
  inset: 0;
  z-index: 1260;
  display: none;
}
.crm-confirm-modal.is-open {
  display: block;
}
.crm-confirm-modal__backdrop {
  position: absolute;
  inset: 0;
  background: rgba(9, 20, 39, .48);
}
.crm-confirm-modal__card {
  position: relative;
  width: min(460px, calc(100% - 24px));
  margin: 12vh auto 0;
  border: 1px solid #c9d9ed;
  background: linear-gradient(180deg, #ffffff 0%, #f7fbff 100%);
  box-shadow: 0 20px 42px rgba(12, 29, 54, .24);
}
body.crm-compose-modal-open {
  overflow: hidden;
}
.crm-compose-head {
  margin-bottom: 2px;
}
.crm-compose-card .panel-field,
.crm-compose-card label {
  min-width: 0;
}
@media (max-width: 1280px) {
  .crm-mailbox-split {
    grid-template-columns: 1fr;
  }
  .crm-mailbox-detail-col {
    position: static;
    max-height: none;
    overflow: visible;
  }
}

@media (max-width: 1100px) {
  .crm-mailbox-hero {
    grid-template-columns: 1fr;
  }
  .crm-mailbox-grid {
    grid-template-columns: 1fr;
  }
  .crm-mailbox-left {
    position: static;
  }
  .crm-mailbox-split {
    grid-template-columns: 1fr;
  }
  .crm-mailbox-detail-col {
    position: static;
    max-height: none;
    overflow: visible;
  }
}
@media (max-width: 640px) {
  .crm-mailbox-title {
    font-size: 22px;
  }
  .crm-mailbox-hero__stats {
    grid-template-columns: 1fr;
  }
  .crm-compose-modal__card {
    margin: 10px auto;
    width: calc(100% - 12px);
    max-height: calc(100vh - 20px);
  }
  .crm-detail-modal__card {
    margin: 10px auto;
    width: calc(100% - 12px);
    max-height: calc(100vh - 20px);
  }
  .crm-mail-tabs {
    flex-wrap: wrap;
  }
  .crm-compose-ai-row {
    align-items: stretch !important;
  }
}
@media (max-width: 900px) {
  .crm-compose-ai-row,
  .crm-compose-grid-2 {
    grid-template-columns: 1fr;
  }
}
</style>
@endsection
