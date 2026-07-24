@extends('layouts.panel', [
  'title' => 'Media Delivery',
  'heading' => 'Media Delivery Workspace',
  'subheading' => 'Upload gallery media, upload final ZIP, and manage paid/unpaid delivery in one place.',
])

@section('content')
@php
  $canManagePipeline = in_array(strtolower(trim((string) auth()->user()?->role)), ['owner', 'admin', 'manager'], true);
@endphp
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

  .project-discussion-card {
    border: 1px solid #d8e2ef;
    border-radius: 14px;
    background: #ffffff;
    padding: 16px;
    display: grid;
    gap: 12px;
  }

  .project-discussion-stream {
    display: grid;
    gap: 12px;
  }

  .project-comment-card {
    border: 1px solid #e1e9f4;
    border-radius: 12px;
    padding: 12px;
    background: #f7faff;
    display: grid;
    gap: 8px;
  }

  .project-comment-card.is-internal {
    background: #fff6f7;
    border-color: #f1c7cd;
  }

  .project-comment-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
  }

  .project-comment-author {
    display: flex;
    gap: 10px;
    align-items: center;
  }

  .project-comment-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #c02636;
    color: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.85rem;
  }

  .project-comment-name {
    font-weight: 700;
    margin: 0;
  }

  .project-comment-meta {
    font-size: 0.8rem;
    color: #6d7f95;
    margin: 2px 0 0;
  }

  .project-comment-time {
    font-size: 0.75rem;
    color: #7a8aa2;
  }

  .project-comment-edited {
    font-size: 0.7rem;
    color: #9a4f5d;
    margin-left: 6px;
  }

  .project-comment-body {
    font-size: 0.92rem;
    color: #1f2d42;
    white-space: pre-line;
  }

  .project-comment-actions {
    display: flex;
    gap: 14px;
    align-items: center;
    flex-wrap: wrap;
  }

  .project-comment-action {
    background: none;
    border: none;
    padding: 0;
    color: #4b5f7a;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    gap: 6px;
    align-items: center;
  }

  .project-comment-action.is-danger {
    color: #b21f34;
  }

  .project-comment-reply {
    border-left: 3px solid #cdd9ea;
    padding-left: 10px;
    display: grid;
    gap: 4px;
    color: #4f627a;
    font-size: 0.85rem;
  }

  .project-discussion-form {
    display: grid;
    gap: 8px;
  }

  .project-comment-reply-banner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 8px 10px;
    border-radius: 10px;
    background: #f0f4fb;
    border: 1px solid #d3deee;
  }

  .project-comment-reply-preview {
    font-size: 0.82rem;
    color: #4f6078;
  }

  .project-comment-reply-cancel {
    border: none;
    background: none;
    color: #b21f34;
    font-weight: 600;
    cursor: pointer;
  }

  .project-comment-edit-form {
    display: none;
    gap: 8px;
  }

  .project-comment-edit-form.is-open {
    display: grid;
  }

  .project-comment-edit-actions {
    display: flex;
    gap: 8px;
  }

  .project-comment-edit-form[hidden],
  .project-comment-reply-banner[hidden] {
    display: none !important;
  }


  .media-delivery-shell {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 320px;
    gap: 18px;
    align-items: start;
  }

  .media-delivery-shell .panel-side-sticky {
    display: grid;
    gap: 12px;
    position: sticky;
    top: 18px;
  }

  .project-comments-panel {
    display: grid;
    gap: 14px;
  }

  .project-comments-panel .project-discussion-stream {
    max-height: 360px;
    overflow: auto;
    padding-right: 4px;
  }

  @media (max-width: 1100px) {
    .media-delivery-shell {
      grid-template-columns: 1fr;
    }

    .media-delivery-shell .panel-side-sticky {
      position: static;
    }
  }

</style>

<section class="panel-two-col media-delivery-shell">
  <div class="panel-main-col">
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
       <button class="panel-btn panel-btn-primary" type="button" data-dropbox-modal-trigger style="background-color: #0061ff; border-color: #0061ff; display: inline-flex; align-items: center; gap: 6px; color: #fff; border-radius: 6px; font-weight: 600;">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M5.008 3.513l6.99 4.417-6.99 4.416L5.008 3.513zm13.984 0l-6.99 4.417 6.99 4.416.002-8.833zm-13.984 17.06l6.99-4.417-6.99-4.416-.002 8.833zm13.984 0l-6.99-4.417 6.99-4.416.002 8.833zM11.998 12.35l6.99-4.417-6.99-4.42-6.99 4.42 6.99 4.417z"/></svg>
        Import from Dropbox
      </button>
      <a class="panel-link shadow-sm border" href="{{ route('admin.media-delivery.import-history') }}" style="display: inline-flex; align-items: center; gap: 6px; font-weight: 600; color: #475569; background: #fff; padding: 6px 12px; border-radius: 6px; text-decoration: none; border-color: #e2e8f0; font-size: 0.85rem; height: 36px;">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        Import History
      </a>
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
    <article class="panel-card media-project-card" id="project-{{ $project->id }}" data-project-media-card="{{ $project->id }}" data-project-id="{{ $project->id }}">
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
          @if($canManagePipeline)
          <a class="panel-link" href="{{ route('admin.clients.show', ['client' => $project->client_id, 'project_id' => $project->id]) }}">Open Project</a>
          <a class="panel-link" href="{{ route('admin.clients.show', $project->client_id) }}">Open Client</a>
          @endif
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
            <button class="panel-btn" type="button" data-dropbox-modal-trigger data-preselect-project="{{ $project->id }}" data-preselect-stage="raw" style="border: 1px solid #0061ff; color: #0061ff; background: transparent; width:100%; margin-top:8px; font-weight:600;">Import from Dropbox</button>
            <form method="post" action="{{ route('admin.projects.raw-zip.store', $project) }}" class="panel-stack" enctype="multipart/form-data" style="margin-top:12px;">
              @csrf
              <label class="panel-muted">Upload Raw Footage ZIP</label>
              <input class="panel-input" type="file" name="raw_zip" accept=".zip,application/zip" required>
              <button class="panel-btn" type="submit">Upload Raw ZIP</button>
            </form>
          </article>

          <section class="panel-card media-file-list-card">
            <h4 class="panel-section-title">Raw Footage Media</h4>
            <div class="media-file-list" id="raw-media-list">
              @forelse($rawItems as $index => $mediaItem)
              @php
                $mediaName = $mediaItem->original_name;
                $canDeleteMediaItem = $canDeleteMedia || (int) ($mediaItem->uploaded_by ?? 0) === (int) auth()->id();
              @endphp
              <article class="media-file-row @if($index >= 2) is-hidden-by-default @endif" data-gallery-row>
                <div class="media-file-meta">
                  <span class="media-file-kind">{{ strtoupper($mediaItem->type) }}</span>
                  <span class="media-file-name">{{ $mediaName }}</span>
                  <span class="panel-muted">Uploaded by {{ $mediaItem->uploader?->name ?: 'System' }} @if($mediaItem->uploader?->role)&bull; {{ ucfirst($mediaItem->uploader->role) }} @endif</span>
                </div>
                <div class="media-file-actions">
                  <a class="panel-link" href="{{ route('admin.projects.media.view', ['project' => $project, 'media' => $mediaItem]) }}" target="_blank" rel="noopener">View</a>
                  @if($canDeleteMediaItem)
                  <form method="post" action="{{ route('admin.projects.media.delete', ['project' => $project, 'media' => $mediaItem], false) }}" data-delete-form data-delete-name="{{ $mediaItem->original_name }}">
                    @csrf
                    <button class="panel-btn panel-btn-danger panel-btn-icon" type="button" data-delete-trigger title="Delete media" aria-label="Delete media"><span class="panel-icon-trash" aria-hidden="true"><x-panel-icon name="trash" /></span></button>
                  </form>
                  @endif
                </div>
              </article>
              @empty
              <p class="panel-muted">No raw footage media yet.</p>
              @endforelse
              @if($rawZipItems->isNotEmpty())
                @foreach($rawZipItems as $rawZipItem)
                @php
                  $canDeleteRawZip = $canDeleteMedia || (int) ($rawZipItem->uploaded_by ?? 0) === (int) auth()->id();
                @endphp
                <article class="media-file-row">
                  <div class="media-file-meta">
                    <span class="media-file-kind">RAW ZIP</span>
                    <span class="media-file-name">{{ $rawZipItem->original_name }}</span>
                    <span class="panel-muted">Uploaded by {{ $rawZipItem->uploader?->name ?: 'System' }} @if($rawZipItem->uploader?->role)&bull; {{ ucfirst($rawZipItem->uploader->role) }} @endif</span>
                  </div>
                  <div class="media-file-actions">
                    <a class="panel-link" href="{{ route('admin.projects.media.view', ['project' => $project, 'media' => $rawZipItem]) }}" target="_blank" rel="noopener">Download ZIP</a>
                    @if($canDeleteRawZip)
                    <form method="post" action="{{ route('admin.projects.media.delete', ['project' => $project, 'media' => $rawZipItem], false) }}" data-delete-form data-delete-name="{{ $rawZipItem->original_name }}">
                      @csrf
                      <button class="panel-btn panel-btn-danger panel-btn-icon" type="button" data-delete-trigger title="Delete raw ZIP" aria-label="Delete raw ZIP"><span class="panel-icon-trash" aria-hidden="true"><x-panel-icon name="trash" /></span></button>
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
            <button class="panel-btn" type="button" data-dropbox-modal-trigger data-preselect-project="{{ $project->id }}" data-preselect-stage="edited" style="border: 1px solid #0061ff; color: #0061ff; background: transparent; width:100%; margin-top:8px; font-weight:600;">Import from Dropbox</button>
          </article>
          @endif

          <section class="panel-card media-file-list-card">
            <h4 class="panel-section-title">Edited/Final Media Files</h4>
            <div class="media-file-list" id="edited-media-list">
              @forelse($editedItems as $index => $mediaItem)
              @php
                $mediaName = $mediaItem->original_name;
                $canDeleteMediaItem = $canDeleteMedia || (int) ($mediaItem->uploaded_by ?? 0) === (int) auth()->id();
              @endphp
              <article class="media-file-row @if($index >= 2) is-hidden-by-default @endif" data-gallery-row>
                <div class="media-file-meta">
                  <span class="media-file-kind">{{ strtoupper($mediaItem->type) }}</span>
                  <span class="media-file-name">{{ $mediaName }}</span>
                  <span class="panel-muted">Uploaded by {{ $mediaItem->uploader?->name ?: 'System' }} @if($mediaItem->uploader?->role)&bull; {{ ucfirst($mediaItem->uploader->role) }} @endif</span>
                </div>
                <div class="media-file-actions">
                  <a class="panel-link" href="{{ route('admin.projects.media.view', ['project' => $project, 'media' => $mediaItem]) }}" target="_blank" rel="noopener">View</a>
                  @if($canDeleteMediaItem)
                  <form method="post" action="{{ route('admin.projects.media.delete', ['project' => $project, 'media' => $mediaItem], false) }}" data-delete-form data-delete-name="{{ $mediaItem->original_name }}">
                    @csrf
                    <button class="panel-btn panel-btn-danger panel-btn-icon" type="button" data-delete-trigger title="Delete media" aria-label="Delete media"><span class="panel-icon-trash" aria-hidden="true"><x-panel-icon name="trash" /></span></button>
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
            <div class="media-file-list" id="other-media-list">
              @forelse($zipItems as $zipItem)
              @php
                $zipName = $zipItem->original_name;
                $canDeleteFinalZip = $canDeleteMedia || (int) ($zipItem->uploaded_by ?? 0) === (int) auth()->id();
              @endphp
              <article class="media-file-row">
                <div class="media-file-meta">
                  <span class="media-file-kind">ZIP</span>
                  <span class="media-file-name">{{ $zipName }}</span>
                  <span class="panel-muted">Uploaded by {{ $zipItem->uploader?->name ?: 'System' }} @if($zipItem->uploader?->role)&bull; {{ ucfirst($zipItem->uploader->role) }} @endif</span>
                </div>
                <div class="media-file-actions">
                  <a class="panel-link" href="{{ route('admin.projects.media.view', ['project' => $project, 'media' => $zipItem]) }}" target="_blank" rel="noopener">Download ZIP</a>
                  @if($canDeleteFinalZip)
                  <form method="post" action="{{ route('admin.projects.media.delete', ['project' => $project, 'media' => $zipItem], false) }}" data-delete-form data-delete-name="{{ $zipItem->original_name }}">
                    @csrf
                    <button class="panel-btn panel-btn-danger panel-btn-icon" type="button" data-delete-trigger title="Delete ZIP" aria-label="Delete ZIP"><span class="panel-icon-trash" aria-hidden="true"><x-panel-icon name="trash" /></span></button>
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

  </div>

  <aside class="panel-side-col">
    <div class="panel-side-sticky">
      <article class="panel-card project-comments-panel">
        <h2 class="panel-section-title">Project Comments</h2>
        <div class="panel-stack" style="gap: 12px;">
          <label class="panel-muted">Project</label>
          <select class="panel-select" data-project-comments-select>
            @foreach($projects as $project)
              <option value="{{ $project->id }}">{{ $project->title }}</option>
            @endforeach
          </select>
        </div>

        <div class="project-comments-panels">
          @forelse($projects as $project)
            @php
              $assignmentIds = $project->assignments->pluck('user_id')->map(static fn ($id): int => (int) $id)->all();
              $canCommentProject = $canManagePipeline || (in_array(strtolower(trim((string) auth()->user()?->role)), ['photographer', 'editor'], true) && in_array((int) auth()->id(), $assignmentIds, true));
            @endphp
            <div class="project-comments-panel-body" data-comment-scope data-project-comments="{{ $project->id }}" hidden>
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
                      $canDeleteComment = $canManagePipeline || (int) $comment->user_id === (int) auth()->id();
                    @endphp
                    <form method="post" action="{{ route('admin.projects.comments.update', ['project' => $project, 'comment' => $comment]) }}" class="project-comment-edit-form" data-edit-form hidden>
                      @csrf
                      <textarea class="panel-textarea" name="body" required>{{ old('body', $comment->body) }}</textarea>
                      <div class="project-comment-edit-actions">
                        <button class="panel-btn panel-btn-primary" type="submit">Save</button>
                        <button class="panel-btn" type="button" data-edit-cancel>Cancel</button>
                      </div>
                    </form>
                    <div class="project-comment-actions">
                      @if($canCommentProject)
                        <button class="project-comment-action" type="button" data-reply-button data-reply-id="{{ $comment->id }}" data-reply-author="{{ $comment->user?->name ?: ucfirst($comment->sender_role) }}" data-reply-body="{{ \Illuminate\Support\Str::limit($comment->body, 160) }}">
                          <span aria-hidden="true"><x-panel-icon name="reply" /></span>
                          Reply
                        </button>
                      @endif
                      @if((int) $comment->user_id === (int) auth()->id())
                        <button class="project-comment-action" type="button" data-edit-open>
                          <span aria-hidden="true"><x-panel-icon name="edit" /></span>
                          Edit
                        </button>
                      @endif
                      @if($canDeleteComment)
                        <form method="post" action="{{ route('admin.projects.comments.delete', ['project' => $project, 'comment' => $comment]) }}" data-app-confirm="1" data-confirm-message="Delete this comment?" style="margin:0;">
                          @csrf
                          <button class="project-comment-action is-danger" type="submit" title="Delete comment" aria-label="Delete comment">
                            <span aria-hidden="true"><x-panel-icon name="trash" /></span>
                            Delete
                          </button>
                        </form>
                      @endif
                    </div>
                  </article>
                @empty
                <div class="panel-muted">No comments yet.</div>
                @endforelse
              </div>
              @if($canCommentProject)
                <form method="post" action="{{ route('admin.projects.comments.store', $project) }}" class="project-discussion-form" data-comment-form>
                  @csrf
                  <input type="hidden" name="parent_comment_id" value="" data-reply-input>
                  <div class="project-comment-reply-banner" data-reply-banner hidden>
                    <div>
                      <strong data-reply-author></strong>
                      <div class="project-comment-reply-preview" data-reply-preview></div>
                    </div>
                    <button class="project-comment-reply-cancel" type="button" data-reply-cancel>Cancel</button>
                  </div>
                  <textarea class="panel-textarea" name="body" placeholder="Write a comment for the project team" required>{{ old('body') }}</textarea>
                  <button class="panel-btn panel-btn-primary" type="submit">Post Comment</button>
                </form>
              @else
                <p class="panel-muted">You do not have permission to comment on this project.</p>
              @endif
            </div>
          @empty
            <p class="panel-muted">No projects available.</p>
          @endforelse
        </div>
      </article>
    </div>
  </aside>
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
  :delete-enabled="$canDeleteMedia"
  delete-url-template="{{ '/admin/projects/__PROJECT__/media/__MEDIA__/delete' }}"
  csrf-token="{{ csrf_token() }}"
/>
<x-panel-delete-confirm-modal modal-id="media-delete-confirm-modal" trigger-selector="[data-delete-trigger]" title="Delete Media File" />

<script>
  (function () {
    var scopes = document.querySelectorAll('[data-comment-scope]');
    if (!scopes.length) return;

    scopes.forEach(function (scope) {
      scope.querySelectorAll('[data-edit-form]').forEach(function (editForm) {
        editForm.hidden = true;
        editForm.classList.remove('is-open');
      });

      var form = scope.querySelector('[data-comment-form]');
      if (!form) return;

      var replyInput = form.querySelector('[data-reply-input]');
      var replyBanner = form.querySelector('[data-reply-banner]');
      var replyAuthor = form.querySelector('[data-reply-author]');
      var replyPreview = form.querySelector('[data-reply-preview]');
      var replyCancel = form.querySelector('[data-reply-cancel]');
      var commentTextarea = form.querySelector('textarea[name="body"]');

      scope.querySelectorAll('[data-reply-button]').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var id = btn.getAttribute('data-reply-id');
          var author = btn.getAttribute('data-reply-author');
          var body = btn.getAttribute('data-reply-body');
          if (replyInput) replyInput.value = id || '';
          if (replyAuthor) replyAuthor.textContent = author || '';
          if (replyPreview) replyPreview.textContent = body || '';
          if (replyBanner) replyBanner.hidden = false;
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

      scope.querySelectorAll('[data-edit-open]').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var card = btn.closest('[data-comment-card]');
          if (!card) return;
          var body = card.querySelector('[data-comment-body]');
          var editForm = card.querySelector('[data-edit-form]');
          if (body) body.hidden = true;
          if (editForm) {
            editForm.hidden = false;
            editForm.classList.add('is-open');
            var textarea = editForm.querySelector('textarea');
            if (textarea) textarea.focus();
          }
        });
      });

      scope.querySelectorAll('[data-edit-cancel]').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var formEl = btn.closest('[data-edit-form]');
          if (!formEl) return;
          var card = btn.closest('[data-comment-card]');
          formEl.hidden = true;
          formEl.classList.remove('is-open');
          var body = card ? card.querySelector('[data-comment-body]') : null;
          if (body) body.hidden = false;
        });
      });
    });

    var select = document.querySelector('[data-project-comments-select]');
    var panels = document.querySelectorAll('[data-project-comments]');
    if (select && panels.length) {
      function showPanel(projectId) {
        panels.forEach(function (panel) {
          panel.hidden = panel.getAttribute('data-project-comments') !== projectId;
        });
      }

      select.addEventListener('change', function () {
        showPanel(select.value);
      });

      if (select.value) {
        showPanel(select.value);
      } else if (select.options.length) {
        select.value = select.options[0].value;
        showPanel(select.value);
      }
    }
  })();
</script>

@if($canUploadMedia ?? false)
<div class="dropbox-modal-overlay" id="dropbox-import-modal" role="dialog" aria-modal="true" aria-labelledby="dropbox-modal-title">
  <div class="dropbox-modal-container" style="max-width: 550px;">
    <header class="dropbox-modal-header">
      <h3 id="dropbox-modal-title" style="margin:0; font-size:1.15rem; font-weight:700; color:#1e293b; display:inline-flex; align-items:center; gap:8px;">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="#0061ff"><path d="M5.008 3.513l6.99 4.417-6.99 4.416L5.008 3.513zm13.984 0l-6.99 4.417 6.99 4.416.002-8.833zm-13.984 17.06l6.99-4.417-6.99-4.416-.002 8.833zm13.984 0l-6.99-4.417 6.99-4.416.002 8.833zM11.998 12.35l6.99-4.417-6.99-4.42-6.99 4.42 6.99 4.417z"/></svg>
        Import Gallery from Dropbox
      </h3>
      <button class="dropbox-modal-close" type="button" data-dropbox-modal-close aria-label="Close modal">&times;</button>
    </header>

    <!-- FORM STATE -->
    <div class="dropbox-modal-body" id="dropbox-form-state">
      <div class="panel-stack" style="gap:14px; margin-bottom:0;">
        <div class="panel-stack" style="gap:4px; margin-bottom:0;">
          <label class="panel-muted" style="font-weight:600; color:#475569;">Dropbox Shared Folder Link</label>
          <input class="panel-input" type="text" id="dropbox-folder-link" placeholder="https://www.dropbox.com/scl/fo/..." style="width:100%;">
          <div id="dropbox-link-error" class="panel-muted" style="color:#ef4444; font-size:0.85rem; display:none; margin-top:2px;"></div>
        </div>

        <div class="panel-stack" style="gap:4px; margin-bottom:0;">
          <label class="panel-muted" style="font-weight:600; color:#475569;">Gallery / Project</label>
          <select class="panel-input" id="dropbox-project-select" style="width:100%; height:42px; padding:0 12px;">
            <option value="">Select Project / Gallery...</option>
            @foreach($allProjects ?? [] as $proj)
              <option value="{{ $proj->id }}">{{ $proj->title }} (#{{ $proj->id }})</option>
            @endforeach
          </select>
          <div id="dropbox-project-error" class="panel-muted" style="color:#ef4444; font-size:0.85rem; display:none; margin-top:2px;"></div>
        </div>

        <div class="panel-stack" style="gap:4px; margin-bottom:0;">
          <label class="panel-muted" style="font-weight:600; color:#475569;">Media Upload Stage</label>
          <select class="panel-input" id="dropbox-stage-select" style="width:100%; height:42px; padding:0 12px;">
            <option value="raw">Raw Footage</option>
            <option value="edited">Edited Photos</option>
            <option value="video">Videos</option>
            <option value="document">Documents</option>
          </select>
        </div>

        <!-- Scan Preview Box -->
        <div id="dropbox-preview-box" style="display:none; background:#f8fafc; border:1px solid #cbd5e1; border-radius:8px; padding:16px; margin-top:4px;">
          <h4 style="margin:0 0 10px 0; font-size:0.95rem; font-weight:700; color:#334155; border-bottom:1px solid #e2e8f0; padding-bottom:6px;">Dropbox Scan Results</h4>
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; font-size:0.85rem; color:#475569;">
            <div>Images: <span id="preview-images-count" style="font-weight:700; color:#1e293b;">0</span></div>
            <div>Videos: <span id="preview-videos-count" style="font-weight:700; color:#1e293b;">0</span></div>
            <div>Documents/PDFs: <span id="preview-docs-count" style="font-weight:700; color:#1e293b;">0</span></div>
            <div>Duplicates (to skip): <span id="preview-duplicates-count" style="font-weight:700; color:#ef4444;">0</span></div>
            <div style="grid-column: span 2; border-top:1px solid #e2e8f0; padding-top:8px; margin-top:4px;">
              Total Transfer Size: <span id="preview-total-size" style="font-weight:700; color:#1e293b;">0 MB</span>
            </div>
            <div style="grid-column: span 2;">
              Estimated Duration: <span id="preview-est-time" style="font-weight:700; color:#1e293b;">0 seconds</span>
            </div>
          </div>
        </div>

        <div class="panel-form-row" style="margin-bottom:0; justify-content:flex-end; gap:8px; margin-top:12px;">
          <button class="panel-btn" type="button" data-dropbox-modal-close style="border:1px solid #cbd5e1; background:#fff; color:#475569;">Cancel</button>
          <button class="panel-btn panel-btn-primary" type="button" id="dropbox-scan-btn" style="background:#475569; border-color:#475569; color:#fff;">Scan Folder Preview</button>
          <button class="panel-btn panel-btn-primary" type="button" id="dropbox-submit-btn" style="display:none; background:#0061ff; border-color:#0061ff; color:#fff;">Start Background Import</button>
        </div>
      </div>
    </div>

    <!-- PROGRESS STATE -->
    <div class="dropbox-modal-body" id="dropbox-progress-state" style="display:none;">
      <div class="panel-stack" style="gap:16px; margin-bottom:0;">
        <div id="dropbox-status-title" style="font-weight:700; color:#1e293b; font-size:1.05rem;">Queue Job Initializing...</div>
        
        <div style="background:#f1f5f9; height:10px; border-radius:999px; overflow:hidden; width:100%;">
          <div id="dropbox-progress-bar" style="background:#0061ff; height:100%; width:0%; transition:width 0.2s ease;"></div>
        </div>

        <!-- Metrics Dashboard row -->
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; font-size:0.85rem; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:12px; color:#475569;">
          <div>Remaining Files: <span id="dropbox-remaining-count" style="font-weight:700; color:#1e293b;">--</span></div>
          <div>Speed: <span id="dropbox-speed-text" style="font-weight:700; color:#1e293b;">-- MB/s</span></div>
          <div style="grid-column: span 2;">ETA (Estimated Time): <span id="dropbox-eta-text" style="font-weight:700; color:#1e293b;">--</span></div>
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center; font-size:0.85rem; color:#475569;">
          <span id="dropbox-progress-text">0% (0 / 0 files)</span>
          <span id="dropbox-progress-counter" style="font-weight:600;">0 Imported</span>
        </div>

        <!-- Scrollable Log Box -->
        <div id="dropbox-log-box" style="background:#0f172a; color:#f8fafc; font-family:monospace; font-size:0.82rem; padding:12px; border-radius:8px; height:140px; overflow-y:auto; display:grid; gap:4px; align-content:start;">
          <div style="color:#94a3b8;">&gt;_ Initializing import session...</div>
        </div>

        <p class="text-xs text-gray-400 text-center" style="margin:0;">
          This import job runs on the server queue. You can safely close this modal or browser tab. Monitor history in the <strong>Import History</strong> dashboard.
        </p>

        <div class="panel-form-row" style="margin-bottom:0; justify-content:flex-end; gap:8px; margin-top:4px;">
          <button class="panel-btn" type="button" id="dropbox-cancel-import-btn" style="border:1px solid #fca5a5; background:#fee2e2; color:#b91c1c; font-weight:600;">Cancel Import</button>
          <button class="panel-btn" type="button" id="dropbox-retry-failed-btn" style="display:none; border:1px solid #c7d2fe; background:#e0e7ff; color:#4338ca; font-weight:600;">Retry Failed</button>
          <a class="panel-btn" href="#" id="dropbox-download-duplicates-link" style="display:none; border:1px solid #cbd5e1; background:#f8fafc; color:#475569; font-weight:600; text-decoration:none; display:inline-flex; align-items:center; justify-content:center;" target="_blank">Download Duplicate Report</a>
          <button class="panel-btn" type="button" id="dropbox-close-progress-btn" style="border:1px solid #cbd5e1; background:#fff; color:#475569;">Close Window</button>
        </div>
      </div>
    </div>
  </div>
</div>
@endif

<style>
  .dropbox-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.65);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2000;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.25s ease;
  }

  .dropbox-modal-overlay.is-active {
    opacity: 1;
    pointer-events: auto;
  }

  .dropbox-modal-container {
    background: #ffffff;
    border-radius: 16px;
    width: 100%;
    max-width: 520px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    border: 1px solid #e2e8f0;
    overflow: hidden;
    transform: scale(0.95);
    transition: transform 0.25s ease;
    display: flex;
    flex-direction: column;
  }

  .dropbox-modal-overlay.is-active .dropbox-modal-container {
    transform: scale(1);
  }

  .dropbox-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 1px solid #f1f5f9;
  }

  .dropbox-modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    line-height: 1;
    color: #94a3b8;
    cursor: pointer;
    padding: 4px;
    transition: color 0.15s ease;
  }

  .dropbox-modal-close:hover {
    color: #475569;
  }

  .dropbox-modal-body {
    padding: 20px;
  }

  /* Custom log scrollbar */
  #dropbox-log-box::-webkit-scrollbar {
    width: 6px;
  }
  #dropbox-log-box::-webkit-scrollbar-track {
    background: transparent;
  }
  #dropbox-log-box::-webkit-scrollbar-thumb {
    background: #475569;
    border-radius: 3px;
  }
</style>

<script>
  (function () {
    const modalOverlay = document.getElementById('dropbox-import-modal');
    const closeTriggers = document.querySelectorAll('[data-dropbox-modal-close]');
    const scanBtn = document.getElementById('dropbox-scan-btn');
    const submitBtn = document.getElementById('dropbox-submit-btn');
    const closeProgressBtn = document.getElementById('dropbox-close-progress-btn');
    const cancelImportBtn = document.getElementById('dropbox-cancel-import-btn');
    const retryFailedBtn = document.getElementById('dropbox-retry-failed-btn');
    const downloadDuplicatesLink = document.getElementById('dropbox-download-duplicates-link');

    const folderLinkInput = document.getElementById('dropbox-folder-link');
    const projectSelect = document.getElementById('dropbox-project-select');
    const stageSelect = document.getElementById('dropbox-stage-select');

    const linkError = document.getElementById('dropbox-link-error');
    const projectError = document.getElementById('dropbox-project-error');

    const formState = document.getElementById('dropbox-form-state');
    const progressState = document.getElementById('dropbox-progress-state');
    const previewBox = document.getElementById('dropbox-preview-box');

    const statusTitle = document.getElementById('dropbox-status-title');
    const progressBar = document.getElementById('dropbox-progress-bar');
    const progressText = document.getElementById('dropbox-progress-text');
    const progressCounter = document.getElementById('dropbox-progress-counter');
    const logBox = document.getElementById('dropbox-log-box');

    const remainingCountText = document.getElementById('dropbox-remaining-count');
    const speedText = document.getElementById('dropbox-speed-text');
    const etaText = document.getElementById('dropbox-eta-text');

    let importInProgress = false;
    let currentSessionUuid = null;
    let pollInterval = null;

    if (!modalOverlay) return;

    // Listen for data-dropbox-modal-trigger
    document.querySelectorAll('[data-dropbox-modal-trigger]').forEach(trigger => {
      trigger.addEventListener('click', () => {
        modalOverlay.classList.add('is-active');
        resetForm();

        const preselectProj = trigger.getAttribute('data-preselect-project');
        const preselectStage = trigger.getAttribute('data-preselect-stage');

        if (preselectProj) {
          projectSelect.value = preselectProj;
        }
        if (preselectStage) {
          stageSelect.value = preselectStage;
        }
      });
    });

    closeTriggers.forEach(btn => {
      btn.addEventListener('click', () => {
        if (importInProgress) {
          if (!confirm('An import job is running in the background. Closing the modal will not stop background processing. Are you sure?')) {
            return;
          }
        }
        closeModal();
      });
    });

    closeProgressBtn.addEventListener('click', () => {
      closeModal();
    });

    function closeModal() {
      modalOverlay.classList.remove('is-active');
      if (pollInterval) {
        clearInterval(pollInterval);
        pollInterval = null;
      }
      if (importInProgress) {
        window.location.reload();
      }
    }

    // Step 1: Scan Preview
    scanBtn.addEventListener('click', async () => {
      if (!validateInputs()) return;

      scanBtn.disabled = true;
      scanBtn.textContent = 'Scanning...';
      previewBox.style.display = 'none';
      submitBtn.style.display = 'none';

      const url = folderLinkInput.value.trim();
      const projectId = projectSelect.value;

      try {
        const response = await fetch(`/admin/projects/${projectId}/dropbox/scan-preview`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
          },
          body: JSON.stringify({ dropbox_url: url })
        });

        const data = await response.json();
        if (!response.ok || data.error) {
          throw new Error(data.error || 'Failed to scan Dropbox folder.');
        }

        document.getElementById('preview-images-count').textContent = data.counts.images;
        document.getElementById('preview-videos-count').textContent = data.counts.videos;
        document.getElementById('preview-docs-count').textContent = data.counts.documents;
        document.getElementById('preview-duplicates-count').textContent = data.counts.duplicates;
        document.getElementById('preview-total-size').textContent = (data.total_size / (1024 * 1024)).toFixed(2) + ' MB';
        document.getElementById('preview-est-time').textContent = formatRemainingTime(data.estimated_time_seconds);

        previewBox.style.display = 'block';
        scanBtn.style.display = 'none';
        submitBtn.style.display = 'block';

      } catch (err) {
        alert(err.message);
      } finally {
        scanBtn.disabled = false;
        scanBtn.textContent = 'Scan Folder Preview';
      }
    });

    // Step 2: Start Background Import
    submitBtn.addEventListener('click', async () => {
      if (!validateInputs()) return;

      submitBtn.disabled = true;
      submitBtn.textContent = 'Queuing...';

      const url = folderLinkInput.value.trim();
      const projectId = projectSelect.value;
      const stage = stageSelect.value;

      try {
        const response = await fetch(`/admin/projects/${projectId}/dropbox/start-queue-import`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
          },
          body: JSON.stringify({
            dropbox_url: url,
            media_stage: stage
          })
        });

        const data = await response.json();
        if (!response.ok || data.error) {
          throw new Error(data.error || 'Failed to start queue import.');
        }

        currentSessionUuid = data.session_uuid;
        importInProgress = true;

        // Switch to progress view
        formState.style.display = 'none';
        progressState.style.display = 'block';
        cancelImportBtn.style.display = 'block';
        retryFailedBtn.style.display = 'none';
        downloadDuplicatesLink.style.display = 'none';

        logBox.innerHTML = '<div style="color:#38bdf8;">&gt;_ Background import job initialized! Waiting for queue worker...</div>';
        scrollLogBox();

        // Start polling
        pollInterval = setInterval(() => pollProgress(projectId, currentSessionUuid), 2000);

      } catch (err) {
        alert(err.message);
        submitBtn.disabled = false;
        submitBtn.textContent = 'Start Background Import';
      }
    });

    // Cancel Active Session
    cancelImportBtn.addEventListener('click', async () => {
      if (!currentSessionUuid) return;
      if (!confirm('Are you sure you want to cancel the running import session?')) return;

      cancelImportBtn.disabled = true;
      cancelImportBtn.textContent = 'Cancelling...';

      const projectId = projectSelect.value;

      try {
        const response = await fetch(`/admin/projects/${projectId}/dropbox/cancel-import/${currentSessionUuid}`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
          }
        });

        if (response.ok) {
          logBox.innerHTML += '<div style="color:#f59e0b;">&gt;_ Cancel request submitted! Processing...</div>';
          scrollLogBox();
        }
      } catch (err) {
        console.error('Cancel failed', err);
      } finally {
        cancelImportBtn.disabled = false;
        cancelImportBtn.textContent = 'Cancel Import';
      }
    });

    // Retry Failed Files
    retryFailedBtn.addEventListener('click', async () => {
      if (!currentSessionUuid) return;

      retryFailedBtn.disabled = true;
      retryFailedBtn.textContent = 'Retrying...';

      const projectId = projectSelect.value;

      try {
        const response = await fetch(`/admin/projects/${projectId}/dropbox/retry-failed/${currentSessionUuid}`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
          }
        });

        if (response.ok) {
          logBox.innerHTML += '<div style="color:#38bdf8;">&gt;_ Retry dispatched to queue! Restarting monitor...</div>';
          scrollLogBox();
          
          progressBar.style.backgroundColor = '#0061ff';
          statusTitle.textContent = 'Retrying Failed Files...';
          retryFailedBtn.style.display = 'none';
          cancelImportBtn.style.display = 'block';

          if (pollInterval) clearInterval(pollInterval);
          pollInterval = setInterval(() => pollProgress(projectId, currentSessionUuid), 2000);
        }
      } catch (err) {
        alert('Retry request failed.');
      } finally {
        retryFailedBtn.disabled = false;
        retryFailedBtn.textContent = 'Retry Failed';
      }
    });

    // Poll Progress API
    async function pollProgress(projectId, uuid) {
      try {
        const response = await fetch(`/admin/projects/${projectId}/dropbox/import-progress/${uuid}`);
        const data = await response.json();

        if (!response.ok) return;

        const total = data.total_files || 0;
        const processed = data.processed_files || 0;
        const imported = data.imported_files || 0;
        const duplicates = data.duplicate_files || 0;
        const failed = data.failed_files || 0;

        const percent = total > 0 ? Math.round((processed / total) * 100) : 0;

        progressBar.style.width = `${percent}%`;
        progressText.textContent = `${percent}% (${processed} / ${total} files)`;
        progressCounter.textContent = `${imported} Imported`;

        remainingCountText.textContent = total - processed;
        speedText.textContent = data.speed_bytes_per_sec > 0 ? (data.speed_bytes_per_sec / (1024 * 1024)).toFixed(2) + ' MB/s' : '-- MB/s';
        etaText.textContent = formatRemainingTime(data.estimated_remaining_seconds);

        if (data.current_file) {
          statusTitle.textContent = `Processing: ${data.current_file}`;
          if (!logBox.innerHTML.includes(data.current_file)) {
            logBox.innerHTML += `<div>&gt;_ Downloading ${data.current_file}...</div>`;
            scrollLogBox();
          }
        }

        // Live newly completed media prepends (Feature 8)
        if (data.newly_imported && data.newly_imported.length > 0) {
          data.newly_imported.forEach(item => {
            const containerId = item.stage === 'raw' ? 'raw-media-list' : 'edited-media-list';
            const container = document.getElementById(containerId);
            if (!container) return;

            const existingRow = container.querySelector(`[href="${item.view_url}"]`);
            if (existingRow) return;

            const emptyPlaceholder = container.querySelector('.panel-muted');
            if (emptyPlaceholder) {
              emptyPlaceholder.remove();
            }

            const article = document.createElement('article');
            article.className = 'media-file-row';
            article.innerHTML = `
              <div class="media-file-meta">
                <span class="media-file-kind">${item.type}</span>
                <span class="media-file-name">${item.original_name}</span>
                <span class="panel-muted">Uploaded by ${item.uploader_name} &bull; ${item.uploader_role}</span>
              </div>
              <div class="media-file-actions">
                <a class="panel-link" href="${item.view_url}" target="_blank" rel="noopener">View</a>
              </div>
            `;
            container.insertBefore(article, container.firstChild);
          });
        }

        // Handle states
        if (data.status === 'completed') {
          stopPolling();
          progressBar.style.backgroundColor = '#4ade80';
          statusTitle.textContent = 'Import Completed!';
          logBox.innerHTML += `<div style="color:#4ade80; font-weight:bold; margin-top:8px;">&gt;_ Workflow Completed! Imported: ${imported}, Duplicates: ${duplicates}, Failed: ${failed}</div>`;
          scrollLogBox();
          
          cancelImportBtn.style.display = 'none';
          if (duplicates > 0) {
            downloadDuplicatesLink.href = `/admin/projects/${projectId}/dropbox/export-duplicates/${uuid}`;
            downloadDuplicatesLink.style.display = 'inline-flex';
          }
        } else if (data.status === 'failed') {
          stopPolling();
          progressBar.style.backgroundColor = '#f87171';
          statusTitle.textContent = 'Import Failed';
          logBox.innerHTML += `<div style="color:#ef4444; font-weight:bold; margin-top:8px;">&gt;_ Queue worker encountered error. Check database error logs.</div>`;
          scrollLogBox();

          cancelImportBtn.style.display = 'none';
          retryFailedBtn.style.display = 'block';
        } else if (data.status === 'cancelled') {
          stopPolling();
          progressBar.style.backgroundColor = '#fbbf24';
          statusTitle.textContent = 'Import Cancelled';
          logBox.innerHTML += `<div style="color:#fbbf24; font-weight:bold; margin-top:8px;">&gt;_ Import execution terminated by user request.</div>`;
          scrollLogBox();

          cancelImportBtn.style.display = 'none';
        }

      } catch (err) {
        console.error('Polling error', err);
      }
    }

    function stopPolling() {
      if (pollInterval) {
        clearInterval(pollInterval);
        pollInterval = null;
      }
    }

    function validateInputs() {
      let hasError = false;
      const url = folderLinkInput.value.trim();
      const projectId = projectSelect.value;

      if (!url) {
        linkError.textContent = 'Please enter a Dropbox shared folder link.';
        linkError.style.display = 'block';
        hasError = true;
      } else if (!url.match(/^https?:\/\/(www\.)?dropbox\.com\/(sh|scl\/fo|scl\/fi|s)\/.+/)) {
        linkError.textContent = 'Please enter a valid Dropbox shared link.';
        linkError.style.display = 'block';
        hasError = true;
      } else {
        linkError.style.display = 'none';
      }

      if (!projectId) {
        projectError.textContent = 'Please select a project / gallery.';
        projectError.style.display = 'block';
        hasError = true;
      } else {
        projectError.style.display = 'none';
      }

      return !hasError;
    }

    function resetForm() {
      folderLinkInput.value = '';
      projectSelect.value = '';
      stageSelect.value = 'raw';
      linkError.style.display = 'none';
      projectError.style.display = 'none';
      
      previewBox.style.display = 'none';
      scanBtn.style.display = 'block';
      submitBtn.style.display = 'none';
      submitBtn.disabled = false;
      submitBtn.textContent = 'Start Background Import';

      formState.style.display = 'block';
      progressState.style.display = 'none';
      progressBar.style.width = '0%';
      progressBar.style.backgroundColor = '#0061ff';
      progressText.textContent = '0% (0 / 0 files)';
      progressCounter.textContent = '0 Imported';
      logBox.innerHTML = '<div style="color:#94a3b8;">&gt;_ Initializing import session...</div>';
      
      remainingCountText.textContent = '--';
      speedText.textContent = '-- MB/s';
      etaText.textContent = '--';

      importInProgress = false;
      currentSessionUuid = null;
      stopPolling();
    }

    function scrollLogBox() {
      logBox.scrollTop = logBox.scrollHeight;
    }

    function formatRemainingTime(seconds) {
      if (!seconds || seconds <= 0) return '--';
      if (seconds < 60) return `${seconds} seconds`;
      const mins = Math.floor(seconds / 60);
      const secs = seconds % 60;
      return `${mins}m ${secs}s`;
    }
  })();
</script>

@endsection





