@extends('layouts.panel', [
  'title' => 'Watermark Settings',
  'heading' => 'Watermark Settings',
  'subheading' => 'Upload watermark logo and control position/size for unpaid gallery previews.',
])

@section('content')
<section class="panel-card">
  <div class="panel-form-row" style="justify-content: flex-start; gap: 8px; margin-bottom: 10px;">
    <span class="panel-badge">Unpaid images: {{ number_format((int) $unpaidImageTotal) }}</span>
    <span class="panel-badge">Up-to-date: {{ number_format((int) $upToDateWatermarks) }}</span>
    @if((int) $pendingRebuild > 0)
    <span class="panel-badge panel-badge-danger">Pending rebuild: {{ number_format((int) $pendingRebuild) }}</span>
    @else
    <span class="panel-badge">All unpaid watermarks up-to-date</span>
    @endif
  </div>

  <div class="panel-form-row" style="justify-content: space-between; align-items: center; gap: 10px;">
    <p class="panel-muted" style="margin: 0;">These settings apply to unpaid client gallery images and media preview view.</p>
    <a class="panel-link" href="{{ route('admin.media-delivery.index') }}">Back to Media Delivery</a>
  </div>

  <form method="post" action="{{ route('admin.media-delivery.watermark.update') }}" enctype="multipart/form-data" class="panel-stack" style="margin-top: 14px;">
    @csrf

    <div class="panel-grid" style="grid-template-columns: repeat(2, minmax(280px, 1fr)); gap: 14px; align-items: start;">
      <section class="panel-card" style="margin: 0;">
        <h3 class="panel-section-title">Watermark Logo (PNG)</h3>
        <div class="panel-stack" style="max-width: 100%;">
          <label class="panel-muted" for="watermark_logo">Upload/Re-upload logo</label>
          <input id="watermark_logo" class="panel-input" type="file" name="watermark_logo" accept="image/png" style="width: 100%; max-width: 100%; box-sizing: border-box;">
        </div>
        <p class="panel-muted" style="margin-top: 8px;">Use transparent PNG for best results. Max 10 MB.</p>
      </section>

      <section class="panel-card" style="margin: 0;">
        <h3 class="panel-section-title">Logo Transparency</h3>
        <div class="panel-stack" style="gap: 8px;">
          <input
            id="opacity_percent_range"
            class="panel-input"
            type="range"
            min="1"
            max="100"
            value="{{ (int) ($settings->opacity_percent ?? 62) }}"
            oninput="document.getElementById('opacity_percent_input').value = this.value"
          >
          <div class="panel-form-row" style="margin: 0; align-items: center; gap: 8px;">
            <input
              id="opacity_percent_input"
              class="panel-input"
              type="number"
              name="opacity_percent"
              min="1"
              max="100"
              value="{{ (int) ($settings->opacity_percent ?? 62) }}"
              oninput="document.getElementById('opacity_percent_range').value = Math.min(100, Math.max(1, this.value || 1))"
              style="max-width: 90px;"
            >
            <span class="panel-muted">%</span>
          </div>
          <p class="panel-muted" style="margin: 0;">1 = very transparent, 100 = solid logo visibility.</p>
        </div>
      </section>

      <section class="panel-card" style="margin: 0;">
        <h3 class="panel-section-title">Position</h3>
        <select class="panel-input" name="position" required>
          <option value="top_left" @selected(($settings->position ?? 'center') === 'top_left')>Top Left</option>
          <option value="top_right" @selected(($settings->position ?? 'center') === 'top_right')>Top Right</option>
          <option value="bottom_left" @selected(($settings->position ?? 'center') === 'bottom_left')>Bottom Left</option>
          <option value="bottom_right" @selected(($settings->position ?? 'center') === 'bottom_right')>Bottom Right</option>
          <option value="center" @selected(($settings->position ?? 'center') === 'center')>Center</option>
        </select>
      </section>

      <section class="panel-card" style="margin: 0;">
        <h3 class="panel-section-title">Size</h3>
        <select class="panel-input" name="size" required>
          <option value="small" @selected(($settings->size ?? 'medium') === 'small')>Small</option>
          <option value="medium" @selected(($settings->size ?? 'medium') === 'medium')>Medium</option>
          <option value="large" @selected(($settings->size ?? 'medium') === 'large')>Large</option>
        </select>
      </section>
    </div>

    <div class="panel-form-row" style="justify-content: space-between; align-items: center; margin-top: 6px;">
      <button class="panel-btn panel-btn-primary" type="submit">Save Watermark Settings</button>
      @if($logoExists)
      <span class="panel-badge">Logo configured</span>
      @else
      <span class="panel-badge panel-badge-danger">No PNG logo uploaded</span>
      @endif
    </div>
  </form>

  <section class="panel-card" style="margin: 14px 0 0;">
    <h3 class="panel-section-title">Batch Rebuild</h3>
    <p class="panel-muted" style="margin-bottom: 10px;">Rebuild watermark previews now for all unpaid project images using current logo, position, and size settings.</p>
    <form method="post" action="{{ route('admin.media-delivery.watermark.rebuild') }}" data-action-confirm-form data-confirm-title="Rebuild Watermarks" data-confirm-message="Rebuild watermark previews for all unpaid project images now?" data-confirm-button="Rebuild Now">
      @csrf
      <button class="panel-btn" type="button" data-action-confirm-trigger>Rebuild All Unpaid Watermarks</button>
    </form>
  </section>

  <section class="panel-card" style="margin: 14px 0 0;">
    <h3 class="panel-section-title">Media Folder Maintenance</h3>
    <p class="panel-muted" style="margin-bottom: 10px;">Run one-time migration to move legacy media paths into the new project-name folder structure.</p>
    <form method="post" action="{{ route('admin.media-delivery.folders.migrate') }}" data-action-confirm-form data-confirm-title="Run Media Folder Migration" data-confirm-message="Run media folder migration now? This will move existing files into the new project folder layout." data-confirm-button="Run Migration">
      @csrf
      <button class="panel-btn" type="button" data-action-confirm-trigger>Run Media Folder Migration</button>
    </form>
  </section>

  <section class="panel-card" style="margin: 14px 0 0;">
    <h3 class="panel-section-title">Current Logo Preview</h3>
    @if($logoExists)
    <div style="padding: 14px; border: 1px dashed rgba(0,0,0,0.14); border-radius: 12px; background: #f8f9fb; display: inline-block;">
      <img src="{{ route('admin.media-delivery.watermark.logo') }}?v={{ optional($settings->updated_at)->timestamp }}" alt="Watermark logo" style="max-width: 280px; width: 100%; height: auto; display: block;">
    </div>
    @else
    <p class="panel-muted">Upload a PNG logo to preview and apply brand watermark on unpaid previews.</p>
    @endif
  </section>
</section>

<div id="panel-action-confirm-modal" class="panel-modal" hidden>
  <div class="panel-modal-backdrop" data-action-confirm-close></div>
  <div class="panel-modal-dialog" style="max-width: 560px;">
    <div class="panel-modal-head">
      <h3 class="panel-modal-title" id="panel-action-confirm-title">Confirm Action</h3>
      <button class="panel-modal-close" type="button" data-action-confirm-close aria-label="Close confirmation">×</button>
    </div>

    <div class="panel-modal-body">
      <p class="panel-muted" id="panel-action-confirm-message" style="margin:0;">Are you sure you want to continue?</p>
    </div>

    <div class="panel-modal-foot" style="gap:10px;">
      <button class="panel-btn" type="button" data-action-confirm-close>Cancel</button>
      <button class="panel-btn panel-btn-primary" type="button" id="panel-action-confirm-submit">Confirm</button>
    </div>
  </div>
</div>

<script>
  (function () {
    const modal = document.getElementById('panel-action-confirm-modal');
    const titleEl = document.getElementById('panel-action-confirm-title');
    const messageEl = document.getElementById('panel-action-confirm-message');
    const submitBtn = document.getElementById('panel-action-confirm-submit');

    if (!modal || !titleEl || !messageEl || !submitBtn) {
      return;
    }

    let activeForm = null;

    const closeModal = function () {
      modal.hidden = true;
      document.body.classList.remove('panel-modal-open');
      activeForm = null;
    };

    document.querySelectorAll('[data-action-confirm-trigger]').forEach(function (button) {
      button.addEventListener('click', function (event) {
        event.preventDefault();
        const form = button.closest('form[data-action-confirm-form]');
        if (!form) {
          return;
        }

        activeForm = form;
        titleEl.textContent = form.getAttribute('data-confirm-title') || 'Confirm Action';
        messageEl.textContent = form.getAttribute('data-confirm-message') || 'Are you sure you want to continue?';
        submitBtn.textContent = form.getAttribute('data-confirm-button') || 'Confirm';

        modal.hidden = false;
        document.body.classList.add('panel-modal-open');
      });
    });

    modal.querySelectorAll('[data-action-confirm-close]').forEach(function (button) {
      button.addEventListener('click', closeModal);
    });

    submitBtn.addEventListener('click', function () {
      if (!activeForm) {
        return;
      }

      if (typeof activeForm.requestSubmit === 'function') {
        activeForm.requestSubmit();
      } else {
        activeForm.submit();
      }
    });

    document.addEventListener('keydown', function (event) {
      if (modal.hidden) {
        return;
      }

      if (event.key === 'Escape') {
        closeModal();
      }
    });
  })();
</script>
@endsection
