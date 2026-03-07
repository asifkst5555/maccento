@extends('layouts.panel', [
  'title' => 'Client #' . $client->id,
  'heading' => $client->name,
  'subheading' => ($client->email ?: ($client->phone ?: 'No contact')) . ' | ' . strtoupper($client->status),
])

@section('content')
<section class="panel-two-col">
  <div class="panel-main-col">
    <article class="panel-card">
      <h2 class="panel-section-title">Projects</h2>
      <form method="post" action="{{ route('admin.clients.projects.store', $client) }}" class="panel-stack">
        @csrf
        <input class="panel-input" type="text" name="title" placeholder="Project title" required>
        <input class="panel-input" type="text" name="service_type" placeholder="Service type">
        <input class="panel-input" type="text" name="property_address" placeholder="Property address">
        <div class="panel-form-row">
          <input class="panel-input" type="datetime-local" name="scheduled_at">
          <input class="panel-input" type="datetime-local" name="due_at">
          <select class="panel-select" name="status" required>
            @foreach($projectStatuses as $status)
            <option value="{{ $status }}">{{ ucfirst($status) }}</option>
            @endforeach
          </select>
        </div>
        <textarea class="panel-textarea" name="notes" placeholder="Project notes"></textarea>
        <button class="panel-btn panel-btn-primary" type="submit">Create Project</button>
      </form>

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
                <form method="post" action="{{ route('admin.projects.status', $project) }}" class="panel-form-row">
                  @csrf
                  <select class="panel-select" name="status">
                    @foreach($projectStatuses as $status)
                    <option value="{{ $status }}" @selected($project->status === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                  </select>
                  <button class="panel-btn" type="submit">Save</button>
                </form>
                <a class="panel-link" href="{{ route('admin.invoices.index', ['invoice_project' => $project->id]) }}">Project Invoice</a>
              </td>
            </tr>
            @empty
            <tr><td colspan="5" class="panel-muted">No projects yet.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="panel-stack" style="margin-top: 1rem;">
        <h3 class="panel-section-title">Project Media Delivery</h3>
        @forelse($client->projects as $project)
          @php
            $galleryCount = $project->media->whereIn('type', ['image', 'video'])->count();
            $deliveryZipCount = $project->media->where('type', 'final_zip')->count();
            $isPaid = $project->invoices->contains(fn($invoice) => $invoice->status === 'paid');
          @endphp
          <article class="panel-card">
            <h4 class="panel-section-title">{{ $project->title }}</h4>
            <p class="panel-muted">Gallery: {{ $galleryCount }} file(s) | Final ZIP: {{ $deliveryZipCount }} | Payment: {{ $isPaid ? 'Paid' : 'Unpaid' }}</p>

            <form method="post" action="{{ route('admin.projects.media.store', $project) }}" class="panel-stack" enctype="multipart/form-data">
              @csrf
              <label class="panel-muted">Upload Gallery Images/Videos (multiple)</label>
              <input class="panel-input" type="file" name="media_files[]" accept="image/*,video/*" multiple required>
              <button class="panel-btn panel-btn-primary" type="submit">Upload to Gallery</button>
            </form>

            <form method="post" action="{{ route('admin.projects.delivery-zip.store', $project) }}" class="panel-stack" enctype="multipart/form-data">
              @csrf
              <label class="panel-muted">Upload Final Delivery ZIP</label>
              <input class="panel-input" type="file" name="delivery_zip" accept=".zip,application/zip" required>
              <button class="panel-btn" type="submit">Upload Final ZIP</button>
            </form>

            <div class="panel-stack" style="margin-top: 0.75rem;">
              <h5 class="panel-section-title">Uploaded Files</h5>
              @forelse($project->media as $mediaItem)
                <div class="panel-form-row" style="justify-content: space-between;">
                  <span><strong>{{ strtoupper($mediaItem->type) }}</strong> — {{ $mediaItem->original_name }}</span>
                  <div class="panel-form-row" style="margin-bottom: 0;">
                    <a class="panel-link" href="{{ route('admin.projects.media.view', ['project' => $project, 'media' => $mediaItem]) }}" target="_blank" rel="noopener">View file</a>
                    <form method="post" action="{{ route('admin.projects.media.delete', ['project' => $project, 'media' => $mediaItem]) }}" data-delete-form data-delete-name="{{ $mediaItem->original_name }}">
                      @csrf
                      <button class="panel-btn panel-btn-danger panel-btn-icon" type="button" data-delete-trigger title="Delete file" aria-label="Delete file"><span class="panel-icon-trash" aria-hidden="true"><svg viewBox="0 0 20 20"><path d="M5 6h10M8 6V4h4v2m-6 0l.5 9h7L14 6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span></button>
                    </form>
                  </div>
                </div>
              @empty
                <p class="panel-muted">No uploaded media yet.</p>
              @endforelse
            </div>
          </article>
        @empty
          <p class="panel-muted">Create a project first to upload gallery and delivery files.</p>
        @endforelse
      </div>
    </article>

    <x-panel-delete-confirm-modal modal-id="client-media-delete-confirm-modal" trigger-selector="[data-delete-trigger]" title="Delete Media File" />

    <article class="panel-card">
      <h2 class="panel-section-title">Invoices</h2>
      <form method="post" action="{{ route('admin.clients.invoices.store', $client) }}" class="panel-stack">
        @csrf
        <div class="panel-form-row">
          <select class="panel-select" name="client_project_id">
            <option value="">Link project (optional)</option>
            @foreach($client->projects as $project)
            <option value="{{ $project->id }}">{{ $project->title }}</option>
            @endforeach
          </select>
          <input class="panel-input" type="number" step="0.01" min="0" name="amount" placeholder="Amount" required>
          <input class="panel-input" type="text" name="currency" value="USD" required>
          <select class="panel-select" name="status" required>
            @foreach(['draft','sent','partial','paid','overdue'] as $status)
            <option value="{{ $status }}">{{ ucfirst($status) }}</option>
            @endforeach
          </select>
        </div>
        <div class="panel-form-row">
          <input class="panel-input" type="date" name="issued_at">
          <input class="panel-input" type="date" name="due_date">
        </div>
        <textarea class="panel-textarea" name="notes" placeholder="Invoice notes"></textarea>
        <button class="panel-btn panel-btn-primary" type="submit">Create Invoice</button>
      </form>

      <div class="panel-table-wrap">
        <table class="panel-table">
          <thead><tr><th>Invoice #</th><th>Project</th><th>Amount</th><th>Status</th><th>Due</th></tr></thead>
          <tbody>
            @forelse($client->invoices as $invoice)
            <tr>
              <td>{{ $invoice->invoice_number }}</td>
              <td>{{ $invoice->project?->title ?: '-' }}</td>
              <td>{{ number_format((float) $invoice->amount, 2) }} {{ $invoice->currency }}</td>
              <td><span class="panel-badge">{{ $invoice->status }}</span></td>
              <td>{{ $invoice->due_date?->format('Y-m-d') ?: '-' }}</td>
            </tr>
            @empty
            <tr><td colspan="5" class="panel-muted">No invoices yet.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </article>
  </div>

  <aside class="panel-side-col">
    <div class="panel-side-sticky">
      <article class="panel-card">
        <h2 class="panel-section-title">Send Message</h2>
        <form method="post" action="{{ route('admin.clients.messages.store', $client) }}" class="panel-stack">
          @csrf
          <select class="panel-select" name="client_project_id">
            <option value="">General message</option>
            @foreach($client->projects as $project)
            <option value="{{ $project->id }}">{{ $project->title }}</option>
            @endforeach
          </select>
          <textarea class="panel-textarea" name="message" placeholder="Write a message to client..." required></textarea>
          <button class="panel-btn panel-btn-primary" type="submit">Send Message</button>
        </form>
      </article>

      <article class="panel-card">
        <h2 class="panel-section-title">Message Timeline</h2>
        <div class="panel-chat-list">
          @forelse($client->messages as $message)
          <div class="panel-chat-item {{ $message->sender_role === 'client' ? 'is-user' : 'is-assistant' }}">
            <p class="panel-chat-role">{{ strtoupper($message->sender_role) }}</p>
            <p class="panel-chat-text">{{ $message->message }}</p>
            <p class="panel-muted">{{ $message->sent_at?->format('Y-m-d H:i') ?: $message->created_at?->format('Y-m-d H:i') }}</p>
          </div>
          @empty
          <p class="panel-muted">No messages yet.</p>
          @endforelse
        </div>
      </article>

      <article class="panel-card">
        <h2 class="panel-section-title">Service Requests</h2>
        <div class="panel-table-wrap">
          <table class="panel-table">
            <thead><tr><th>Service</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
              @forelse($client->serviceRequests as $requestItem)
              <tr>
                <td>{{ $requestItem->requested_service }}</td>
                <td><span class="panel-badge">{{ $requestItem->status }}</span></td>
                <td>
                  <form method="post" action="{{ route('admin.service-requests.status', $requestItem) }}" class="panel-form-row">
                    @csrf
                    <select class="panel-select" name="status">
                      @foreach(['new','accepted','in_progress','completed','closed'] as $status)
                      <option value="{{ $status }}" @selected($requestItem->status === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                      @endforeach
                    </select>
                    <button class="panel-btn" type="submit">Save</button>
                  </form>
                </td>
              </tr>
              @empty
              <tr><td colspan="3" class="panel-muted">No service requests yet.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </article>
    </div>
  </aside>
</section>
@endsection
