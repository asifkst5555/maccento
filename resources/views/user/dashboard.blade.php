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
      <thead><tr><th>Title</th><th>Service</th><th>Schedule</th><th>Status</th><th>Action</th></tr></thead>
      <tbody>
        @forelse($client->projects as $project)
        <tr>
          <td>{{ $project->title }}</td>
          <td>{{ $project->service_type ?: '-' }}</td>
          <td>{{ $project->scheduled_at?->format('Y-m-d H:i') ?: '-' }}</td>
          <td><span class="panel-badge">{{ $project->status }}</span></td>
          <td>
            <button class="panel-btn panel-btn-primary" type="button" data-project-popup-open data-project-popup-id="{{ $project->id }}">
              Open Project
            </button>
          </td>
        </tr>
        @empty
        <tr><td colspan="5" class="panel-muted">No projects yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <style>
    .project-popup-backdrop {
      position: fixed;
      inset: 0;
      background: rgba(15, 31, 53, 0.68);
      z-index: 900;
      display: none;
    }
    .project-popup-backdrop.is-open {
      display: block;
    }
    .project-popup {
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      width: min(980px, calc(100vw - 2rem));
      max-height: calc(100vh - 2rem);
      overflow: auto;
      z-index: 901;
      display: none;
      border: 1px solid rgba(31, 73, 119, 0.2);
      box-shadow: 0 28px 60px rgba(11, 26, 44, 0.36);
    }
    .project-popup.is-open {
      display: block;
    }
    .project-popup-head {
      display: flex;
      justify-content: space-between;
      gap: 1rem;
      align-items: flex-start;
      margin-bottom: 0.75rem;
    }
    .project-popup-title {
      margin: 0;
      font-size: 1.15rem;
      color: #183a63;
    }
    .project-popup-meta {
      margin: 0.15rem 0 0;
      color: #5b7396;
      font-size: 0.92rem;
    }
  </style>

  @foreach($client->projects as $project)
    <div class="project-popup-backdrop" data-project-popup-backdrop="{{ $project->id }}"></div>
    <article class="panel-card project-popup" data-project-popup="{{ $project->id }}" role="dialog" aria-modal="true" aria-label="Project {{ $project->title }} details">
      <div class="project-popup-head">
        <div>
          <h3 class="project-popup-title">{{ $project->title }}</h3>
          <p class="project-popup-meta">
            {{ $project->service_type ?: 'Service not set' }}
            @if(!blank($project->property_address))
              | {{ $project->property_address }}
            @endif
          </p>
        </div>
        <button class="panel-btn" type="button" data-project-popup-close data-project-popup-id="{{ $project->id }}">Close</button>
      </div>

      <div class="panel-table-wrap" style="margin-bottom: 0.75rem;">
        <table class="panel-table">
          <tbody>
            <tr>
              <th style="width: 180px;">Status</th>
              <td><span class="panel-badge">{{ $project->status }}</span></td>
            </tr>
            <tr>
              <th>Schedule</th>
              <td>{{ $project->scheduled_at?->format('Y-m-d H:i') ?: '-' }}</td>
            </tr>
            <tr>
              <th>Project ID</th>
              <td>#{{ $project->id }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <h4 class="panel-section-title" style="margin-top: 0;">Invoices for this Project</h4>
      <div class="panel-table-wrap">
        <table class="panel-table">
          <thead>
            <tr><th>Invoice #</th><th>Amount</th><th>Status</th><th>Due Date</th><th>Email</th><th>Action</th></tr>
          </thead>
          <tbody>
            @forelse($project->invoices as $invoice)
              <tr>
                <td>{{ $invoice->invoice_number }}</td>
                <td>{{ number_format((float) $invoice->amount, 2) }} {{ $invoice->currency }}</td>
                <td><span class="panel-badge">{{ $invoice->status }}</span></td>
                <td>{{ $invoice->due_date?->format('Y-m-d') ?: '-' }}</td>
                <td>{{ $invoice->client?->email ?: ($client->email ?: '-') }}</td>
                <td><a class="panel-link" href="{{ route('user.invoices.download', $invoice) }}">Download PDF</a></td>
              </tr>
            @empty
              <tr><td colspan="6" class="panel-muted">No invoices for this project yet.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <h4 class="panel-section-title" style="margin-top: 1rem;">Need an Extra Service?</h4>
      <form method="post" action="{{ route('user.service-requests.store') }}" class="panel-stack" style="margin-bottom: 1rem;">
        @csrf
        <input type="hidden" name="client_project_id" value="{{ $project->id }}">
        <div class="panel-form-row">
          <input class="panel-input" type="text" name="requested_service" placeholder="Extra service (ex: twilight shoot, reels, floor plan)" required>
          <input class="panel-input" type="date" name="preferred_date">
        </div>
        <input class="panel-input" type="text" name="subject" placeholder="Short subject (optional)">
        <textarea class="panel-textarea" name="details" placeholder="Tell us what you need for this project"></textarea>
        <button class="panel-btn panel-btn-primary" type="submit">Request Additional Service</button>
      </form>

      <h4 class="panel-section-title">Project Timeline</h4>
      <div class="panel-table-wrap" style="margin-bottom: 0.75rem;">
        <table class="panel-table">
          <thead>
            <tr><th>Service Request</th><th>Status</th><th>Preferred Date</th><th>Created</th></tr>
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
                <td>{{ $requestItem->created_at?->format('Y-m-d H:i') ?: '-' }}</td>
              </tr>
            @empty
              <tr><td colspan="4" class="panel-muted">No service request timeline yet.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="panel-chat-list">
        @forelse($project->messages as $message)
          <div class="panel-chat-item {{ $message->sender_role === 'client' ? 'is-user' : 'is-assistant' }}">
            <p class="panel-chat-role">{{ strtoupper($message->sender_role) }}</p>
            <p class="panel-chat-text">{{ $message->message }}</p>
            <p class="panel-muted">{{ $message->sent_at?->format('Y-m-d H:i') ?: $message->created_at?->format('Y-m-d H:i') }}</p>
          </div>
        @empty
          <p class="panel-muted">No project messages yet.</p>
        @endforelse
      </div>
    </article>
  @endforeach

  <script>
    (function () {
      var openButtons = document.querySelectorAll('[data-project-popup-open]');
      var closeButtons = document.querySelectorAll('[data-project-popup-close]');

      var setPopupState = function (projectId, shouldOpen) {
        var popup = document.querySelector('[data-project-popup="' + projectId + '"]');
        var backdrop = document.querySelector('[data-project-popup-backdrop="' + projectId + '"]');
        if (!popup || !backdrop) return;

        popup.classList.toggle('is-open', shouldOpen);
        backdrop.classList.toggle('is-open', shouldOpen);
        document.body.style.overflow = shouldOpen ? 'hidden' : '';
      };

      openButtons.forEach(function (button) {
        button.addEventListener('click', function () {
          var projectId = button.getAttribute('data-project-popup-id');
          if (!projectId) return;
          setPopupState(projectId, true);
        });
      });

      closeButtons.forEach(function (button) {
        button.addEventListener('click', function () {
          var projectId = button.getAttribute('data-project-popup-id');
          if (!projectId) return;
          setPopupState(projectId, false);
        });
      });

      document.querySelectorAll('[data-project-popup-backdrop]').forEach(function (backdrop) {
        backdrop.addEventListener('click', function () {
          var projectId = backdrop.getAttribute('data-project-popup-backdrop');
          if (!projectId) return;
          setPopupState(projectId, false);
        });
      });

      document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        document.querySelectorAll('.project-popup.is-open').forEach(function (popup) {
          var projectId = popup.getAttribute('data-project-popup');
          if (!projectId) return;
          setPopupState(projectId, false);
        });
      });
    })();
  </script>
</section>

<section class="panel-card">
  <h2 class="panel-section-title">Project Gallery & Delivery</h2>
  // ...existing code...
</section>

<x-panel-gallery-viewer modal-id="client-media-gallery-viewer" open-selector="[data-gallery-open]" title-default="Gallery Viewer" />

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
