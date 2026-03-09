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
      <p class="client-portal-kpi-value">{{ $isPaid ? 'Paid' : 'Pending' }}</p>
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
  </section>

  <section class="panel-card client-media-workspace">
    <div class="panel-form-row media-project-header">
      <div class="media-project-meta">
        <h2 class="panel-section-title" style="margin: 0;">Project Media Delivery</h2>
        <p class="media-project-summary">Gallery: {{ $galleryItems->count() }} | Final ZIP: {{ $zipItems->count() }} | Payment: <strong>{{ $isPaid ? 'Paid' : 'Unpaid' }}</strong></p>
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
        @if($zipItems->isNotEmpty() && $isPaid)
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
                @if($isPaid)
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
                @if($isPaid)
                  <a class="panel-btn panel-btn-primary" href="{{ route('user.projects.media.download', ['project' => $project, 'media' => $zipItem]) }}">Download ZIP</a>
                @else
                  <span class="panel-badge">Unlocks after payment</span>
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

  <section class="panel-card client-portal-stack">
    <h2 class="panel-section-title">Project Conversation</h2>
    @forelse($project->messages as $message)
      <div class="panel-chat-item {{ $message->sender_role === 'client' ? 'is-user' : 'is-assistant' }}">
        <p class="panel-chat-role">
          {{ strtoupper($message->sender_role) }}
          @if($message->sender)
            &bull; {{ $message->sender->name }}
          @endif
        </p>
        <p class="panel-chat-text">{{ $message->message }}</p>
        <p class="panel-muted">{{ $message->sent_at?->format('Y-m-d H:i') ?: $message->created_at?->format('Y-m-d H:i') }}</p>
      </div>
    @empty
      <div class="client-portal-empty">No project conversation is available yet.</div>
    @endforelse
  </section>
</div>

<x-panel-gallery-viewer
  modal-id="user-project-gallery-viewer"
  open-selector="[data-gallery-open]"
/>
@endsection
