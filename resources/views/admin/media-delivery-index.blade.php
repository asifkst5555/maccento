@extends('layouts.panel', [
  'title' => 'Media Delivery',
  'heading' => 'Media Delivery Workspace',
  'subheading' => 'Upload gallery media, upload final ZIP, and manage paid/unpaid delivery in one place.',
])

@section('content')
<style>
  .media-delivery-upload-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
    margin-top: 12px;
  }

  .media-delivery-upload-card {
    border: 1px solid #d8e1ec;
    border-radius: 12px;
    background: #f9fbff;
    padding: 12px;
  }

  .media-delivery-upload-card .panel-section-title {
    margin-bottom: 8px;
    font-size: 1rem;
  }

  .media-delivery-upload-card .panel-stack {
    margin-bottom: 0;
  }

  .media-delivery-files-grid {
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    margin-top: 12px;
    gap: 12px;
  }

  .media-file-list-card {
    margin: 0;
    border: 1px solid #d8e2ef;
    background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
  }

  .media-file-list {
    display: grid;
    gap: 10px;
  }

  .media-file-row {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 10px;
    padding: 10px 12px;
    border: 1px solid #e1e9f4;
    border-radius: 10px;
    background: #fff;
  }

  .media-file-meta {
    min-width: 0;
    display: grid;
    gap: 4px;
  }

  .media-file-kind {
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

  .media-file-name {
    color: #1e3450;
    font-weight: 600;
    word-break: break-word;
  }

  .media-file-actions {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    flex-wrap: nowrap;
  }

  .media-file-list-cta {
    margin-top: 4px;
    justify-content: flex-end;
  }

  .media-file-list-cta-group {
    justify-content: flex-end;
    gap: 8px;
    flex-wrap: wrap;
  }

  .media-file-row.is-hidden-by-default {
    display: none;
  }

  @media (max-width: 960px) {
    .media-delivery-upload-grid {
      grid-template-columns: 1fr;
    }
  }

  @media (max-width: 640px) {
    .media-file-row {
      flex-direction: column;
      align-items: stretch;
    }

    .media-file-actions,
    .media-file-list-cta,
    .media-file-list-cta-group {
      width: 100%;
      justify-content: stretch;
    }

    .media-file-actions > *,
    .media-file-list-cta-group > * {
      width: 100%;
    }

    .media-delivery-upload-card .panel-btn,
    .media-delivery-upload-card .panel-input,
    .media-delivery-upload-card .panel-link {
      width: 100%;
    }

    .media-project-header > .panel-form-row {
      width: 100%;
      align-items: stretch !important;
    }

    .media-project-header > .panel-form-row > * {
      width: 100%;
      flex: 1 1 100%;
    }

    .media-project-toggle {
      height: 42px;
    }

    .panel-sticky-filters .panel-form-row {
      align-items: stretch;
    }
  }
</style>
<section class="panel-card">
  @php
    $canOpenWatermarkSettings = in_array(strtolower(trim((string) auth()->user()?->role)), ['owner', 'admin', 'manager'], true);
  @endphp
  <div class="panel-sticky-filters">
    <form method="get" class="panel-form-row">
      <input class="panel-input" type="text" name="media_search" value="{{ $filters['media_search'] }}" placeholder="Search project/client/service/address">
      <button class="panel-btn panel-btn-primary" type="submit">Search</button>
      <a class="panel-link" href="{{ route('admin.media-delivery.index') }}">Clear</a>
      @if($canOpenWatermarkSettings)
      <a class="panel-link" href="{{ route('admin.media-delivery.watermark.index') }}">Watermark Settings</a>
      @endif
    </form>
  </div>

  <div class="panel-stack">
    @if($isScopedMediaUser)
    <p class="panel-muted">Showing only projects assigned to you. Uploads are stored under each project in your role and user folder.</p>
    @endif
    @forelse($projects as $project)
    @php
      $rawItems = $project->media->filter(fn ($item) => in_array($item->type, ['image', 'video'], true) && (($item->delivery_stage ?? null) !== 'edited'))->values();
      $rawZipItems = $project->media->where('type', 'raw_zip')->values();
      $editedItems = $project->media->filter(fn ($item) => in_array($item->type, ['image', 'video'], true) && (($item->delivery_stage ?? null) === 'edited'))->values();
      $galleryItems = $rawItems->concat($editedItems)->values();
      $zipItems = $project->media->where('type', 'final_zip')->values();
      $isPaid = $project->invoices->contains(fn($invoice) => $invoice->status === 'paid');
      $preferredPreviewItems = $editedItems->isNotEmpty() ? $editedItems : $rawItems;
      $projectGalleryPayload = $preferredPreviewItems->map(function ($item) use ($project) {
        return [
          'id' => (int) $item->id,
          'name' => (string) $item->original_name,
          'type' => (string) $item->type,
          'mime' => (string) ($item->mime_type ?? ''),
          'url' => route('admin.projects.media.view', ['project' => $project, 'media' => $item]),
        ];
      })->all();
      $rawGalleryPayload = $rawItems->map(function ($item) use ($project) {
        return [
          'id' => (int) $item->id,
          'name' => (string) $item->original_name,
          'type' => (string) $item->type,
          'mime' => (string) ($item->mime_type ?? ''),
          'url' => route('admin.projects.media.view', ['project' => $project, 'media' => $item]),
        ];
      })->all();
      $editedGalleryPayload = $editedItems->map(function ($item) use ($project) {
        return [
          'id' => (int) $item->id,
          'name' => (string) $item->original_name,
          'type' => (string) $item->type,
          'mime' => (string) ($item->mime_type ?? ''),
          'url' => route('admin.projects.media.view', ['project' => $project, 'media' => $item]),
        ];
      })->all();
    @endphp
    <article class="panel-card media-project-card" data-project-media-card="{{ $project->id }}" data-project-id="{{ $project->id }}">
      @php
        $assignmentIds = $project->assignments->pluck('user_id')->map(static fn ($id): int => (int) $id)->all();
        $canUploadProjectMedia = ($canUploadMedia ?? false) && (!($isScopedMediaUser ?? false) || in_array((int) auth()->id(), $assignmentIds, true));
      @endphp
      <div class="panel-form-row media-project-header" style="justify-content: space-between; align-items: flex-start; gap: 12px; flex-wrap: wrap;">
        <x-project-media-summary
          :project="$project"
          :gallery-count="$galleryItems->count()"
          :raw-count="$rawItems->count()"
          :edited-count="$editedItems->count()"
          :zip-count="$zipItems->count()"
          :is-paid="$isPaid"
          :show-client="true"
        />
        <div class="panel-form-row" style="margin-bottom: 0;">
          <button class="panel-btn panel-btn-primary media-project-toggle" type="button" data-project-toggle aria-expanded="true" aria-label="Toggle project details" style="color: #fff; border-color: #a8162a;">
            <svg class="media-project-toggle-icon" viewBox="0 0 24 24" aria-hidden="true" style="width: 32px; height: 32px; color: #fff;"><path d="M8 10l4 4 4-4" fill="none" stroke="#ffffff" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </button>
          @if($preferredPreviewItems->isNotEmpty())
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
          <a class="panel-link" href="{{ route('admin.clients.show', ['client' => $project->client_id, 'project_id' => $project->id]) }}">Open Project</a>
          <a class="panel-link" href="{{ route('admin.clients.show', $project->client_id) }}">Open Client</a>
          @if($canViewInvoices ?? false)
          <a class="panel-link" href="{{ route('admin.invoices.index', ['invoice_project' => $project->id]) }}">Project Invoice</a>
          @endif
        </div>
      </div>

      <div class="media-project-details" data-project-details>

      @if($canUploadProjectMedia)
      <div class="media-stage-section">
        <div class="media-delivery-upload-grid">
          <article class="media-delivery-upload-card">
            <h4 class="panel-section-title">Raw Media Gallery</h4>
            <form method="post" action="{{ route('admin.projects.media.store', $project) }}" class="panel-stack" enctype="multipart/form-data">
              @csrf
              <input type="hidden" name="media_stage" value="raw">
              <label class="panel-muted">Upload Raw Footage Media</label>
              <input class="panel-input" type="file" name="media_files[]" accept="image/*,video/*" multiple required>
              <button class="panel-btn panel-btn-primary" type="submit">Upload Raw Footage</button>
            </form>
            <form method="post" action="{{ route('admin.projects.raw-zip.store', $project) }}" class="panel-stack" enctype="multipart/form-data">
              @csrf
              <label class="panel-muted">Upload Raw Footage ZIP</label>
              <input class="panel-input" type="file" name="raw_zip" accept=".zip,application/zip" required>
              <button class="panel-btn" type="submit">Upload Raw ZIP</button>
            </form>
          </article>

          <section class="panel-card media-file-list-card">
            <h4 class="panel-section-title">Raw Footage Media</h4>
            <div class="media-file-list">
              @forelse($rawItems as $index => $mediaItem)
              @php
                $mediaName = $mediaItem->original_name;
              @endphp
              <article class="media-file-row @if($index >= 2) is-hidden-by-default @endif" data-gallery-row>
                <div class="media-file-meta">
                  <span class="media-file-kind">{{ strtoupper($mediaItem->type) }}</span>
                  <span class="media-file-name">{{ $mediaName }}</span>
                  <span class="panel-muted">Uploaded by {{ $mediaItem->uploader?->name ?: 'System' }} @if($mediaItem->uploader?->role)&bull; {{ ucfirst($mediaItem->uploader->role) }} @endif</span>
                </div>
                <div class="media-file-actions">
                  <a class="panel-link" href="{{ route('admin.projects.media.view', ['project' => $project, 'media' => $mediaItem]) }}" target="_blank" rel="noopener">View</a>
                  @if($canDeleteMedia)
                  <form method="post" action="{{ route('admin.projects.media.delete', ['project' => $project, 'media' => $mediaItem]) }}" data-delete-form data-delete-name="{{ $mediaItem->original_name }}">
                    @csrf
                    <button class="panel-btn panel-btn-danger panel-btn-icon" type="button" data-delete-trigger title="Delete media" aria-label="Delete media"><span class="panel-icon-trash" aria-hidden="true"><svg viewBox="0 0 20 20"><path d="M5 6h10M8 6V4h4v2m-6 0l.5 9h7L14 6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span></button>
                  </form>
                  @endif
                </div>
              </article>
              @empty
              <p class="panel-muted">No raw footage media yet.</p>
              @endforelse
              @if($rawZipItems->isNotEmpty())
                @foreach($rawZipItems as $rawZipItem)
                <article class="media-file-row">
                  <div class="media-file-meta">
                    <span class="media-file-kind">RAW ZIP</span>
                    <span class="media-file-name">{{ $rawZipItem->original_name }}</span>
                    <span class="panel-muted">Uploaded by {{ $rawZipItem->uploader?->name ?: 'System' }} @if($rawZipItem->uploader?->role)&bull; {{ ucfirst($rawZipItem->uploader->role) }} @endif</span>
                  </div>
                  <div class="media-file-actions">
                    <a class="panel-link" href="{{ route('admin.projects.media.view', ['project' => $project, 'media' => $rawZipItem]) }}" target="_blank" rel="noopener">View ZIP</a>
                    @if($canDeleteMedia)
                    <form method="post" action="{{ route('admin.projects.media.delete', ['project' => $project, 'media' => $rawZipItem]) }}" data-delete-form data-delete-name="{{ $rawZipItem->original_name }}">
                      @csrf
                      <button class="panel-btn panel-btn-danger panel-btn-icon" type="button" data-delete-trigger title="Delete raw ZIP" aria-label="Delete raw ZIP"><span class="panel-icon-trash" aria-hidden="true"><svg viewBox="0 0 20 20"><path d="M5 6h10M8 6V4h4v2m-6 0l.5 9h7L14 6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span></button>
                    </form>
                    @endif
                  </div>
                </article>
                @endforeach
              @endif
              @if($rawItems->count() > 2)
              <div class="panel-form-row media-file-list-cta media-file-list-cta-group">
                <button class="panel-btn" type="button" data-gallery-list-toggle aria-expanded="false">Show All Raw Media ({{ $rawItems->count() }})</button>
                <button class="panel-btn panel-btn-primary" type="button" data-gallery-open data-project-id="{{ $project->id }}" data-gallery-items='@json($rawGalleryPayload)'>View Raw Footage ({{ $rawItems->count() }})</button>
              </div>
              @endif
            </div>
          </section>
        </div>
      </div>
      @endif

      <div class="media-stage-section">
        <div class="media-delivery-upload-grid">
          @if($canUploadProjectMedia)
          <article class="media-delivery-upload-card">
            <h4 class="panel-section-title">Edited/Final Media Upload</h4>
            <form method="post" action="{{ route('admin.projects.media.store', $project) }}" class="panel-stack" enctype="multipart/form-data">
              @csrf
              <input type="hidden" name="media_stage" value="edited">
              <label class="panel-muted">Upload Edited/Final Media Files</label>
              <input class="panel-input" type="file" name="media_files[]" accept="image/*,video/*" multiple required>
              <button class="panel-btn panel-btn-primary" type="submit">Upload Edited/Final Media</button>
            </form>
          </article>
          @endif

          <section class="panel-card media-file-list-card">
            <h4 class="panel-section-title">Edited/Final Media Files</h4>
            <div class="media-file-list">
              @forelse($editedItems as $index => $mediaItem)
              @php
                $mediaName = $mediaItem->original_name;
              @endphp
              <article class="media-file-row @if($index >= 2) is-hidden-by-default @endif" data-gallery-row>
                <div class="media-file-meta">
                  <span class="media-file-kind">{{ strtoupper($mediaItem->type) }}</span>
                  <span class="media-file-name">{{ $mediaName }}</span>
                  <span class="panel-muted">Uploaded by {{ $mediaItem->uploader?->name ?: 'System' }} @if($mediaItem->uploader?->role)&bull; {{ ucfirst($mediaItem->uploader->role) }} @endif</span>
                </div>
                <div class="media-file-actions">
                  <a class="panel-link" href="{{ route('admin.projects.media.view', ['project' => $project, 'media' => $mediaItem]) }}" target="_blank" rel="noopener">View</a>
                  @if($canDeleteMedia)
                  <form method="post" action="{{ route('admin.projects.media.delete', ['project' => $project, 'media' => $mediaItem]) }}" data-delete-form data-delete-name="{{ $mediaItem->original_name }}">
                    @csrf
                    <button class="panel-btn panel-btn-danger panel-btn-icon" type="button" data-delete-trigger title="Delete media" aria-label="Delete media"><span class="panel-icon-trash" aria-hidden="true"><svg viewBox="0 0 20 20"><path d="M5 6h10M8 6V4h4v2m-6 0l.5 9h7L14 6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span></button>
                  </form>
                  @endif
                </div>
              </article>
              @empty
              <p class="panel-muted">No edited/final media files yet.</p>
              @endforelse
              @if($editedItems->count() > 2)
              <div class="panel-form-row media-file-list-cta media-file-list-cta-group">
                <button class="panel-btn" type="button" data-gallery-list-toggle aria-expanded="false">Show All Edited/Final Media ({{ $editedItems->count() }})</button>
                <button class="panel-btn panel-btn-primary" type="button" data-gallery-open data-project-id="{{ $project->id }}" data-gallery-items='@json($editedGalleryPayload)'>View Edited/Final Media ({{ $editedItems->count() }})</button>
              </div>
              @endif
            </div>
          </section>
        </div>
      </div>

      <div class="media-stage-section">
        <div class="media-delivery-upload-grid">
          @if($canUploadProjectMedia)
          <article class="media-delivery-upload-card">
            <h4 class="panel-section-title">Final ZIP Upload</h4>
            <form method="post" action="{{ route('admin.projects.delivery-zip.store', $project) }}" class="panel-stack" enctype="multipart/form-data">
              @csrf
              <label class="panel-muted">Upload Final Delivery ZIP</label>
              <input class="panel-input" type="file" name="delivery_zip" accept=".zip,application/zip" required>
              <button class="panel-btn" type="submit">Upload Final ZIP</button>
            </form>
          </article>
          @endif

          <section class="panel-card media-file-list-card">
            <h4 class="panel-section-title">Final Delivery ZIP</h4>
            <div class="media-file-list">
              @forelse($zipItems as $zipItem)
              @php
                $zipName = $zipItem->original_name;
              @endphp
              <article class="media-file-row">
                <div class="media-file-meta">
                  <span class="media-file-kind">ZIP</span>
                  <span class="media-file-name">{{ $zipName }}</span>
                  <span class="panel-muted">Uploaded by {{ $zipItem->uploader?->name ?: 'System' }} @if($zipItem->uploader?->role)&bull; {{ ucfirst($zipItem->uploader->role) }} @endif</span>
                </div>
                <div class="media-file-actions">
                  <a class="panel-link" href="{{ route('admin.projects.media.view', ['project' => $project, 'media' => $zipItem]) }}" target="_blank" rel="noopener">View ZIP</a>
                  @if($canDeleteMedia)
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
      </div>
    </article>
    @empty
    <p class="panel-muted">No projects found.</p>
    @endforelse
  </div>

  <x-panel-pagination :paginator="$projects" />

  @if(!$canUploadMedia)
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

      const listToggle = card.querySelector('[data-gallery-list-toggle]');
      const galleryRows = card.querySelectorAll('[data-gallery-row]');
      if (listToggle && galleryRows.length > 2) {
        listToggle.addEventListener('click', function () {
          const expand = listToggle.getAttribute('aria-expanded') !== 'true';
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
  })();
</script>

<x-panel-gallery-viewer
  modal-id="media-delivery-viewer"
  open-selector="[data-gallery-open]"
  title-default="Gallery Viewer"
  :delete-enabled="true"
  delete-url-template="{{ url('/admin/projects/__PROJECT__/media/__MEDIA__/delete') }}"
  csrf-token="{{ csrf_token() }}"
/>
<x-panel-delete-confirm-modal modal-id="media-delete-confirm-modal" trigger-selector="[data-delete-trigger]" title="Delete Media File" />
@endsection
