

<?php $__env->startSection('content'); ?>
<section class="panel-card">
  <div class="panel-form-row" style="justify-content: flex-start; gap: 8px; margin-bottom: 10px;">
    <span class="panel-badge">Unpaid images: <?php echo e(number_format((int) $unpaidImageTotal)); ?></span>
    <span class="panel-badge">Up-to-date: <?php echo e(number_format((int) $upToDateWatermarks)); ?></span>
    <?php if((int) $pendingRebuild > 0): ?>
    <span class="panel-badge panel-badge-danger">Pending rebuild: <?php echo e(number_format((int) $pendingRebuild)); ?></span>
    <?php else: ?>
    <span class="panel-badge">All unpaid watermarks up-to-date</span>
    <?php endif; ?>
  </div>

  <div class="panel-form-row" style="justify-content: space-between; align-items: center; gap: 10px;">
    <p class="panel-muted" style="margin: 0;">These settings apply to unpaid client gallery images and media preview view.</p>
    <a class="panel-link" href="<?php echo e(route('admin.media-delivery.index')); ?>">Back to Media Delivery</a>
  </div>

  <form method="post" action="<?php echo e(route('admin.media-delivery.watermark.update')); ?>" enctype="multipart/form-data" class="panel-stack" style="margin-top: 14px;">
    <?php echo csrf_field(); ?>

    <div class="panel-grid" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 14px; align-items: start;">
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
            value="<?php echo e((int) ($settings->opacity_percent ?? 62)); ?>"
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
              value="<?php echo e((int) ($settings->opacity_percent ?? 62)); ?>"
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
          <option value="top_left" <?php if(($settings->position ?? 'center') === 'top_left'): echo 'selected'; endif; ?>>Top Left</option>
          <option value="top_right" <?php if(($settings->position ?? 'center') === 'top_right'): echo 'selected'; endif; ?>>Top Right</option>
          <option value="bottom_left" <?php if(($settings->position ?? 'center') === 'bottom_left'): echo 'selected'; endif; ?>>Bottom Left</option>
          <option value="bottom_right" <?php if(($settings->position ?? 'center') === 'bottom_right'): echo 'selected'; endif; ?>>Bottom Right</option>
          <option value="center" <?php if(($settings->position ?? 'center') === 'center'): echo 'selected'; endif; ?>>Center</option>
        </select>
      </section>

      <section class="panel-card" style="margin: 0;">
        <h3 class="panel-section-title">Size</h3>
        <select class="panel-input" name="size" required>
          <option value="small" <?php if(($settings->size ?? 'medium') === 'small'): echo 'selected'; endif; ?>>Small</option>
          <option value="medium" <?php if(($settings->size ?? 'medium') === 'medium'): echo 'selected'; endif; ?>>Medium</option>
          <option value="large" <?php if(($settings->size ?? 'medium') === 'large'): echo 'selected'; endif; ?>>Large</option>
        </select>
      </section>
    </div>

    <div class="panel-form-row" style="justify-content: space-between; align-items: center; margin-top: 6px;">
      <button class="panel-btn panel-btn-primary" type="submit">Save Watermark Settings</button>
      <?php if($logoExists): ?>
      <span class="panel-badge">Logo configured</span>
      <?php else: ?>
      <span class="panel-badge panel-badge-danger">No PNG logo uploaded</span>
      <?php endif; ?>
    </div>
  </form>

  <section class="panel-card" style="margin: 14px 0 0;">
    <h3 class="panel-section-title">Batch Rebuild</h3>
    <p class="panel-muted" style="margin-bottom: 10px;">Rebuild watermark previews now for all unpaid project images using current logo, position, and size settings.</p>
    <form method="post" action="<?php echo e(route('admin.media-delivery.watermark.rebuild')); ?>" data-action-confirm-form data-confirm-title="Rebuild Watermarks" data-confirm-message="Rebuild watermark previews for all unpaid project images now?" data-confirm-button="Rebuild Now">
      <?php echo csrf_field(); ?>
      <button class="panel-btn" type="button" data-action-confirm-trigger>Rebuild All Unpaid Watermarks</button>
    </form>
  </section>

  <section class="panel-card" style="margin: 14px 0 0;">
    <h3 class="panel-section-title">Media Folder Maintenance</h3>
    <p class="panel-muted" style="margin-bottom: 10px;">Run one-time migration to move legacy media paths into the new project-name folder structure.</p>
    <form method="post" action="<?php echo e(route('admin.media-delivery.folders.migrate')); ?>" data-action-confirm-form data-confirm-title="Run Media Folder Migration" data-confirm-message="Run media folder migration now? This will move existing files into the new project folder layout." data-confirm-button="Run Migration">
      <?php echo csrf_field(); ?>
      <button class="panel-btn" type="button" data-action-confirm-trigger>Run Media Folder Migration</button>
    </form>
  </section>

  <section class="panel-card" style="margin: 14px 0 0;">
    <h3 class="panel-section-title">Current Logo Preview</h3>
    <?php if($logoExists): ?>
    <div style="padding: 14px; border: 1px dashed rgba(0,0,0,0.14); border-radius: 12px; background: #f8f9fb; display: inline-block;">
      <img src="<?php echo e(route('admin.media-delivery.watermark.logo')); ?>?v=<?php echo e(optional($settings->updated_at)->timestamp); ?>" alt="Watermark logo" style="max-width: 280px; width: 100%; height: auto; display: block;">
    </div>
    <?php else: ?>
    <p class="panel-muted">Upload a PNG logo to preview and apply brand watermark on unpaid previews.</p>
    <?php endif; ?>
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
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.panel', [
  'title' => 'Watermark Settings',
  'heading' => 'Watermark Settings',
  'subheading' => 'Upload watermark logo and control position/size for unpaid gallery previews.',
], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/asifk/projects/maccento/resources/views/admin/media-watermark-settings.blade.php ENDPATH**/ ?>