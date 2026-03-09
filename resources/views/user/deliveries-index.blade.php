@extends('layouts.panel', [
  'title' => 'Media & Deliveries',
  'heading' => 'Media & Deliveries',
  'subheading' => 'Review gallery previews, final delivery files, and payment-gated downloads in one workspace.',
])

@section('content')
<div class="client-portal-shell client-media-workspace">
  <section class="panel-grid panel-grid-kpi-compact">
    <article class="client-portal-kpi">
      <span class="panel-kpi-label">Deliveries Ready</span>
      <p class="client-portal-kpi-value">{{ $portalStats['deliveries_ready'] }}</p>
    </article>
    <article class="client-portal-kpi">
      <span class="panel-kpi-label">Active Projects</span>
      <p class="client-portal-kpi-value">{{ $portalStats['active_projects'] }}</p>
    </article>
    <article class="client-portal-kpi">
      <span class="panel-kpi-label">Unpaid Invoices</span>
      <p class="client-portal-kpi-value">{{ $portalStats['unpaid_invoices'] }}</p>
    </article>
  </section>

  <section class="panel-card client-portal-stack">
    <h2 class="panel-section-title">Delivery Workspace</h2>

    @forelse($projects as $project)
      @php
        $galleryItems = $project->media->whereIn('type', ['image', 'video'])->values();
        $zipItems = $project->media->where('type', 'final_zip')->values();
        $isPaid = $project->invoices->contains(fn($invoice) => $invoice->status === 'paid');
        $projectGalleryPayload = $galleryPayloadByProject[$project->id] ?? [];
      @endphp

      <article class="panel-card media-project-card" data-project-media-card data-project-id="{{ $project->id }}" id="project-{{ $project->id }}">
        <div class="panel-form-row media-project-header">
          <div class="media-project-meta">
            <h3 class="media-project-title">{{ $project->title }}</h3>
            <p class="client-portal-meta">
              {{ $project->service_type ?: 'Service pending' }}
              @if(!blank($project->property_address))
                &bull; {{ $project->property_address }}
              @endif
              &bull; {{ $project->status }}
            </p>
            <p class="media-project-summary">Gallery: {{ $galleryItems->count() }} | Final ZIP: {{ $zipItems->count() }} | Payment: <strong>{{ $isPaid ? 'Paid' : 'Unpaid' }}</strong></p>
          </div>

          <div class="media-project-controls">
            <button class="panel-btn panel-btn-primary media-project-toggle" type="button" data-project-toggle aria-expanded="true" aria-label="Toggle delivery details">
              <svg class="media-project-toggle-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 10l4 4 4-4" fill="none" stroke="#ffffff" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
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
            <a class="panel-btn" href="{{ route('user.projects.show', $project) }}">Open Project</a>
            @if($zipItems->isNotEmpty() && $isPaid)
              <a class="panel-btn panel-btn-primary" href="{{ route('user.projects.media.download-zip', $project) }}">Download Final ZIP</a>
            @endif
          </div>
        </div>

        <div class="media-project-details" data-project-details>
          <div class="panel-grid media-delivery-files-grid">
            <section class="panel-card media-file-list-card">
              <h4 class="panel-section-title">Gallery Files</h4>
              <div class="media-file-list">
                @forelse($galleryItems as $index => $mediaItem)
                  @php
                    $mediaName = $mediaItem->original_name;
                  @endphp
                  <article class="media-file-row @if($index >= 2) is-hidden-by-default @endif" data-gallery-row>
                    <div class="media-file-meta">
                      <span class="media-file-kind">{{ strtoupper($mediaItem->type) }}</span>
                      <span class="media-file-name">{{ $mediaName }}</span>
                    </div>
                    <div class="media-file-actions">
                      <a class="panel-btn" href="{{ route('user.projects.media.preview', ['project' => $project, 'media' => $mediaItem]) }}" target="_blank" rel="noopener">Preview</a>
                      @if($isPaid)
                        <a class="panel-btn" href="{{ route('user.projects.media.download', ['project' => $project, 'media' => $mediaItem]) }}">Download</a>
                      @endif
                    </div>
                  </article>
                @empty
                  <p class="panel-muted">No gallery files are uploaded for this project yet.</p>
                @endforelse

                @if($galleryItems->count() > 2)
                  <div class="panel-form-row media-file-list-cta media-file-list-cta-group">
                    <button class="panel-btn" type="button" data-gallery-list-toggle aria-expanded="false">
                      Show All Media List ({{ $galleryItems->count() }})
                    </button>
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
                  <p class="panel-muted">No final ZIP uploaded yet.</p>
                @endforelse
              </div>
            </section>
          </div>
        </div>
      </article>
    @empty
      <div class="client-portal-empty">No project delivery records are available yet.</div>
    @endforelse

    <x-panel-pagination :paginator="$projects" />
  </section>
</div>

<x-panel-gallery-viewer
  modal-id="user-media-gallery-viewer"
  open-selector="[data-gallery-open]"
/>

<script>
  (function () {
    const cards = document.querySelectorAll('[data-project-media-card]');
    if (!cards.length) return;

    cards.forEach(function (card) {
      const details = card.querySelector('[data-project-details]');
      const toggle = card.querySelector('[data-project-toggle]');
      if (!details || !toggle) {
        return;
      }

      const applyState = function (collapsed) {
        card.classList.toggle('is-collapsed', collapsed);
        details.hidden = collapsed;
        toggle.setAttribute('aria-expanded', String(!collapsed));
      };

      applyState(false);

      toggle.addEventListener('click', function () {
        applyState(!card.classList.contains('is-collapsed'));
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
@endsection
