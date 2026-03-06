@extends('layouts.panel', [
  'title' => 'Client Dashboard',
  'heading' => 'Client Dashboard',
  'subheading' => 'Welcome, ' . auth()->user()->name,
])

@section('content')
<section class="panel-card">
  <h2 class="panel-section-title">Your Leads</h2>
  <div class="panel-table-wrap">
    <table class="panel-table">
      <thead><tr><th>Lead ID</th><th>Service</th><th>Location</th><th>Status</th><th>Score</th></tr></thead>
      <tbody>
        @forelse($leads as $lead)
        <tr>
          <td>#{{ $lead->id }}</td>
          <td>{{ $lead->service_type ?: '-' }}</td>
          <td>{{ $lead->location ?: '-' }}</td>
          <td><span class="panel-badge">{{ $lead->status }}</span></td>
          <td>{{ $lead->score }}</td>
        </tr>
        @empty
        <tr><td colspan="5" class="panel-muted">No leads yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <x-panel-pagination :paginator="$leads" />
</section>

<section class="panel-card">
  <h2 class="panel-section-title">Your Quotes</h2>
  <div class="panel-table-wrap">
    <table class="panel-table">
      <thead><tr><th>Quote ID</th><th>Services</th><th>Status</th><th>Total</th><th>Submitted</th><th>Action</th></tr></thead>
      <tbody>
        @forelse($quotes as $quote)
        <tr>
          <td>{{ $quote->quote_id }}</td>
          <td>{{ is_array($quote->services) ? implode(', ', $quote->services) : '-' }}</td>
          <td><span class="panel-badge">{{ $quote->status }}</span></td>
          <td>{{ number_format((int) $quote->estimated_total) }} {{ $quote->currency }}</td>
          <td>{{ $quote->submitted_at?->format('Y-m-d H:i') ?: '-' }}</td>
          <td><a class="panel-link" href="{{ route('user.quotes.show', $quote) }}">Open</a></td>
        </tr>
        @empty
        <tr><td colspan="6" class="panel-muted">No quotes yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</section>

<section class="panel-card">
  <h2 class="panel-section-title">Request New Service</h2>
  <form method="post" action="{{ route('user.service-requests.store') }}" class="panel-stack">
    @csrf
    <input class="panel-input" type="text" name="requested_service" placeholder="Service needed (photo/video/drone etc.)" required>
    <input class="panel-input" type="text" name="subject" placeholder="Subject (optional)">
    <input class="panel-input" type="date" name="preferred_date">
    <textarea class="panel-textarea" name="details" placeholder="Tell us what you need"></textarea>
    <button class="panel-btn panel-btn-primary" type="submit">Submit Request</button>
  </form>
</section>

@if($client)
<section class="panel-card">
  <h2 class="panel-section-title">Your Projects</h2>
  <div class="panel-table-wrap">
    <table class="panel-table">
      <thead><tr><th>Title</th><th>Service</th><th>Schedule</th><th>Status</th></tr></thead>
      <tbody>
        @forelse($client->projects as $project)
        <tr>
          <td>{{ $project->title }}</td>
          <td>{{ $project->service_type ?: '-' }}</td>
          <td>{{ $project->scheduled_at?->format('Y-m-d H:i') ?: '-' }}</td>
          <td><span class="panel-badge">{{ $project->status }}</span></td>
        </tr>
        @empty
        <tr><td colspan="4" class="panel-muted">No projects yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</section>

<section class="panel-card">
  <h2 class="panel-section-title">Project Gallery & Delivery</h2>
  <div class="panel-stack">
    @forelse($client->projects as $project)
      @php
        $galleryItems = $project->media->whereIn('type', ['image', 'video'])->values();
        $finalZipItems = $project->media->where('type', 'final_zip')->values();
        $isPaid = $project->invoices->contains(fn($invoice) => $invoice->status === 'paid');
        $projectGalleryPayload = $galleryPayloadByProject[$project->id] ?? [];
      @endphp
      <article class="panel-card">
        <x-project-media-summary
          :project="$project"
          :gallery-count="$galleryItems->count()"
          :zip-count="$finalZipItems->count()"
          :is-paid="$isPaid"
        />

        @if($galleryItems->isEmpty() && $finalZipItems->isEmpty())
          <p class="panel-muted">No delivery files yet.</p>
        @endif

        @if($galleryItems->isNotEmpty())
          <div class="panel-form-row" style="margin-bottom: 0.75rem;">
            @if($isPaid)
              <a class="panel-btn panel-btn-primary" href="{{ route('user.projects.media.download-zip', $project) }}">Download Gallery ZIP</a>
            @else
              <button class="panel-btn panel-btn-primary" type="button" disabled>Download Gallery ZIP (Locked until paid)</button>
            @endif
            <button
              class="panel-btn panel-btn-primary"
              type="button"
              data-gallery-open
              data-project-id="{{ $project->id }}"
              data-gallery-items='@json($projectGalleryPayload)'
            >
              Open Gallery Viewer
            </button>
          </div>

          <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 12px;">
            @foreach($galleryItems as $item)
              @php
                $previewUrl = route('user.projects.media.preview', ['project' => $project, 'media' => $item]);
              @endphp
              <div class="panel-card" style="position: relative;">
                @if($item->type === 'image')
                  <img src="{{ $previewUrl }}" alt="{{ $item->original_name }}" style="width: 100%; max-height: 180px; object-fit: cover; border-radius: 6px;">
                @else
                  <video controls style="width: 100%; max-height: 180px; border-radius: 6px;">
                    <source src="{{ $previewUrl }}" type="{{ $item->mime_type ?: 'video/mp4' }}">
                  </video>
                @endif

                @if(!$isPaid)
                  <div class="panel-badge" style="position: absolute; top: 8px; left: 8px; z-index: 10;">WATERMARK PREVIEW</div>
                @endif

                <div class="panel-form-row" style="margin-top: 0.5rem; justify-content: space-between;">
                  <span class="panel-muted" style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 65%;">{{ $item->original_name }}</span>
                  @if($isPaid)
                    <a class="panel-link" href="{{ route('user.projects.media.download', ['project' => $project, 'media' => $item]) }}">Download</a>
                  @else
                    <span class="panel-muted">Locked</span>
                  @endif
                </div>
              </div>
            @endforeach
          </div>
        @endif

        @if($finalZipItems->isNotEmpty())
          <div class="panel-stack" style="margin-top: 0.75rem;">
            <h4 class="panel-section-title">Final Delivery ZIP</h4>
            @foreach($finalZipItems as $zipItem)
              <div class="panel-form-row" style="justify-content: space-between;">
                <span>{{ $zipItem->original_name }}</span>
                @if($isPaid)
                  <a class="panel-link" href="{{ route('user.projects.media.download', ['project' => $project, 'media' => $zipItem]) }}">Download ZIP</a>
                @else
                  <span class="panel-muted">Locked until paid</span>
                @endif
              </div>
            @endforeach
          </div>
        @endif
      </article>
    @empty
      <p class="panel-muted">No project gallery available.</p>
    @endforelse
  </div>
</section>

<x-panel-gallery-viewer modal-id="client-media-gallery-viewer" open-selector="[data-gallery-open]" title-default="Gallery Viewer" />

<section class="panel-card">
  <h2 class="panel-section-title">Your Invoices</h2>
  <div class="panel-table-wrap">
    <table class="panel-table">
      <thead><tr><th>Invoice #</th><th>Amount</th><th>Status</th><th>Due Date</th></tr></thead>
      <tbody>
        @forelse($client->invoices as $invoice)
        <tr>
          <td>{{ $invoice->invoice_number }}</td>
          <td>{{ number_format((float) $invoice->amount, 2) }} {{ $invoice->currency }}</td>
          <td><span class="panel-badge">{{ $invoice->status }}</span></td>
          <td>{{ $invoice->due_date?->format('Y-m-d') ?: '-' }}</td>
        </tr>
        @empty
        <tr><td colspan="4" class="panel-muted">No invoices yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</section>

<section class="panel-card">
  <h2 class="panel-section-title">Request History</h2>
  <div class="panel-table-wrap">
    <table class="panel-table">
      <thead><tr><th>Service</th><th>Preferred Date</th><th>Status</th></tr></thead>
      <tbody>
        @forelse($client->serviceRequests as $requestItem)
        <tr>
          <td>{{ $requestItem->requested_service }}</td>
          <td>{{ $requestItem->preferred_date?->format('Y-m-d') ?: '-' }}</td>
          <td><span class="panel-badge">{{ $requestItem->status }}</span></td>
        </tr>
        @empty
        <tr><td colspan="3" class="panel-muted">No requests yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</section>

<section class="panel-card">
  <h2 class="panel-section-title">Messages from Team</h2>
  <div class="panel-chat-list">
    @forelse($client->messages as $message)
    <div class="panel-chat-item {{ $message->sender_role === 'client' ? 'is-user' : 'is-assistant' }}">
      <p class="panel-chat-role">{{ strtoupper($message->sender_role) }}</p>
      <p class="panel-chat-text">{{ $message->message }}</p>
      <p class="panel-muted">{{ $message->sent_at?->format('Y-m-d H:i') ?: $message->created_at?->format('Y-m-d H:i') }}</p>
    </div>
    @empty
    <p class="panel-muted">No team messages yet.</p>
    @endforelse
  </div>
</section>
@endif
@endsection
