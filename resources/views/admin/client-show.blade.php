@extends('layouts.panel', [
  'title' => 'Client #' . $client->id,
  'heading' => $client->name,
  'subheading' => ($client->email ?: ($client->phone ?: 'No contact')) . ' | ' . strtoupper($client->status),
])

@section('content')
@php
  $visibleProjects = $focusedProject ? $client->projects->where('id', $focusedProject->id)->values() : $client->projects;
  $visibleInvoices = $focusedProject ? $client->invoices->where('client_project_id', $focusedProject->id)->values() : $client->invoices;
  $visibleServiceRequests = $focusedProject ? $client->serviceRequests->where('client_project_id', $focusedProject->id)->values() : $client->serviceRequests;
  $visibleMessages = $focusedProject ? $focusedProject->messages : $client->messages;
  $crmRole = strtolower(trim((string) auth()->user()?->role));
  $canManagePipeline = in_array($crmRole, ['owner', 'admin', 'manager'], true);
@endphp
<style>
  .client-corporate-shell {
    --corp-ink: #10233a;
    --corp-ink-soft: #5a6b82;
    --corp-surface: #ffffff;
    --corp-surface-alt: #f4f7fb;
    --corp-line: #d8e1ec;
    --corp-accent: #c21f37;
    --corp-shadow: 0 14px 32px rgba(16, 35, 58, 0.08);
    background: linear-gradient(180deg, #f8fbff 0%, #edf3fa 100%);
    border: 1px solid #dde6f2;
    border-radius: 16px;
    padding: 1rem;
    gap: 1rem;
  }

  .client-corporate-shell .panel-card {
    background: var(--corp-surface);
    border: 1px solid var(--corp-line);
    border-radius: 14px;
    box-shadow: var(--corp-shadow);
  }

  .client-corporate-shell .panel-section-title {
    color: var(--corp-ink);
    letter-spacing: 0.01em;
  }

  .client-corporate-shell .panel-muted {
    color: var(--corp-ink-soft);
  }

  .client-corporate-shell .panel-link {
    color: var(--corp-ink);
    font-weight: 600;
    text-decoration: none;
  }

  .client-corporate-shell .panel-link:hover {
    color: var(--corp-accent);
  }

  .client-corporate-shell .panel-input,
  .client-corporate-shell .panel-select,
  .client-corporate-shell .panel-textarea {
    border-radius: 10px;
    border: 1px solid #cfd9e7;
    background-color: #fff;
  }

  .client-corporate-shell .panel-input:focus,
  .client-corporate-shell .panel-select:focus,
  .client-corporate-shell .panel-textarea:focus {
    border-color: #8ca6c4;
    box-shadow: 0 0 0 3px rgba(140, 166, 196, 0.2);
    outline: none;
  }

  .client-corporate-shell .panel-btn {
    border-radius: 10px;
    border: 1px solid #becddd;
    font-weight: 600;
  }

  .client-corporate-shell .panel-btn-primary {
    border-color: #a3162b;
    background: linear-gradient(90deg, #b71d34 0%, #cc2741 100%);
  }

  .client-corporate-shell .panel-table-wrap {
    border: 1px solid var(--corp-line);
    border-radius: 12px;
    overflow: hidden;
    background: #fff;
  }

  .client-corporate-shell .panel-table thead th {
    background: #f2f6fb;
    color: #334a66;
    font-size: 0.76rem;
    letter-spacing: 0.03em;
    text-transform: uppercase;
  }

  .client-corporate-shell .panel-table tbody tr:nth-child(even) {
    background: #fafcff;
  }

  .client-corporate-shell .panel-badge {
    border-radius: 999px;
    border: 1px solid #c6d3e2;
    background: #f1f6fc;
    color: #213a56;
    font-weight: 700;
    font-size: 0.7rem;
    letter-spacing: 0.03em;
  }

  .client-corporate-shell .panel-side-sticky {
    display: grid;
    gap: 0.9rem;
  }

  .client-corporate-shell .panel-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.6rem;
    margin-bottom: 0.8rem;
  }

  .client-corporate-shell .panel-card-head .panel-section-title {
    margin-bottom: 0;
  }

  .client-corporate-shell .panel-card-toggle {
    border: 1px solid #c4d1e0;
    background: #f3f7fc;
    color: #28415f;
    border-radius: 999px;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.03em;
    padding: 0.3rem 0.7rem;
    cursor: pointer;
  }

  .client-corporate-shell .panel-card-toggle:hover {
    background: #e9f0f8;
  }

  .client-corporate-shell .is-collapsed .panel-card-body {
    display: none;
  }

  .client-corporate-shell .panel-chat-list {
    max-height: 340px;
    overflow: auto;
    padding-right: 0.2rem;
  }

  .client-corporate-shell .panel-chat-item {
    border-radius: 10px;
    border: 1px solid #d4ddeb;
    background: #fbfdff;
  }

  .client-corporate-shell .row-tight {
    margin-bottom: 0;
    gap: 0.75rem;
  }

  .client-corporate-shell .stack-top-lg {
    margin-top: 1rem;
  }

  .client-corporate-shell .stack-top-md {
    margin-top: 0.75rem;
  }

  .client-corporate-shell .row-between {
    justify-content: space-between;
  }

  .client-corporate-shell .row-inline-end {
    margin-bottom: 0;
  }

  .client-corporate-shell .invoice-recipient {
    margin-bottom: 0.75rem;
  }

  .client-corporate-shell .inline-delete-form {
    display: inline-block;
    margin-left: 8px;
  }

  .client-corporate-shell .service-filter-row {
    margin-bottom: 0.75rem;
    gap: 0.5rem;
  }

  .client-corporate-shell .inline-checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    white-space: nowrap;
  }

  .client-corporate-shell .media-project-card {
    padding: 1rem;
    border: 1px solid var(--corp-line);
    border-radius: 14px;
    background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
    width: 100%;
    box-sizing: border-box;
  }

  .client-corporate-shell .media-project-header {
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.75rem;
    flex-wrap: wrap;
    width: 100%;
  }

  .client-corporate-shell .media-project-meta {
    display: grid;
    gap: 0.3rem;
    flex: 1 1 320px;
    min-width: 0;
  }

  .client-corporate-shell .media-project-title {
    margin: 0;
    color: var(--corp-ink);
    font-size: 1.25rem;
    line-height: 1.2;
  }

  .client-corporate-shell .media-project-subline {
    color: var(--corp-ink-soft);
  }

  .client-corporate-shell .media-project-summary {
    color: var(--corp-ink-soft);
  }

  .client-corporate-shell .media-project-controls {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.6rem;
    flex-wrap: wrap;
    margin-bottom: 0;
    flex: 1 1 340px;
  }

  .client-corporate-shell .media-project-toggle {
    width: 44px;
    height: 44px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-color: #a3162b;
    background: linear-gradient(90deg, #b71d34 0%, #cc2741 100%);
    color: #fff;
    border-radius: 14px;
    box-shadow: 0 8px 18px rgba(194, 31, 55, 0.2);
  }

  .client-corporate-shell .media-project-toggle svg {
    width: 22px;
    height: 22px;
    transition: transform 160ms ease;
  }

  .client-corporate-shell .media-project-card.is-collapsed .media-project-toggle svg {
    transform: rotate(-90deg);
  }

  .client-corporate-shell .media-project-details[hidden] {
    display: none;
  }

  .client-corporate-shell .media-project-details {
    width: 100%;
    margin-top: 0.85rem;
  }

  .client-corporate-shell .media-delivery-upload-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
    margin-top: 12px;
    width: 100%;
  }

  .client-corporate-shell .media-delivery-upload-card {
    border: 1px solid #d8e1ec;
    border-radius: 12px;
    background: #f9fbff;
    padding: 12px;
    min-width: 0;
  }

  .client-corporate-shell .media-delivery-files-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
    margin-top: 12px;
    width: 100%;
  }

  .client-corporate-shell .media-file-list-card {
    margin: 0;
    border: 1px solid #d8e2ef;
    background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
    min-width: 0;
  }

  .client-corporate-shell .media-file-list {
    display: grid;
    gap: 10px;
  }

  .client-corporate-shell .media-file-row {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 10px;
    padding: 10px 12px;
    border: 1px solid #e1e9f4;
    border-radius: 10px;
    background: #fff;
  }

  .client-corporate-shell .media-file-meta {
    min-width: 0;
    display: grid;
    gap: 4px;
  }

  .client-corporate-shell .media-file-kind {
    display: inline-flex;
    align-items: center;
    width: fit-content;
    padding: 2px 8px;
    border-radius: 999px;
    font-size: 11px;
    letter-spacing: 0.04em;
    font-weight: 700;
    text-transform: uppercase;
    color: #28415f;
    background: #eef4fb;
    border: 1px solid #d0dced;
  }

  .client-corporate-shell .media-file-name {
    color: #1e3450;
    font-weight: 600;
    word-break: break-word;
  }

  .client-corporate-shell .media-file-actions {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    flex-wrap: nowrap;
  }

  .client-corporate-shell .media-file-list-cta {
    margin-top: 4px;
    justify-content: flex-end;
  }

  .client-corporate-shell .media-file-list-cta-group {
    gap: 8px;
    flex-wrap: wrap;
  }

  .client-corporate-shell .is-hidden-by-default {
    display: none;
  }

  @media (max-width: 1180px) {
    .client-corporate-shell {
      border-radius: 12px;
      padding: 0.75rem;
    }

    .client-corporate-shell .panel-side-col {
      position: static;
    }

    .client-corporate-shell .media-delivery-upload-grid,
    .client-corporate-shell .media-delivery-files-grid {
      grid-template-columns: 1fr;
    }

    .client-corporate-shell .media-project-controls {
      justify-content: flex-start;
    }
  }

  @media (max-width: 640px) {
    .client-corporate-shell {
      padding: 0.7rem;
      gap: 0.8rem;
    }

    .client-corporate-shell .panel-card {
      border-radius: 12px;
    }

    .client-corporate-shell .panel-card-head,
    .client-corporate-shell .media-project-header,
    .client-corporate-shell .row-tight,
    .client-corporate-shell .service-filter-row,
    .client-corporate-shell .row-between {
      flex-direction: column;
      align-items: stretch;
    }

    .client-corporate-shell .media-project-controls,
    .client-corporate-shell .media-file-actions,
    .client-corporate-shell .media-file-list-cta-group {
      width: 100%;
      justify-content: stretch;
    }

    .client-corporate-shell .media-project-controls > *,
    .client-corporate-shell .media-file-actions > *,
    .client-corporate-shell .media-file-list-cta-group > * {
      width: 100%;
    }

    .client-corporate-shell .media-project-toggle {
      width: 100%;
      height: 42px;
      border-radius: 12px;
    }

    .client-corporate-shell .media-file-row {
      flex-direction: column;
      align-items: stretch;
    }

    .client-corporate-shell .inline-checkbox-label {
      white-space: normal;
      align-items: flex-start;
    }

    .client-corporate-shell .inline-delete-form {
      display: block;
      margin-left: 0;
      margin-top: 8px;
    }

    .client-corporate-shell .panel-form-row > .panel-btn,
    .client-corporate-shell .panel-form-row > .panel-link,
    .client-corporate-shell .panel-form-row > form,
    .client-corporate-shell .panel-form-row > label {
      width: 100%;
      flex: 1 1 100%;
    }

    .client-corporate-shell .panel-table td[data-label="Action"] .panel-form-row {
      flex-direction: column;
      align-items: stretch;
    }

    .client-corporate-shell .panel-table td[data-label="Action"] .panel-select,
    .client-corporate-shell .panel-table td[data-label="Action"] .panel-input,
    .client-corporate-shell .panel-table td[data-label="Action"] .panel-btn,
    .client-corporate-shell .panel-table td[data-label="Action"] .panel-link {
      width: 100%;
    }
  }
</style>
<section class="panel-two-col client-corporate-shell">
  <div class="panel-main-col">
    @if($focusedProject)
    <article class="panel-card">
      <h2 class="panel-section-title">Client > Single Project Mode</h2>
      <p class="panel-muted">You are on the client detail page, filtered to one project only.</p>
      <p class="panel-muted">Viewing: <strong>{{ $focusedProject->title }}</strong> (Project #{{ $focusedProject->id }})</p>
      <div class="panel-form-row row-tight">
        <a class="panel-link" href="{{ route('admin.clients.show', $client) }}">Show all projects for this client</a>
      </div>
    </article>
    @endif

    <article class="panel-card">
      <h2 class="panel-section-title">Projects</h2>
      @if(!$focusedProject && $canManagePipeline)
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
      @else
      @endif

      <div class="panel-table-wrap">
        <table class="panel-table">
          <thead><tr><th>Title</th><th>Service</th><th>Schedule</th><th>Status</th><th>Action</th></tr></thead>
          <tbody>
            @forelse($visibleProjects as $project)
            <tr id="project-{{ $project->id }}">
              <td>{{ $project->title }}</td>
              <td>{{ $project->service_type ?: '-' }}</td>
              <td>{{ $project->scheduled_at?->format('Y-m-d H:i') ?: '-' }}</td>
              <td><span class="panel-badge">{{ $project->status }}</span></td>
              <td class="project-action-cell" style="white-space: nowrap; min-width: 430px;">
                @if($canManagePipeline)
                <form method="post" action="{{ route('admin.projects.status', $project) }}" class="project-action-form" style="display: inline-flex; align-items: center; gap: 8px; flex-wrap: nowrap; white-space: nowrap; width: auto; margin: 0;">
                  @csrf
                  <select class="panel-select" name="status" style="width: 140px; min-width: 140px; flex: 0 0 140px;">
                    @foreach($projectStatuses as $status)
                    <option value="{{ $status }}" @selected($project->status === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                  </select>
                  <button class="panel-btn" type="submit" style="flex: 0 0 auto;">Save</button>
                </form>
                @else
                <span class="panel-muted">Read only</span>
                @endif
              </td>
            </tr>
            @empty
            <tr><td colspan="5" class="panel-muted">No projects yet.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="panel-stack stack-top-lg">
        <h3 class="panel-section-title">Project Media Delivery</h3>
        @forelse($visibleProjects as $project)
          @php
            $galleryItems = $project->media->whereIn('type', ['image', 'video'])->values();
            $zipItems = $project->media->where('type', 'final_zip')->values();
            $galleryCount = $galleryItems->count();
            $deliveryZipCount = $zipItems->count();
            $isPaid = $project->invoices->contains(fn($invoice) => $invoice->status === 'paid');
            $projectGalleryPayload = $galleryItems->map(function ($item) use ($project) {
              return [
                'id' => (int) $item->id,
                'name' => (string) $item->original_name,
                'type' => (string) $item->type,
                'mime' => (string) ($item->mime_type ?? ''),
                'url' => route('admin.projects.media.view', ['project' => $project, 'media' => $item]),
              ];
            })->all();
          @endphp
          <article class="panel-card media-project-card" data-project-media-card data-project-id="{{ $project->id }}">
            <div class="panel-form-row media-project-header">
              <div class="media-project-meta">
                <h4 class="media-project-title">{{ $project->title }}</h4>
                <p class="media-project-subline">{{ $client->name }} @if($project->service_type)&bull; {{ $project->service_type }} @endif &bull; {{ $project->status }}</p>
                <p class="media-project-summary">Gallery: {{ $galleryCount }} | Final ZIP: {{ $deliveryZipCount }} | Payment: <strong>{{ $isPaid ? 'Paid' : 'Unpaid' }}</strong></p>
              </div>
              <div class="media-project-controls">
                <button class="panel-btn media-project-toggle" type="button" data-project-toggle aria-expanded="true" aria-label="Toggle project media details">
                  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 10l4 4 4-4" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                @if($galleryItems->isNotEmpty())
                <button
                  class="panel-btn panel-btn-primary"
                  type="button"
                  data-gallery-open
                  data-project-id="{{ $project->id }}"
                  data-gallery-items='@json($projectGalleryPayload)'
                >
                  View Media
                </button>
                @else
                <button class="panel-btn panel-btn-primary" type="button" disabled>View Media</button>
                @endif
                <a class="panel-link" href="{{ route('admin.clients.show', $client) }}">Open Client</a>
                @if($canManagePipeline)
                <a class="panel-link" href="{{ route('admin.invoices.index', ['invoice_project' => $project->id]) }}">Project Invoice</a>
                @endif
              </div>
            </div>

            <div class="media-project-details" data-project-details>
              @if($canManagePipeline)
              <div class="media-delivery-upload-grid">
                <article class="media-delivery-upload-card">
                  <h5 class="panel-section-title">Gallery Upload</h5>
                  <form method="post" action="{{ route('admin.projects.media.store', $project) }}" class="panel-stack" enctype="multipart/form-data">
                    @csrf
                    <label class="panel-muted">Upload Gallery Images/Videos</label>
                    <input class="panel-input" type="file" name="media_files[]" accept="image/*,video/*" multiple required>
                    <button class="panel-btn panel-btn-primary" type="submit">Upload Gallery</button>
                  </form>
                </article>

                <article class="media-delivery-upload-card">
                  <h5 class="panel-section-title">Final ZIP Upload</h5>
                  <form method="post" action="{{ route('admin.projects.delivery-zip.store', $project) }}" class="panel-stack" enctype="multipart/form-data">
                    @csrf
                    <label class="panel-muted">Upload Final Delivery ZIP</label>
                    <input class="panel-input" type="file" name="delivery_zip" accept=".zip,application/zip" required>
                    <button class="panel-btn" type="submit">Upload Final ZIP</button>
                  </form>
                </article>
              </div>
              @endif

              <div class="media-delivery-files-grid">
                <section class="panel-card media-file-list-card">
                  <h5 class="panel-section-title">Gallery Files</h5>
                  <div class="media-file-list">
                    @forelse($galleryItems as $index => $mediaItem)
                      @php
                        $mediaName = $mediaItem->original_name;
                        if (preg_match('/Ãƒ.|Ã‚|Ã¢â‚¬|Ã¢â‚¬â€œ|Ã¢â‚¬â€/u', (string) $mediaName)) {
                          $decodedName = @iconv('Windows-1252', 'UTF-8//IGNORE', (string) $mediaName);
                          if (is_string($decodedName) && $decodedName !== '') {
                            $mediaName = $decodedName;
                          }
                        }
                      @endphp
                      <article class="media-file-row @if($index >= 2) is-hidden-by-default @endif" data-gallery-row>
                        <div class="media-file-meta">
                          <span class="media-file-kind">{{ strtoupper($mediaItem->type) }}</span>
                          <span class="media-file-name">{{ $mediaName }}</span>
                        </div>
                        <div class="media-file-actions">
                          <a class="panel-link" href="{{ route('admin.projects.media.view', ['project' => $project, 'media' => $mediaItem]) }}" target="_blank" rel="noopener">View</a>
                          @if($canManagePipeline)
                          <form method="post" action="{{ route('admin.projects.media.delete', ['project' => $project, 'media' => $mediaItem]) }}" data-delete-form data-delete-name="{{ $mediaItem->original_name }}">
                            @csrf
                            <button class="panel-btn panel-btn-danger panel-btn-icon" type="button" data-delete-trigger title="Delete media" aria-label="Delete media"><span class="panel-icon-trash" aria-hidden="true"><svg viewBox="0 0 20 20"><path d="M5 6h10M8 6V4h4v2m-6 0l.5 9h7L14 6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span></button>
                          </form>
                          @endif
                        </div>
                      </article>
                    @empty
                    <p class="panel-muted">No gallery files yet.</p>
                    @endforelse
                    @if($galleryItems->count() > 2)
                    <div class="panel-form-row media-file-list-cta media-file-list-cta-group">
                      <button class="panel-btn" type="button" data-gallery-list-toggle aria-expanded="false">Show All Media List ({{ $galleryItems->count() }})</button>
                      <button
                        class="panel-btn panel-btn-primary"
                        type="button"
                        data-gallery-open
                        data-project-id="{{ $project->id }}"
                        data-gallery-items='@json($projectGalleryPayload)'
                      >
                        View All Gallery Files ({{ $galleryItems->count() }})
                      </button>
                    </div>
                    @endif
                  </div>
                </section>

                <section class="panel-card media-file-list-card">
                  <h5 class="panel-section-title">Final Delivery ZIP</h5>
                  <div class="media-file-list">
                    @forelse($zipItems as $zipItem)
                      @php
                        $zipName = $zipItem->original_name;
                        if (preg_match('/Ãƒ.|Ã‚|Ã¢â‚¬|Ã¢â‚¬â€œ|Ã¢â‚¬â€/u', (string) $zipName)) {
                          $decodedZipName = @iconv('Windows-1252', 'UTF-8//IGNORE', (string) $zipName);
                          if (is_string($decodedZipName) && $decodedZipName !== '') {
                            $zipName = $decodedZipName;
                          }
                        }
                      @endphp
                      <article class="media-file-row">
                        <div class="media-file-meta">
                          <span class="media-file-kind">ZIP</span>
                          <span class="media-file-name">{{ $zipName }}</span>
                        </div>
                        <div class="media-file-actions">
                          <a class="panel-link" href="{{ route('admin.projects.media.view', ['project' => $project, 'media' => $zipItem]) }}" target="_blank" rel="noopener">View ZIP</a>
                          @if($canManagePipeline)
                          <form method="post" action="{{ route('admin.projects.media.delete', ['project' => $project, 'media' => $zipItem]) }}" data-delete-form data-delete-name="{{ $zipItem->original_name }}">
                            @csrf
                            <button class="panel-btn panel-btn-danger panel-btn-icon" type="button" data-delete-trigger title="Delete ZIP" aria-label="Delete ZIP"><span class="panel-icon-trash" aria-hidden="true"><svg viewBox="0 0 20 20"><path d="M5 6h10M8 6V4h4v2m-6 0l.5 9h7L14 6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span></button>
                          </form>
                          @endif
                        </div>
                      </article>
                    @empty
                    <p class="panel-muted">No final ZIP uploaded yet.</p>
                    @endforelse
                  </div>
                </section>
              </div>
            </div>
          </article>
        @empty
          <p class="panel-muted">Create a project first to upload gallery and delivery files.</p>
        @endforelse
      </div>
    </article>

    <x-panel-delete-confirm-modal modal-id="client-media-delete-confirm-modal" trigger-selector="[data-delete-trigger]" title="Delete Media File" />
    <x-panel-gallery-viewer
      modal-id="client-media-gallery-viewer"
      open-selector="[data-gallery-open]"
      title-default="Gallery Viewer"
      :delete-enabled="true"
      delete-url-template="{{ url('/admin/projects/__PROJECT__/media/__MEDIA__/delete') }}"
      csrf-token="{{ csrf_token() }}"
    />

    <article class="panel-card">
      <h2 class="panel-section-title">Invoices</h2>
      @if($canManagePipeline)
      <p class="panel-muted invoice-recipient">Invoice recipient: {{ $client->email ?: ($client->phone ?: 'No contact set') }}</p>
      <form method="post" action="{{ route('admin.clients.invoices.store', $client) }}" class="panel-stack">
        @csrf
        <div class="panel-form-row">
          <select class="panel-select" name="client_project_id">
            <option value="">Link project (optional)</option>
            @foreach($visibleProjects as $project)
            <option value="{{ $project->id }}" @selected($focusedProjectId === (int) $project->id)>{{ $project->title }}</option>
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
      @endif

      <div class="panel-table-wrap">
        <table class="panel-table">
          <thead><tr><th>Invoice #</th><th>Project</th><th>Amount</th><th>Status</th><th>Due</th><th>Action</th></tr></thead>
          <tbody>
            @forelse($visibleInvoices as $invoice)
            <tr>
              <td>{{ $invoice->invoice_number }}</td>
              <td>{{ $invoice->project?->title ?: '-' }}</td>
              <td>{{ number_format((float) $invoice->amount, 2) }} {{ $invoice->currency }}</td>
              <td><span class="panel-badge">{{ $invoice->status }}</span></td>
              <td>{{ $invoice->due_date?->format('Y-m-d') ?: '-' }}</td>
              <td>
                @if($canManagePipeline)
                <a class="panel-link" href="{{ route('admin.invoices.download', $invoice) }}">Download PDF</a>
                <form method="post" action="{{ route('admin.invoices.delete', $invoice) }}" data-app-confirm="1" data-confirm-message="Delete invoice {{ $invoice->invoice_number }}?" class="inline-delete-form">
                  @csrf
                  <button class="panel-btn panel-btn-danger panel-btn-icon" type="submit" title="Delete invoice" aria-label="Delete invoice"><span class="panel-icon-trash" aria-hidden="true"><svg viewBox="0 0 20 20"><path d="M5 6h10M8 6V4h4v2m-6 0l.5 9h7L14 6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span></button>
                </form>
                @else
                <span class="panel-muted">No actions</span>
                @endif
              </td>
            </tr>
            @empty
            <tr><td colspan="6" class="panel-muted">No invoices yet.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </article>
  </div>

  <aside class="panel-side-col">
    <div class="panel-side-sticky">
      <article class="panel-card js-collapsible-card" data-collapsible-card>
        <div class="panel-card-head">
          <h2 class="panel-section-title">Send Message</h2>
          <button class="panel-card-toggle" type="button" data-collapsible-toggle>Minimize</button>
        </div>
        <div class="panel-card-body">
          @if($canManagePipeline)
          <form method="post" action="{{ route('admin.clients.messages.store', $client) }}" class="panel-stack">
            @csrf
            <select class="panel-select" name="client_project_id">
              <option value="">General message</option>
              @foreach($visibleProjects as $project)
              <option value="{{ $project->id }}" @selected($focusedProjectId === (int) $project->id)>{{ $project->title }}</option>
              @endforeach
            </select>
            <textarea class="panel-textarea" name="message" placeholder="Write a message to client..." required></textarea>
            <button class="panel-btn panel-btn-primary" type="submit">Send Message</button>
          </form>
          @else
          <p class="panel-muted">Message sending is available for manager-level roles.</p>
          @endif
        </div>
      </article>

      <article class="panel-card js-collapsible-card" data-collapsible-card>
        <div class="panel-card-head">
          <h2 class="panel-section-title">Message Timeline</h2>
          <button class="panel-card-toggle" type="button" data-collapsible-toggle>Minimize</button>
        </div>
        <div class="panel-card-body">
          <div class="panel-chat-list">
            @forelse($visibleMessages as $message)
            <div class="panel-chat-item {{ $message->sender_role === 'client' ? 'is-user' : 'is-assistant' }}">
              <p class="panel-chat-role">{{ strtoupper($message->sender_role) }}</p>
              <p class="panel-chat-text">{{ $message->message }}</p>
              <p class="panel-muted">{{ $message->sent_at?->format('Y-m-d H:i') ?: $message->created_at?->format('Y-m-d H:i') }}</p>
            </div>
            @empty
            <p class="panel-muted">No messages yet.</p>
            @endforelse
          </div>
        </div>
      </article>

      <article class="panel-card js-collapsible-card" data-collapsible-card>
        <div class="panel-card-head">
          <h2 class="panel-section-title">Service Requests</h2>
          <button class="panel-card-toggle" type="button" data-collapsible-toggle>Minimize</button>
        </div>
        <div class="panel-card-body">
          <div class="panel-form-row service-filter-row">
            <button class="panel-btn panel-btn-primary" type="button" data-service-filter="all">All</button>
            <button class="panel-btn" type="button" data-service-filter="addon">Add-on (Project-linked)</button>
            <button class="panel-btn" type="button" data-service-filter="general">General</button>
          </div>
          <div class="panel-table-wrap">
            <table class="panel-table">
              <thead><tr><th>Service</th><th>Project</th><th>Status</th><th>Action</th></tr></thead>
              <tbody>
                @forelse($visibleServiceRequests as $requestItem)
                <tr data-service-type="{{ $requestItem->project ? 'addon' : 'general' }}">
                  <td>
                    <strong>{{ $requestItem->requested_service }}</strong>
                    <div>
                      <span class="panel-badge">{{ $requestItem->project ? 'ADD-ON' : 'GENERAL' }}</span>
                    </div>
                    @if(!blank($requestItem->subject))
                      <div class="panel-muted">{{ $requestItem->subject }}</div>
                    @endif
                    @if(!blank($requestItem->details))
                      <div class="panel-muted">{{ $requestItem->details }}</div>
                    @endif
                    <div class="panel-muted">Preferred: {{ $requestItem->preferred_date?->format('Y-m-d') ?: '-' }}</div>
                  </td>
                  <td>{{ $requestItem->project?->title ?: '-' }}</td>
                  <td><span class="panel-badge">{{ $requestItem->status }}</span></td>
                  <td>
                    @if($canManagePipeline)
                    <form method="post" action="{{ route('admin.service-requests.status', $requestItem) }}" class="panel-stack">
                      @csrf
                      <div class="panel-form-row">
                        <select class="panel-select" name="status">
                          @foreach(['new','accepted','in_progress','completed','closed'] as $status)
                          <option value="{{ $status }}" @selected($requestItem->status === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                          @endforeach
                        </select>
                        <label class="panel-muted inline-checkbox-label">
                          <input type="checkbox" name="create_invoice" value="1">
                          Create invoice
                        </label>
                      </div>
                      <div class="panel-form-row">
                        <input class="panel-input" type="number" step="0.01" min="0" name="invoice_amount" placeholder="Invoice amount">
                        <input class="panel-input" type="text" name="invoice_currency" value="USD" placeholder="Currency">
                        <input class="panel-input" type="date" name="invoice_due_date">
                      </div>
                      <textarea class="panel-textarea" name="invoice_notes" placeholder="Invoice notes (optional)"></textarea>
                      <input class="panel-input" type="text" name="timeline_note" placeholder="Timeline note for client (optional)">
                      <button class="panel-btn" type="submit">Save</button>
                    </form>
                    @else
                    <span class="panel-muted">Read only</span>
                    @endif
                  </td>
                </tr>
                @empty
                <tr><td colspan="4" class="panel-muted">No service requests yet.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>

        <script>
          (function () {
            var filterButtons = document.querySelectorAll('[data-service-filter]');
            var rows = document.querySelectorAll('tr[data-service-type]');
            var collapsibleCards = document.querySelectorAll('[data-collapsible-card]');
            var mediaCards = document.querySelectorAll('[data-project-media-card]');

            collapsibleCards.forEach(function (card) {
              var toggle = card.querySelector('[data-collapsible-toggle]');
              if (!toggle) return;

              toggle.addEventListener('click', function () {
                card.classList.toggle('is-collapsed');
                toggle.textContent = card.classList.contains('is-collapsed') ? 'Expand' : 'Minimize';
              });
            });

            mediaCards.forEach(function (card) {
              var details = card.querySelector('[data-project-details]');
              var toggle = card.querySelector('[data-project-toggle]');
              var listToggle = card.querySelector('[data-gallery-list-toggle]');
              var galleryRows = card.querySelectorAll('[data-gallery-row]');

              if (details && toggle) {
                var collapsed = mediaCards.length > 1;
                details.hidden = collapsed;
                card.classList.toggle('is-collapsed', collapsed);
                toggle.setAttribute('aria-expanded', String(!collapsed));

                toggle.addEventListener('click', function () {
                  var willCollapse = !card.classList.contains('is-collapsed');
                  card.classList.toggle('is-collapsed', willCollapse);
                  details.hidden = willCollapse;
                  toggle.setAttribute('aria-expanded', String(!willCollapse));
                });
              }

              if (listToggle && galleryRows.length > 2) {
                listToggle.addEventListener('click', function () {
                  var expand = listToggle.getAttribute('aria-expanded') !== 'true';
                  listToggle.setAttribute('aria-expanded', String(expand));
                  listToggle.textContent = expand
                    ? 'Show Less Media List'
                    : 'Show All Media List (' + galleryRows.length + ')';

                  galleryRows.forEach(function (row, index) {
                    if (index < 2) return;
                    row.classList.toggle('is-hidden-by-default', !expand);
                  });
                });
              }
            });

            if (!filterButtons.length || !rows.length) return;

            var applyFilter = function (filter) {
              rows.forEach(function (row) {
                var type = row.getAttribute('data-service-type') || 'general';
                var visible = filter === 'all' || filter === type;
                row.style.display = visible ? '' : 'none';
              });

              filterButtons.forEach(function (button) {
                var isActive = (button.getAttribute('data-service-filter') || 'all') === filter;
                button.classList.toggle('panel-btn-primary', isActive);
              });
            };

            filterButtons.forEach(function (button) {
              button.addEventListener('click', function () {
                applyFilter(button.getAttribute('data-service-filter') || 'all');
              });
            });

            applyFilter('all');
          })();
        </script>
      </article>
    </div>
  </aside>
</section>
@endsection
