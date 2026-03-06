@extends('layouts.panel', [
  'title' => 'Media Delivery',
  'heading' => 'Media Delivery Workspace',
  'subheading' => 'Upload gallery media, upload final ZIP, and manage paid/unpaid delivery in one place.',
])

@section('content')
<section class="panel-card">
  <div class="panel-sticky-filters">
    <form method="get" class="panel-form-row">
      <input class="panel-input" type="text" name="media_search" value="{{ $filters['media_search'] }}" placeholder="Search project/client/service/address">
      <button class="panel-btn panel-btn-primary" type="submit">Search</button>
      <a class="panel-link" href="{{ route('admin.media-delivery.index') }}">Clear</a>
      <a class="panel-link" href="{{ route('admin.media-delivery.watermark.index') }}">Watermark Settings</a>
    </form>
  </div>

  <div class="panel-stack">
    @forelse($projects as $project)
    @php
      $galleryItems = $project->media->whereIn('type', ['image', 'video'])->values();
      $zipItems = $project->media->where('type', 'final_zip')->values();
      $isPaid = $project->invoices->contains(fn($invoice) => $invoice->status === 'paid');
      $projectGalleryPayload = $galleryPayloadByProject[$project->id] ?? [];
    @endphp
    <article class="panel-card media-project-card" data-project-media-card="{{ $project->id }}" data-project-id="{{ $project->id }}">
      <div class="panel-form-row media-project-header" style="justify-content: space-between; align-items: flex-start; gap: 12px; flex-wrap: wrap;">
        <x-project-media-summary
          :project="$project"
          :gallery-count="$galleryItems->count()"
          :zip-count="$zipItems->count()"
          :is-paid="$isPaid"
          :show-client="true"
        />
        <div class="panel-form-row" style="margin-bottom: 0;">
          <button class="panel-btn media-project-toggle" type="button" data-project-toggle aria-expanded="true" aria-label="Toggle project details">
            <svg class="media-project-toggle-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 10l4 4 4-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </button>
          <a class="panel-link" href="{{ route('admin.clients.show', $project->client_id) }}">Open Client</a>
          @if($canViewInvoices ?? false)
          <a class="panel-link" href="{{ route('admin.invoices.index', ['invoice_project' => $project->id]) }}">Project Invoice</a>
          @endif
          @if($galleryItems->isNotEmpty())
          <button
            class="panel-btn panel-btn-primary"
            type="button"
            data-gallery-open
            data-project-id="{{ $project->id }}"
            data-gallery-items='@json($projectGalleryPayload)'
          >
            Open Gallery Viewer
          </button>
          @endif
        </div>
      </div>

      <div class="media-project-details" data-project-details>

      @if($canManageMedia)
      <div class="panel-form-row" style="align-items: flex-end; margin-top: 12px;">
        <form method="post" action="{{ route('admin.projects.media.store', $project) }}" class="panel-stack" enctype="multipart/form-data" style="flex: 1; min-width: 260px;">
          @csrf
          <label class="panel-muted">Upload Gallery Images/Videos</label>
          <input class="panel-input" type="file" name="media_files[]" accept="image/*,video/*" multiple required>
          <button class="panel-btn panel-btn-primary" type="submit">Upload Gallery</button>
        </form>

        <form method="post" action="{{ route('admin.projects.delivery-zip.store', $project) }}" class="panel-stack" enctype="multipart/form-data" style="flex: 1; min-width: 260px;">
          @csrf
          <label class="panel-muted">Upload Final Delivery ZIP</label>
          <input class="panel-input" type="file" name="delivery_zip" accept=".zip,application/zip" required>
          <button class="panel-btn" type="submit">Upload Final ZIP</button>
        </form>
      </div>
      @endif

      <div class="panel-grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); margin-top: 12px; gap: 12px;">
        <section class="panel-card" style="margin: 0;">
          <h4 class="panel-section-title">Gallery Files</h4>
          <div class="panel-stack">
            @forelse($galleryItems as $mediaItem)
            <div class="panel-form-row" style="justify-content: space-between; margin-bottom: 0;">
              <span><strong>{{ strtoupper($mediaItem->type) }}</strong> — {{ $mediaItem->original_name }}</span>
              <div class="panel-form-row" style="margin-bottom: 0;">
                <a class="panel-link" href="{{ route('admin.projects.media.view', ['project' => $project, 'media' => $mediaItem]) }}" target="_blank" rel="noopener">View</a>
                @if($canManageMedia)
                <form method="post" action="{{ route('admin.projects.media.delete', ['project' => $project, 'media' => $mediaItem]) }}" data-delete-form data-delete-name="{{ $mediaItem->original_name }}">
                  @csrf
                  <button class="panel-btn" type="button" data-delete-trigger>Delete</button>
                </form>
                @endif
              </div>
            </div>
            @empty
            <p class="panel-muted">No gallery files yet.</p>
            @endforelse
          </div>
        </section>

        <section class="panel-card" style="margin: 0;">
          <h4 class="panel-section-title">Final Delivery ZIP</h4>
          <div class="panel-stack">
            @forelse($zipItems as $zipItem)
            <div class="panel-form-row" style="justify-content: space-between; margin-bottom: 0;">
              <span>{{ $zipItem->original_name }}</span>
              <div class="panel-form-row" style="margin-bottom: 0;">
                <a class="panel-link" href="{{ route('admin.projects.media.view', ['project' => $project, 'media' => $zipItem]) }}" target="_blank" rel="noopener">View ZIP</a>
                @if($canManageMedia)
                <form method="post" action="{{ route('admin.projects.media.delete', ['project' => $project, 'media' => $zipItem]) }}" data-delete-form data-delete-name="{{ $zipItem->original_name }}">
                  @csrf
                  <button class="panel-btn" type="button" data-delete-trigger>Delete</button>
                </form>
                @endif
              </div>
            </div>
            @empty
            <p class="panel-muted">No final ZIP uploaded yet.</p>
            @endforelse
          </div>
        </section>
      </div>
      </div>
    </article>
    @empty
    <p class="panel-muted">No projects found.</p>
    @endforelse
  </div>

  <x-panel-pagination :paginator="$projects" />

  @if(!$canManageMedia)
  <p class="panel-muted" style="margin-top: 1rem;">Your role is read-only for media uploads. Contact an admin/owner/manager to upload files.</p>
  @endif
</section>

<script>
  (function () {
    const cards = document.querySelectorAll('[data-project-media-card]');
    if (!cards.length) return;

    const storageKey = 'maccento_media_delivery_collapsed';
    let collapsedMap = {};

    try {
      const raw = window.localStorage.getItem(storageKey);
      collapsedMap = raw ? JSON.parse(raw) : {};
    } catch (error) {
      collapsedMap = {};
    }

    const persist = function () {
      try {
        window.localStorage.setItem(storageKey, JSON.stringify(collapsedMap));
      } catch (error) {
      }
    };

    cards.forEach(function (card) {
      const projectId = card.getAttribute('data-project-id');
      const details = card.querySelector('[data-project-details]');
      const toggle = card.querySelector('[data-project-toggle]');
      if (!projectId || !details || !toggle) {
        return;
      }

      const applyState = function (collapsed) {
        card.classList.toggle('is-collapsed', collapsed);
        details.hidden = collapsed;
        toggle.setAttribute('aria-expanded', String(!collapsed));
      };

      const initiallyCollapsed = Object.prototype.hasOwnProperty.call(collapsedMap, projectId)
        ? Boolean(collapsedMap[projectId])
        : true;

      applyState(initiallyCollapsed);

      toggle.addEventListener('click', function () {
        const willCollapse = !card.classList.contains('is-collapsed');
        collapsedMap[projectId] = willCollapse;
        applyState(willCollapse);
        persist();
      });
    });
  })();
</script>

<x-panel-gallery-viewer modal-id="media-delivery-viewer" open-selector="[data-gallery-open]" title-default="Gallery Viewer" />
<x-panel-delete-confirm-modal modal-id="media-delete-confirm-modal" trigger-selector="[data-delete-trigger]" title="Delete Media File" />
@endsection
