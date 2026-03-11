@extends('layouts.panel', [
  'title' => $project->title,
  'heading' => $project->title,
  'subheading' => 'Project workspace with delivery, billing, and communication history.',
])

@section('content')
@php
  $galleryItems = $project->media->whereIn('type', ['image', 'video'])->values();
  $zipItems = $project->media->where('type', 'final_zip')->values();
@endphp
<style>
  .project-discussion-card {
    display: grid;
    gap: 0.9rem;
  }

  .project-discussion-stream {
    display: grid;
    gap: 0.85rem;
    max-height: 420px;
    overflow-y: auto;
    padding-right: 0.25rem;
  }

  .project-comment-card {
    border: 1px solid #dbe3ee;
    border-radius: 18px;
    padding: 0.95rem 1.05rem;
    background: #ffffff;
    box-shadow: 0 10px 24px rgba(18, 35, 58, 0.08);
  }

  .project-comment-card.is-internal {
    border-color: rgba(194, 31, 55, 0.28);
    background: #fff8f9;
  }

  .project-comment-head {
    display: flex;
    justify-content: space-between;
    gap: 0.75rem;
    align-items: flex-start;
  }

  .project-comment-author {
    display: flex;
    gap: 0.7rem;
    align-items: center;
  }

  .project-comment-avatar {
    width: 42px;
    height: 42px;
    border-radius: 999px;
    background: linear-gradient(140deg, #b71c2d, #d23b4f);
    color: #ffffff;
    display: grid;
    place-items: center;
    font-weight: 700;
    font-size: 0.85rem;
    box-shadow: 0 6px 14px rgba(183, 28, 45, 0.25);
  }

  .project-comment-name {
    margin: 0;
    font-weight: 700;
    color: #1c2b3d;
  }

  .project-comment-meta {
    margin: 0;
    font-size: 0.75rem;
    color: #5b6a7e;
  }

  .project-comment-time {
    font-size: 0.75rem;
    color: #5b6a7e;
    white-space: nowrap;
  }

  .project-comment-body {
    margin: 0.65rem 0;
    color: #1c2b3d;
    white-space: pre-wrap;
    padding: 0.7rem 0;
    border-top: 1px solid #e4e9f2;
    border-bottom: 1px solid #e4e9f2;
    line-height: 1.55;
  }

  .project-comment-actions {
    display: flex;
    align-items: center;
    gap: 1.1rem;
    padding-top: 0.35rem;
  }

  .project-comment-action {
    border: 0;
    background: transparent;
    color: #55657b;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0;
    cursor: pointer;
  }

  .project-comment-action:hover {
    color: #2c3f57;
  }

  .project-comment-action.is-danger {
    color: #b21f34;
  }

  .project-comment-action.is-danger:hover {
    color: #8f1628;
  }
</style>
<div class="client-portal-shell">
  <section class="panel-card client-portal-hero">
    <div class="client-portal-hero-head">
      <div>
        <span class="client-portal-eyebrow">Project Workspace</span>
        <h2 class="panel-section-title" style="margin-top: 12px;">{{ $project->title }}</h2>
        <p class="client-portal-summary">
          {{ $project->service_type ?: 'Service pending' }}
          @if(!blank($project->property_address))
            &bull; {{ $project->property_address }}
          @endif
          &bull; Status: {{ $project->status }}
        </p>
      </div>
      <div class="client-portal-actions">
        <a class="panel-btn" href="{{ route('user.deliveries.index') }}#project-{{ $project->id }}">Open Deliveries</a>
        <a class="panel-btn" href="{{ route('user.messages.index') }}">Messages</a>
        @if($project->quoteBuild)
          <a class="panel-btn panel-btn-primary" href="{{ route('user.quotes.show', $project->quoteBuild) }}">Linked Quote</a>
        @endif
      </div>
    </div>
  </section>

  <section class="panel-grid panel-grid-kpi-compact">
    <article class="client-portal-kpi">
      <span class="panel-kpi-label">Project Status</span>
      <p class="client-portal-kpi-value">{{ ucfirst($project->status ?: 'pending') }}</p>
    </article>
    <article class="client-portal-kpi">
      <span class="panel-kpi-label">Gallery Files</span>
      <p class="client-portal-kpi-value">{{ collect($galleryPayload)->count() }}</p>
    </article>
    <article class="client-portal-kpi">
      <span class="panel-kpi-label">Payment State</span>
      <p class="client-portal-kpi-value">{{ $canViewBilling ? ($isPaid ? 'Paid' : 'Pending') : 'Restricted' }}</p>
    </article>
  </section>

  <section class="client-portal-grid-two">
    <article class="panel-card client-portal-stack">
      <h2 class="panel-section-title">Project Details</h2>
      <div class="client-portal-detail-grid">
        <div class="client-portal-detail">
          <span class="client-portal-detail-label">Project ID</span>
          <p class="client-portal-detail-value">#{{ $project->id }}</p>
        </div>
        <div class="client-portal-detail">
          <span class="client-portal-detail-label">Schedule</span>
          <p class="client-portal-detail-value">{{ $project->scheduled_at?->format('Y-m-d H:i') ?: 'To be confirmed' }}</p>
        </div>
        <div class="client-portal-detail">
          <span class="client-portal-detail-label">Client</span>
          <p class="client-portal-detail-value">{{ $project->client?->name ?: '-' }}</p>
        </div>
      </div>
      @if(!blank($project->notes))
        <div class="client-portal-empty">{{ $project->notes }}</div>
      @endif
    </article>

    @if($canViewBilling)
    <article class="panel-card client-portal-stack">
      <h2 class="panel-section-title">Request Additional Service</h2>
      <p class="panel-muted">Need an add-on for this project? Send it directly to the team from this workspace.</p>
      <form method="post" action="{{ route('user.service-requests.store') }}" class="panel-stack">
        @csrf
        <input type="hidden" name="client_project_id" value="{{ $project->id }}">
        <input class="panel-input" type="text" name="requested_service" placeholder="Additional service request" required>
        <input class="panel-input" type="text" name="subject" placeholder="Subject (optional)">
        <input class="panel-input" type="date" name="preferred_date">
        <textarea class="panel-textarea" name="details" placeholder="Describe the request for this project"></textarea>
        <button class="panel-btn panel-btn-primary" type="submit">Send Project Request</button>
      </form>
    </article>
  @endif
  </section>

  <section class="panel-card client-media-workspace">
    <div class="panel-form-row media-project-header">
      <div class="media-project-meta">
        <h2 class="panel-section-title" style="margin: 0;">Project Media Delivery</h2>
        <p class="media-project-summary">Gallery: {{ $galleryItems->count() }} | Final ZIP: {{ $zipItems->count() }} | Payment: <strong>{{ $canViewBilling ? ($isPaid ? 'Paid' : 'Unpaid') : 'Restricted' }}</strong></p>
      </div>
      <div class="media-project-controls">
        @if($galleryItems->isNotEmpty())
          <button
            class="panel-btn panel-btn-primary"
            type="button"
            data-gallery-open
            data-project-id="{{ $project->id }}"
            data-gallery-items='@json($galleryPayload)'
          >
            View Media
          </button>
        @endif
        @if($zipItems->isNotEmpty() && $isPaid && $canViewBilling)
          <a class="panel-btn panel-btn-primary" href="{{ route('user.projects.media.download-zip', $project) }}">Download Final ZIP</a>
        @endif
      </div>
    </div>

    <div class="panel-grid media-delivery-files-grid">
      <section class="panel-card media-file-list-card">
        <h4 class="panel-section-title">Gallery Files</h4>
        <div class="media-file-list">
          @forelse($galleryItems as $mediaItem)
            <article class="media-file-row">
              <div class="media-file-meta">
                <span class="media-file-kind">{{ strtoupper($mediaItem->type) }}</span>
                <span class="media-file-name">{{ $mediaItem->original_name }}</span>
              </div>
              <div class="media-file-actions">
                <a class="panel-btn" href="{{ route('user.projects.media.preview', ['project' => $project, 'media' => $mediaItem]) }}" target="_blank" rel="noopener">Preview</a>
                @if($isPaid && $canViewBilling)
                  <a class="panel-btn" href="{{ route('user.projects.media.download', ['project' => $project, 'media' => $mediaItem]) }}">Download</a>
                @endif
              </div>
            </article>
          @empty
            <div class="client-portal-empty">No gallery files are available for this project yet.</div>
          @endforelse
        </div>
      </section>

      <section class="panel-card media-file-list-card">
        <h4 class="panel-section-title">Final Delivery ZIP</h4>
        <div class="media-file-list">
          @forelse($zipItems as $zipItem)
            <article class="media-file-row">
              <div class="media-file-meta">
                <span class="media-file-kind">ZIP</span>
                <span class="media-file-name">{{ $zipItem->original_name }}</span>
              </div>
              <div class="media-file-actions">
                @if($isPaid && $canViewBilling)
                  <a class="panel-btn panel-btn-primary" href="{{ route('user.projects.media.download', ['project' => $project, 'media' => $zipItem]) }}">Download ZIP</a>
                @else
                  @if($canViewBilling)
                  <span class="panel-badge">Unlocks after payment</span>
                @else
                  <span class="panel-badge">Billing contact only</span>
                @endif
                @endif
              </div>
            </article>
          @empty
            <div class="client-portal-empty">No final ZIP is uploaded for this project yet.</div>
          @endforelse
        </div>
      </section>
    </div>
  </section>

  @if($canViewBilling)
  <section class="client-portal-grid-two">
    <article class="panel-card client-portal-table">
      <h2 class="panel-section-title">Invoices</h2>
      <div class="panel-table-wrap">
        <table class="panel-table">
          <thead>
            <tr>
              <th>Invoice</th>
              <th>Amount</th>
              <th>Status</th>
              <th>Due Date</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            @forelse($project->invoices as $invoice)
              <tr>
                <td>{{ $invoice->invoice_number }}</td>
                <td>{{ number_format((float) $invoice->amount, 2) }} {{ $invoice->currency }}</td>
                <td><span class="panel-badge">{{ $invoice->status }}</span></td>
                <td>{{ $invoice->due_date?->format('Y-m-d') ?: '-' }}</td>
                <td><a class="panel-btn" href="{{ route('user.invoices.download', $invoice) }}">Download PDF</a></td>
              </tr>
            @empty
              <tr><td colspan="5" class="panel-muted">No invoices are linked to this project yet.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </article>

    <article class="panel-card client-portal-table">
      <h2 class="panel-section-title">Service Timeline</h2>
      <div class="panel-table-wrap">
        <table class="panel-table">
          <thead>
            <tr>
              <th>Service Request</th>
              <th>Status</th>
              <th>Preferred Date</th>
            </tr>
          </thead>
          <tbody>
            @forelse($project->serviceRequests as $requestItem)
              <tr>
                <td>
                  {{ $requestItem->requested_service }}
                  @if(!blank($requestItem->subject))
                    <div class="panel-muted">{{ $requestItem->subject }}</div>
                  @endif
                </td>
                <td><span class="panel-badge">{{ $requestItem->status }}</span></td>
                <td>{{ $requestItem->preferred_date?->format('Y-m-d') ?: '-' }}</td>
              </tr>
            @empty
              <tr><td colspan="3" class="panel-muted">No service activity is logged for this project yet.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </article>
  </section>
@endif

  <section class="project-team-grid">
    <article class="panel-card project-team-card">
      <h2 class="panel-section-title">Assigned Team</h2>
      <div class="project-assignee-list">
        @forelse($project->assignments as $assignment)
          <span class="project-assignee-chip">
            <span>{{ $assignment->user?->name ?: 'Unknown team member' }}</span>
            <span class="project-assignee-role">{{ ucfirst($assignment->user?->role ?: 'staff') }}</span>
          </span>
        @empty
          <span class="panel-muted">No team members are assigned to this project yet.</span>
        @endforelse
      </div>
    </article>

    <article class="panel-card project-discussion-card">
      <h2 class="panel-section-title">Project Discussion</h2>
      <div class="project-discussion-stream">
        @forelse($project->comments->sortBy('id') as $comment)
          <article class="project-comment-card {{ $comment->sender_role === 'client' ? '' : 'is-internal' }}" data-comment-card>
            <div class="project-comment-head">
              <div class="project-comment-author">
                <span class="project-comment-avatar">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($comment->user?->name ?: ($comment->sender_role ?: 'U'), 0, 2)) }}</span>
                <div class="project-comment-author-text">
                  <p class="project-comment-name">{{ $comment->user?->name ?: ucfirst($comment->sender_role) }}</p>
                  <p class="project-comment-meta">{{ ucfirst(str_replace('_', ' ', $comment->sender_role)) }}</p>
                </div>
              </div>
              <div class="project-comment-meta-right">
                <span class="project-comment-time">{{ $comment->created_at?->format('M j, Y g:i A') }}</span>
                @if($comment->edited_at)
                  <span class="project-comment-edited">Edited</span>
                @endif
              </div>
            </div>
            @if($comment->parent)
              <div class="project-comment-reply">
                <span class="project-comment-reply-label">Replying to {{ $comment->parent->user?->name ?: ucfirst($comment->parent->sender_role) }}</span>
                <span class="project-comment-reply-body">{{ \Illuminate\Support\Str::limit($comment->parent->body, 140) }}</span>
              </div>
            @endif
            <div class="project-comment-body" data-comment-body>{{ $comment->body }}</div>
            @php
              $canEditComment = (int) $comment->user_id === (int) auth()->id();
            @endphp
            <form method="post" action="{{ route('user.projects.comments.update', ['project' => $project, 'comment' => $comment]) }}" class="project-comment-edit-form" data-edit-form hidden>
              @csrf
              <textarea class="panel-textarea" name="body" required>{{ old('body', $comment->body) }}</textarea>
              <div class="project-comment-edit-actions">
                <button class="panel-btn panel-btn-primary" type="submit">Save</button>
                <button class="panel-btn" type="button" data-edit-cancel>Cancel</button>
              </div>
            </form>
            <div class="project-comment-actions">
              <button class="project-comment-action" type="button" data-reply-button data-reply-id="{{ $comment->id }}" data-reply-author="{{ $comment->user?->name ?: ucfirst($comment->sender_role) }}" data-reply-body="{{ \Illuminate\Support\Str::limit($comment->body, 160) }}">
                <span class="panel-icon" aria-hidden="true"><x-panel-icon name="reply" /></span>
                <span>Reply</span>
              </button>
              @if($canEditComment)
                <button class="project-comment-action" type="button" data-edit-open>
                  <span class="panel-icon" aria-hidden="true"><x-panel-icon name="pencil" /></span>
                  <span>Edit</span>
                </button>
              @endif
              @if($canEditComment)
                <form method="post" action="{{ route('user.projects.comments.delete', ['project' => $project, 'comment' => $comment]) }}" data-app-confirm="1" data-confirm-message="Delete this comment?" style="margin:0;">
                  @csrf
                  <button class="project-comment-action is-danger" type="submit" title="Delete comment" aria-label="Delete comment">
                    <span class="panel-icon-trash" aria-hidden="true"><x-panel-icon name="trash" /></span>
                    <span>Delete</span>
                  </button>
                </form>
              @endif
            </div>
          </article>
        @empty
          <div class="client-portal-empty">No project discussion is available yet.</div>
        @endforelse
      </div>

      <form method="post" action="{{ route('user.projects.comments.store', $project) }}" class="project-discussion-form">
        @csrf
        <input type="hidden" name="parent_comment_id" value="" data-reply-input>
        <div class="project-comment-reply-banner" data-reply-banner hidden>
          <div>
            <strong>Replying to <span data-reply-author></span></strong>
            <div class="project-comment-reply-preview" data-reply-preview></div>
          </div>
          <button class="project-comment-reply-cancel" type="button" data-reply-cancel>Cancel</button>
        </div>
        <textarea class="panel-textarea" name="body" placeholder="Write a comment for the project team" required>{{ old('body') }}</textarea>
        <button class="panel-btn panel-btn-primary" type="submit">Post Comment</button>
      </form>

      <script>
        (function () {
          var commentForm = document.querySelector('.project-discussion-form');
          if (!commentForm) return;

          var replyInput = commentForm.querySelector('[data-reply-input]');
          var replyBanner = commentForm.querySelector('[data-reply-banner]');
          var replyAuthor = commentForm.querySelector('[data-reply-author]');
          var replyPreview = commentForm.querySelector('[data-reply-preview]');
          var replyCancel = commentForm.querySelector('[data-reply-cancel]');
          var commentTextarea = commentForm.querySelector('textarea[name="body"]');

          document.querySelectorAll('[data-reply-button]').forEach(function (btn) {
            btn.addEventListener('click', function () {
              var id = btn.getAttribute('data-reply-id') || '';
              if (replyInput) replyInput.value = id;
              if (replyBanner) replyBanner.hidden = false;
              if (replyAuthor) replyAuthor.textContent = btn.getAttribute('data-reply-author') || '';
              if (replyPreview) replyPreview.textContent = btn.getAttribute('data-reply-body') || '';
              if (commentTextarea) commentTextarea.focus();
            });
          });

          if (replyCancel) {
            replyCancel.addEventListener('click', function () {
              if (replyInput) replyInput.value = '';
              if (replyBanner) replyBanner.hidden = true;
              if (replyAuthor) replyAuthor.textContent = '';
              if (replyPreview) replyPreview.textContent = '';
            });
          }

          document.querySelectorAll('[data-edit-open]').forEach(function (btn) {
            btn.addEventListener('click', function () {
              var card = btn.closest('[data-comment-card]');
              if (!card) return;
              var body = card.querySelector('[data-comment-body]');
              var form = card.querySelector('[data-edit-form]');
              if (body) body.hidden = true;
              if (form) {
                form.hidden = false;
                var textarea = form.querySelector('textarea');
                if (textarea) textarea.focus();
              }
            });
          });

          document.querySelectorAll('[data-edit-cancel]').forEach(function (btn) {
            btn.addEventListener('click', function () {
              var form = btn.closest('[data-edit-form]');
              if (!form) return;
              var card = btn.closest('[data-comment-card]');
              form.hidden = true;
              var body = card ? card.querySelector('[data-comment-body]') : null;
              if (body) body.hidden = false;
            });
          });
        })();
      </script>
    </article>
  </section>
</div>

<x-panel-gallery-viewer
  modal-id="user-project-gallery-viewer"
  open-selector="[data-gallery-open]"
/>
@endsection
















