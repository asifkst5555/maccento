<?php $__env->startSection('content'); ?>
<div class="corp-admin-shell client-media-workspace panel-stack">
  <section class="panel-grid panel-grid-kpi">
    <article class="panel-card">
      <span class="panel-kpi-label">Deliveries Ready</span>
      <p class="panel-kpi-value"><?php echo e($portalStats['deliveries_ready']); ?></p>
    </article>
    <article class="panel-card">
      <span class="panel-kpi-label">Active Projects</span>
      <p class="panel-kpi-value"><?php echo e($portalStats['active_projects']); ?></p>
    </article>
    <article class="panel-card">
      <span class="panel-kpi-label">Unpaid Invoices</span>
      <p class="panel-kpi-value"><?php echo e($portalStats['unpaid_invoices']); ?></p>
    </article>
  </section>

  <section class="panel-card panel-stack">
    <h2 class="panel-section-title">Delivery Workspace</h2>

    <?php $__empty_1 = true; $__currentLoopData = $projects; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $project): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
      <?php
        $galleryItems = $project->media->whereIn('type', ['image', 'video'])->values();
        $zipItems = $project->media->where('type', 'final_zip')->values();
        $isPaid = $project->invoices->contains(fn($invoice) => $invoice->status === 'paid');
        $projectGalleryPayload = $galleryPayloadByProject[$project->id] ?? [];
      ?>

      <article class="panel-card media-project-card" data-project-media-card data-project-id="<?php echo e($project->id); ?>" id="project-<?php echo e($project->id); ?>">
        <div class="panel-form-row media-project-header">
          <div class="media-project-meta">
            <h3 class="media-project-title"><?php echo e($project->title); ?></h3>
            <p class="panel-muted">
              <?php echo e($project->service_type ?: 'Service pending'); ?>

              <?php if(!blank($project->property_address)): ?>
                &bull; <?php echo e($project->property_address); ?>

              <?php endif; ?>
              &bull; <?php echo e($project->status); ?>

            </p>
            <p class="media-project-summary">Gallery: <?php echo e($galleryItems->count()); ?> | Final ZIP: <?php echo e($zipItems->count()); ?> | Payment: <strong><?php echo e($isPaid ? 'Paid' : 'Unpaid'); ?></strong></p>
          </div>

          <div class="media-project-controls">
            <button class="panel-btn panel-btn-primary media-project-toggle" type="button" data-project-toggle aria-expanded="true" aria-label="Toggle delivery details">
              <svg class="media-project-toggle-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 10l4 4 4-4" fill="none" stroke="#ffffff" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
            <?php if($galleryItems->isNotEmpty()): ?>
              <button
                class="panel-btn panel-btn-primary"
                type="button"
                data-gallery-open
                data-project-id="<?php echo e($project->id); ?>"
                data-gallery-items='<?php echo json_encode($projectGalleryPayload, 15, 512) ?>'
              >
                View Media
              </button>
            <?php else: ?>
              <button class="panel-btn panel-btn-primary" type="button" disabled>View Media</button>
            <?php endif; ?>
            <a class="panel-btn" href="<?php echo e(route('user.projects.show', $project)); ?>">Open Project</a>
            <?php if($zipItems->isNotEmpty() && $isPaid): ?>
              <a class="panel-btn panel-btn-primary" href="<?php echo e(route('user.projects.media.download-zip', $project)); ?>">Download Final ZIP</a>
            <?php endif; ?>
          </div>
        </div>

        <div class="media-project-details" data-project-details>
          <div class="panel-grid media-delivery-files-grid">
            <section class="panel-card media-file-list-card">
              <h4 class="panel-section-title">Gallery Files</h4>
              <div class="media-file-list">
                <?php $__empty_2 = true; $__currentLoopData = $galleryItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $mediaItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_2 = false; ?>
                  <?php
                    $mediaName = $mediaItem->original_name;
                  ?>
                  <article class="media-file-row <?php if($index >= 2): ?> is-hidden-by-default <?php endif; ?>" data-gallery-row>
                    <div class="media-file-meta">
                      <span class="media-file-kind"><?php echo e(strtoupper($mediaItem->type)); ?></span>
                      <span class="media-file-name"><?php echo e($mediaName); ?></span>
                    </div>
                    <div class="media-file-actions">
                      <a class="panel-btn" href="<?php echo e(route('user.projects.media.preview', ['project' => $project, 'media' => $mediaItem])); ?>" target="_blank" rel="noopener">Preview</a>
                      <?php if($isPaid): ?>
                        <a class="panel-btn" href="<?php echo e(route('user.projects.media.download', ['project' => $project, 'media' => $mediaItem])); ?>">Download</a>
                      <?php endif; ?>
                    </div>
                  </article>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_2): ?>
                  <p class="panel-muted">No gallery files are uploaded for this project yet.</p>
                <?php endif; ?>

                <?php if($galleryItems->count() > 2): ?>
                  <div class="panel-form-row media-file-list-cta media-file-list-cta-group">
                    <button class="panel-btn" type="button" data-gallery-list-toggle aria-expanded="false">
                      Show All Media List (<?php echo e($galleryItems->count()); ?>)
                    </button>
                    <button
                      class="panel-btn panel-btn-primary"
                      type="button"
                      data-gallery-open
                      data-project-id="<?php echo e($project->id); ?>"
                      data-gallery-items='<?php echo json_encode($projectGalleryPayload, 15, 512) ?>'
                    >
                      View All Gallery Files (<?php echo e($galleryItems->count()); ?>)
                    </button>
                  </div>
                <?php endif; ?>
              </div>
            </section>

            <section class="panel-card media-file-list-card">
              <h4 class="panel-section-title">Final Delivery ZIP</h4>
              <div class="media-file-list">
                <?php $__empty_2 = true; $__currentLoopData = $zipItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $zipItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_2 = false; ?>
                  <article class="media-file-row">
                    <div class="media-file-meta">
                      <span class="media-file-kind">ZIP</span>
                      <span class="media-file-name"><?php echo e($zipItem->original_name); ?></span>
                    </div>
                    <div class="media-file-actions">
                      <?php if($isPaid): ?>
                        <a class="panel-btn panel-btn-primary" href="<?php echo e(route('user.projects.media.download', ['project' => $project, 'media' => $zipItem])); ?>">Download ZIP</a>
                      <?php else: ?>
                        <span class="panel-badge">Unlocks after payment</span>
                      <?php endif; ?>
                    </div>
                  </article>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_2): ?>
                  <p class="panel-muted">No final ZIP uploaded yet.</p>
                <?php endif; ?>
              </div>
            </section>
          </div>
        </div>
      </article>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
      <div class="panel-muted">No project delivery records are available yet.</div>
    <?php endif; ?>

    <?php if (isset($component)) { $__componentOriginal9f9d3eae18f18ccf28f34f84596c1d92 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9f9d3eae18f18ccf28f34f84596c1d92 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.panel-pagination','data' => ['paginator' => $projects]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('panel-pagination'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['paginator' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($projects)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9f9d3eae18f18ccf28f34f84596c1d92)): ?>
<?php $attributes = $__attributesOriginal9f9d3eae18f18ccf28f34f84596c1d92; ?>
<?php unset($__attributesOriginal9f9d3eae18f18ccf28f34f84596c1d92); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9f9d3eae18f18ccf28f34f84596c1d92)): ?>
<?php $component = $__componentOriginal9f9d3eae18f18ccf28f34f84596c1d92; ?>
<?php unset($__componentOriginal9f9d3eae18f18ccf28f34f84596c1d92); ?>
<?php endif; ?>
  </section>
</div>

<?php if (isset($component)) { $__componentOriginalba6d36c42ca97636bd10fd4e4bd0ee08 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalba6d36c42ca97636bd10fd4e4bd0ee08 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.panel-gallery-viewer','data' => ['modalId' => 'user-media-gallery-viewer','openSelector' => '[data-gallery-open]']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('panel-gallery-viewer'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['modal-id' => 'user-media-gallery-viewer','open-selector' => '[data-gallery-open]']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalba6d36c42ca97636bd10fd4e4bd0ee08)): ?>
<?php $attributes = $__attributesOriginalba6d36c42ca97636bd10fd4e4bd0ee08; ?>
<?php unset($__attributesOriginalba6d36c42ca97636bd10fd4e4bd0ee08); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalba6d36c42ca97636bd10fd4e4bd0ee08)): ?>
<?php $component = $__componentOriginalba6d36c42ca97636bd10fd4e4bd0ee08; ?>
<?php unset($__componentOriginalba6d36c42ca97636bd10fd4e4bd0ee08); ?>
<?php endif; ?>

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
<?php $__env->stopSection(); ?>






<?php echo $__env->make('layouts.panel', [
  'title' => 'Media & Deliveries',
  'heading' => 'Media & Deliveries',
  'subheading' => 'Review gallery previews, final delivery files, and payment-gated downloads in one workspace.',
], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/asifk/projects/maccento/resources/views/user/deliveries-index.blade.php ENDPATH**/ ?>