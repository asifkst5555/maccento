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
@endphp

<section class="crm-mailbox-v2 panel-stack">
  <article class="panel-card crm-mailbox-hero">
    <div class="crm-mailbox-hero__copy">
      <p class="crm-mailbox-eyebrow">Email Operations Hub</p>
      <h2 class="crm-mailbox-title">Unified inbox, sent timeline, drafts, and AI compose workspace</h2>
      <p class="crm-mailbox-sub">Track message flow, review thread context, and send polished client communication from a single controlled environment.</p>
    </div>
    <div class="crm-mailbox-hero__stats" aria-label="Mailbox summary">
      <article class="crm-mailbox-stat">
        <span class="crm-mailbox-stat__label">Inbox</span>
        <strong class="crm-mailbox-stat__value">{{ number_format($inboxCount) }}</strong>
      </article>
      <article class="crm-mailbox-stat">
        <span class="crm-mailbox-stat__label">Sent</span>
        <strong class="crm-mailbox-stat__value">{{ number_format($sentCount) }}</strong>
      </article>
      <article class="crm-mailbox-stat">
        <span class="crm-mailbox-stat__label">Drafts</span>
        <strong class="crm-mailbox-stat__value">{{ number_format($draftCount) }}</strong>
      </article>
    </div>
  </article>

  <section class="crm-mailbox-grid">
  <aside class="panel-card panel-stack crm-mailbox-left">
    <div class="crm-mailbox-section-head">
      <h2 class="panel-section-title">Folders & Templates</h2>
      <span class="panel-badge">{{ number_format($totalItems) }} items</span>
    </div>
    <nav class="crm-folder-list">
      <a class="crm-folder-link @if($activeFolder === 'inbox') is-active @endif" href="{{ route('admin.emails.inbox') }}">
        <span class="crm-folder-link-main"><span class="crm-ui-icon"><svg viewBox="0 0 20 20" aria-hidden="true"><path d="M3 6a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6zm2 .4v1.2l5 3.2 5-3.2V6.4l-5 3.1-5-3.1z" fill="currentColor"/></svg></span>Inbox</span>
        <span>{{ number_format((int) ($folderCounts['inbox'] ?? 0)) }}</span>
      </a>
      <a class="crm-folder-link @if($activeFolder === 'sent') is-active @endif" href="{{ route('admin.emails.sent') }}">
        <span class="crm-folder-link-main"><span class="crm-ui-icon"><svg viewBox="0 0 20 20" aria-hidden="true"><path d="M3 10l13-6-3.4 12-3.1-4.1L6 14l-3-4z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg></span>Sent</span>
        <span>{{ number_format((int) ($folderCounts['sent'] ?? 0)) }}</span>
      </a>
      <a class="crm-folder-link @if($activeFolder === 'drafts') is-active @endif" href="{{ route('admin.emails.drafts') }}">
        <span class="crm-folder-link-main"><span class="crm-ui-icon"><svg viewBox="0 0 20 20" aria-hidden="true"><path d="M4 14.8h2.4L14 7.2 11.8 5 4.2 12.6V15zM10.9 6l2.2 2.2" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>Drafts</span>
        <span>{{ number_format((int) ($folderCounts['drafts'] ?? 0)) }}</span>
      </a>
      <a class="crm-folder-link crm-folder-compose" href="{{ route('admin.emails.inbox', ['compose' => 1]) }}#composeCard">
        <span class="crm-folder-link-main"><span class="crm-ui-icon"><svg viewBox="0 0 20 20" aria-hidden="true"><path d="M10 4v12M4 10h12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>Compose</span>
        <span>New</span>
      </a>
    </nav>

    <div class="panel-stack">
      <h3 class="crm-mailbox-subtitle">Quick Send Templates</h3>
      @foreach($quickTemplates as $template)
      <form method="post" action="{{ route('admin.emails.send') }}" class="crm-quick-template-card">
        @csrf
        <input type="hidden" name="mode" value="template">
        <input type="hidden" name="template_key" value="{{ $template['key'] }}">
        <input class="panel-input" type="email" name="recipient_email" value="{{ old('recipient_email', $defaultRecipient) }}" required>
        <input type="hidden" name="reply_to" value="{{ old('reply_to', $defaultReplyTo ?? $defaultRecipient) }}">
        <h4>{{ $template['title'] }}</h4>
        <p class="panel-muted">{{ $template['description'] }}</p>
        <p class="panel-muted" style="margin:0; font-size:.78rem;">Subject: {{ $template['subject_preview'] ?: '-' }}</p>
        <button class="panel-btn crm-btn-icon" type="submit"><span class="crm-ui-icon"><svg viewBox="0 0 20 20" aria-hidden="true"><path d="M3 10l13-6-3.4 12-3.1-4.1L6 14l-3-4z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg></span>Send</button>
      </form>
      @endforeach
    </div>
  </aside>

  <section class="panel-stack">
    <article class="panel-card panel-stack crm-mailbox-main-card">
      <div class="panel-form-row crm-mailbox-main-head" style="justify-content:space-between; align-items:center;">
        <h2 class="panel-section-title" style="margin:0; text-transform:capitalize;">{{ $activeFolder }}</h2>
        <span class="panel-badge">{{ number_format((int) $mailboxItems->total()) }} items</span>
      </div>

      <div class="crm-mailbox-split">
        <section class="crm-mailbox-list-col">
          <div class="panel-table-wrap">
            @php($baseQuery = request()->query())
            <table class="panel-table crm-mailbox-table">
              @if($activeFolder === 'inbox')
              <thead><tr><th>From</th><th>Subject</th><th>Linked CRM</th><th>Received</th></tr></thead>
              <tbody>
                @forelse($mailboxItems as $item)
                <tr class="crm-mail-row @if((int) $openMessageId === (int) $item->id) is-active @endif" data-open-href="{{ route('admin.emails.inbox', array_merge($baseQuery, ['open_id' => $item->id])) }}">
                  <td>
                    <p style="margin:0;">{{ $item->from_name ?: $item->from_email }}</p>
                    <p class="panel-muted" style="margin:4px 0 0;">{{ $item->from_email }}</p>
                  </td>
                  <td>
                    <p style="margin:0;">{{ $item->subject ?: '(No subject)' }}</p>
                    <p class="panel-muted" style="margin:4px 0 0;">{{ \Illuminate\Support\Str::limit((string) ($item->body_text ?? ''), 130) }}</p>
                  </td>
                  <td>
                    @if($item->client)
                    <p style="margin:0;">{{ $item->client->name }}</p>
                    <p class="panel-muted" style="margin:4px 0 0;">{{ $item->project?->title ?: 'No project linked' }}</p>
                    @else
                    <span class="panel-badge">Unmatched</span>
                    @endif
                  </td>
                  <td>{{ $item->received_at?->format('Y-m-d H:i') ?: ($item->created_at?->format('Y-m-d H:i') ?: '-') }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="panel-muted">No inbound emails yet. Configure SendGrid inbound parse to populate Inbox.</td></tr>
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
                  <td>
                    <a class="panel-link crm-btn-icon" href="{{ route('admin.emails.drafts', ['draft' => $item->id]) }}"><span class="crm-ui-icon"><svg viewBox="0 0 20 20" aria-hidden="true"><path d="M4 14.8h2.4L14 7.2 11.8 5 4.2 12.6V15zM10.9 6l2.2 2.2" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>Edit</a>
                    <form method="post" action="{{ route('admin.emails.drafts.send', $item) }}" style="display:inline-block; margin-left:8px;">
                      @csrf
                      <button class="panel-btn crm-btn-icon" type="submit"><span class="crm-ui-icon"><svg viewBox="0 0 20 20" aria-hidden="true"><path d="M3 10l13-6-3.4 12-3.1-4.1L6 14l-3-4z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg></span>Send</button>
                    </form>
                    <form method="post" action="{{ route('admin.emails.drafts.delete', $item) }}" style="display:inline-block; margin-left:8px;" onsubmit="return confirm('Delete this draft?');">
                      @csrf
                      <button class="panel-btn panel-btn-danger crm-btn-icon" type="submit"><span class="crm-ui-icon"><svg viewBox="0 0 20 20" aria-hidden="true"><path d="M5 6h10M8 6V4h4v2m-6 0l.5 9h7L14 6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>Delete</button>
                    </form>
                  </td>
                </tr>
                @empty
                <tr><td colspan="4" class="panel-muted">No drafts yet.</td></tr>
                @endforelse
              </tbody>
              @else
              <thead><tr><th>Recipient</th><th>Subject</th><th>Status</th><th>Sent</th><th>Provider Timeline</th></tr></thead>
              <tbody>
                @forelse($mailboxItems as $item)
                @php($timeline = $emailEventTimeline->get($item->id, collect()))
                <tr class="crm-mail-row @if((int) $openMessageId === (int) $item->id) is-active @endif" data-open-href="{{ route('admin.emails.sent', array_merge($baseQuery, ['open_id' => $item->id])) }}">
                  <td>{{ $item->recipient_email }}</td>
                  <td>
                    <p style="margin:0;">{{ $item->subject }}</p>
                    <p class="panel-muted" style="margin:4px 0 0;">{{ \Illuminate\Support\Str::limit((string) ($item->body_preview ?? ''), 110) }}</p>
                  </td>
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
                </tr>
                @empty
                <tr><td colspan="5" class="panel-muted">No sent emails yet.</td></tr>
                @endforelse
              </tbody>
              @endif
            </table>
          </div>
          <x-panel-pagination :paginator="$mailboxItems" />
        </section>

        <aside class="crm-mailbox-detail-col">
          <section class="crm-thread-pane">
            @if($selectedMessage)
            <div class="crm-thread-pane__header">
              <h3>{{ data_get($threadMessages->first(), 'subject', 'Message detail') }}</h3>
              <p class="panel-muted">Full thread view</p>
            </div>

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
            <p class="panel-muted" style="margin:0;">Click any row to open full message detail and related thread context.</p>
            @endif
          </section>
        </aside>
      </div>
    </article>

    <article id="composeCard" class="panel-card panel-stack crm-compose-card">
      <div class="panel-form-row crm-compose-head" style="justify-content:space-between; align-items:center;">
        <h2 class="panel-section-title" style="margin:0;">Compose</h2>
        <div class="crm-compose-badges">
          <span class="panel-badge">OpenRouter AI Assist</span>
          <span id="autosaveBadge" class="panel-badge">Autosave idle</span>
          <span id="autosaveDirtyBadge" class="panel-badge">All changes saved</span>
        </div>
      </div>

      <div class="panel-form-row" style="align-items:flex-end;">
        <label>
          <span>Template</span>
          <select id="aiTemplate" class="panel-select">
            <option value="custom" @selected((string) ($compose['ai_template'] ?? 'custom') === 'custom')>Custom</option>
            <option value="cold_followup" @selected((string) ($compose['ai_template'] ?? 'custom') === 'cold_followup')>Cold Follow-up</option>
            <option value="quote_reminder" @selected((string) ($compose['ai_template'] ?? 'custom') === 'quote_reminder')>Quote Reminder</option>
            <option value="no_response_nudge" @selected((string) ($compose['ai_template'] ?? 'custom') === 'no_response_nudge')>No-response Nudge</option>
          </select>
        </label>
        <label style="flex:2;">
          <span>AI Goal</span>
          <input id="aiGoal" class="panel-input" type="text" value="{{ (string) ($compose['ai_goal'] ?? '') }}" placeholder="Example: follow up with a qualified lead and ask for availability">
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
        <button id="aiWriteBtn" class="panel-btn crm-btn-icon" type="button"><span class="crm-ui-icon"><svg viewBox="0 0 20 20" aria-hidden="true"><path d="M10 2l1.8 3.8L16 7l-3.1 3 0.7 4.2L10 12.2l-3.6 2 0.7-4.2L4 7l4.2-1.2L10 2z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg></span>AI Write</button>
      </div>

      <form id="composeForm" method="post" action="{{ route('admin.emails.send') }}" class="panel-stack">
        @csrf
        <input type="hidden" name="mode" value="custom">
        <input id="composeDraftId" type="hidden" name="draft_id" value="{{ $compose['draft_id'] }}">

        <div class="panel-form-row">
          <label>
            <span>To</span>
            <input id="composeTo" class="panel-input" type="email" name="recipient_email" value="{{ $compose['recipient_email'] }}" required>
          </label>
          <label>
            <span>Reply-to</span>
            <input class="panel-input" type="email" name="reply_to" value="{{ $compose['reply_to'] }}">
          </label>
        </div>

        <div class="panel-form-row">
          <label>
            <span>CC</span>
            <input class="panel-input" type="text" name="cc" value="{{ $compose['cc'] }}" placeholder="team@maccento.ca">
          </label>
          <label>
            <span>BCC</span>
            <input class="panel-input" type="text" name="bcc" value="{{ $compose['bcc'] }}" placeholder="archive@maccento.ca">
          </label>
        </div>

        <label>
          <span>Thread Project</span>
          <select id="composeProject" class="panel-select" name="client_project_id">
            <option value="">Auto-detect from recipient (if exactly one)</option>
            @foreach($projectOptions as $projectOption)
            <option value="{{ $projectOption['id'] }}" @selected((string) $compose['client_project_id'] === (string) $projectOption['id'])>{{ $projectOption['label'] }}</option>
            @endforeach
          </select>
        </label>

        <label>
          <span>Subject</span>
          <input id="composeSubject" class="panel-input" type="text" name="subject" value="{{ $compose['subject'] }}" maxlength="180" required>
          <small class="panel-muted">Final outgoing subject: <strong id="composeSubjectPreview">-</strong></small>
        </label>

        <label>
          <span>Message</span>
          <textarea id="composeMessage" class="panel-textarea" name="message" rows="10" required>{{ $compose['message'] }}</textarea>
        </label>

        <div class="panel-form-row" style="justify-content:space-between; align-items:center;">
          <div>
            <button id="manualSaveDraftBtn" class="panel-btn crm-btn-icon" formaction="{{ route('admin.emails.drafts.save') }}" formmethod="post" type="submit"><span class="crm-ui-icon"><svg viewBox="0 0 20 20" aria-hidden="true"><path d="M4 4h10l2 2v10H4V4zm2 0v4h8V5.2L12.8 4H6zm1 9h6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg></span>Save Draft</button>
            <button class="panel-btn panel-btn-primary crm-btn-icon" type="submit" style="margin-left:8px;"><span class="crm-ui-icon"><svg viewBox="0 0 20 20" aria-hidden="true"><path d="M3 10l13-6-3.4 12-3.1-4.1L6 14l-3-4z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg></span>Send Email</button>
          </div>
          <span id="autosaveStatus" class="panel-muted">Lead ID: {{ $compose['lead_id'] !== '' ? $compose['lead_id'] : 'n/a' }}</span>
        </div>
      </form>
    </article>
  </section>
</section>
</section>

<script>
(function () {
  const projectOptions = @json($projectOptions);
  const composeTo = document.getElementById('composeTo');
  const composeProject = document.getElementById('composeProject');
  const composeSubject = document.getElementById('composeSubject');
  const composeSubjectPreview = document.getElementById('composeSubjectPreview');
  const composeForm = document.getElementById('composeForm');
  const composeDraftId = document.getElementById('composeDraftId');
  const manualSaveDraftBtn = document.getElementById('manualSaveDraftBtn');
  const sendEmailBtn = composeForm ? composeForm.querySelector('button.panel-btn.panel-btn-primary[type="submit"]') : null;
  const aiWriteBtn = document.getElementById('aiWriteBtn');
  const aiTemplate = document.getElementById('aiTemplate');
  const aiGoal = document.getElementById('aiGoal');
  const aiTone = document.getElementById('aiTone');
  const composeMessage = document.getElementById('composeMessage');
  const autosaveStatus = document.getElementById('autosaveStatus');
  const autosaveBadge = document.getElementById('autosaveBadge');
  const autosaveDirtyBadge = document.getElementById('autosaveDirtyBadge');
  const rowLinks = Array.from(document.querySelectorAll('.crm-mail-row[data-open-href]'));
  const composeCard = document.getElementById('composeCard');

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

  if (window.location.search.indexOf('compose=1') !== -1 && composeCard) {
    composeCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
    composeCard.classList.add('crm-compose-focus');
    setTimeout(() => composeCard.classList.remove('crm-compose-focus'), 1600);
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
    aiWriteBtn.textContent = 'Writing...';

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
      aiWriteBtn.textContent = 'AI Write';
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
  grid-template-columns: minmax(0, 1.35fr) minmax(280px, .65fr);
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
  grid-template-columns: 300px minmax(0, 1fr);
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
}
.crm-mailbox-main-head {
  margin-bottom: 2px;
}
.crm-mailbox-table td {
  vertical-align: top;
}
.crm-mailbox-split {
  display: grid;
  grid-template-columns: minmax(0, 1.35fr) minmax(320px, 1fr);
  gap: 12px;
  align-items: start;
}
.crm-mailbox-list-col {
  min-width: 0;
  border: 1px solid #d6e1ef;
  border-radius: 12px;
  background: #fff;
  padding: 10px;
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
  border: 1px solid #ceddec;
  background:
    radial-gradient(120% 200% at 100% 0%, rgba(38, 94, 167, .1) 0%, rgba(38, 94, 167, 0) 55%),
    linear-gradient(180deg, #ffffff 0%, #f6faff 100%);
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
}
</style>
@endsection
