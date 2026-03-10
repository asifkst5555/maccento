

<?php $__env->startSection('content'); ?>
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
  <?php
    $canOpenWatermarkSettings = in_array(strtolower(trim((string) auth()->user()?->role)), ['owner', 'admin', 'manager'], true);
  ?>
  <div class="panel-sticky-filters">
    <form method="get" class="panel-form-row">
      <input class="panel-input" type="text" name="media_search" value="<?php echo e($filters['media_search']); ?>" placeholder="Search project/client/service/address">
      <button class="panel-btn panel-btn-primary" type="submit">Search</button>
      <a class="panel-link" href="<?php echo e(route('admin.media-delivery.index')); ?>">Clear</a>
      <?php if($canOpenWatermarkSettings): ?>
      <a class="panel-link" href="<?php echo e(route('admin.media-delivery.watermark.index')); ?>">Watermark Settings</a>
      <?php endif; ?>
    </form>
  </div>

  <div class="panel-stack">
    <?php if($isScopedMediaUser): ?>
    <p class="panel-muted">Showing only projects assigned to you. Uploads are stored under each project in your role and user folder.</p>
    <?php endif; ?>
    <?php $__empty_1 = true; $__currentLoopData = $projects; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $project): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
    <?php
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
    ?>
    <article class="panel-card media-project-card" data-project-media-card="<?php echo e($project->id); ?>" data-project-id="<?php echo e($project->id); ?>">
      <?php
        $assignmentIds = $project->assignments->pluck('user_id')->map(static fn ($id): int => (int) $id)->all();
        $canUploadProjectMedia = ($canUploadMedia ?? false) && (!($isScopedMediaUser ?? false) || in_array((int) auth()->id(), $assignmentIds, true));
      ?>
      <div class="panel-form-row media-project-header" style="justify-content: space-between; align-items: flex-start; gap: 12px; flex-wrap: wrap;">
        <?php if (isset($component)) { $__componentOriginal30a558c568a448490f2402febc495ca2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal30a558c568a448490f2402febc495ca2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.project-media-summary','data' => ['project' => $project,'galleryCount' => $galleryItems->count(),'rawCount' => $rawItems->count(),'editedCount' => $editedItems->count(),'zipCount' => $zipItems->count(),'isPaid' => $isPaid,'showClient' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('project-media-summary'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['project' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($project),'gallery-count' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($galleryItems->count()),'raw-count' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($rawItems->count()),'edited-count' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($editedItems->count()),'zip-count' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($zipItems->count()),'is-paid' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($isPaid),'show-client' => true]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal30a558c568a448490f2402febc495ca2)): ?>
<?php $attributes = $__attributesOriginal30a558c568a448490f2402febc495ca2; ?>
<?php unset($__attributesOriginal30a558c568a448490f2402febc495ca2); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal30a558c568a448490f2402febc495ca2)): ?>
<?php $component = $__componentOriginal30a558c568a448490f2402febc495ca2; ?>
<?php unset($__componentOriginal30a558c568a448490f2402febc495ca2); ?>
<?php endif; ?>
        <div class="panel-form-row" style="margin-bottom: 0;">
          <button class="panel-btn panel-btn-primary media-project-toggle" type="button" data-project-toggle aria-expanded="true" aria-label="Toggle project details" style="color: #fff; border-color: #a8162a;">
            <svg class="media-project-toggle-icon" viewBox="0 0 24 24" aria-hidden="true" style="width: 32px; height: 32px; color: #fff;"><path d="M8 10l4 4 4-4" fill="none" stroke="#ffffff" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </button>
          <?php if($preferredPreviewItems->isNotEmpty()): ?>
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
          <a class="panel-link" href="<?php echo e(route('admin.clients.show', ['client' => $project->client_id, 'project_id' => $project->id])); ?>">Open Project</a>
          <a class="panel-link" href="<?php echo e(route('admin.clients.show', $project->client_id)); ?>">Open Client</a>
          <?php if($canViewInvoices ?? false): ?>
          <a class="panel-link" href="<?php echo e(route('admin.invoices.index', ['invoice_project' => $project->id])); ?>">Project Invoice</a>
          <?php endif; ?>
        </div>
      </div>

      <div class="media-project-details" data-project-details>

      <?php if($canUploadProjectMedia): ?>
      <div class="media-stage-section">
        <div class="media-delivery-upload-grid">
          <article class="media-delivery-upload-card">
            <h4 class="panel-section-title">Raw Media Gallery</h4>
            <form method="post" action="<?php echo e(route('admin.projects.media.store', $project)); ?>" class="panel-stack" enctype="multipart/form-data">
              <?php echo csrf_field(); ?>
              <input type="hidden" name="media_stage" value="raw">
              <label class="panel-muted">Upload Raw Footage Media</label>
              <input class="panel-input" type="file" name="media_files[]" accept="image/*,video/*" multiple required>
              <button class="panel-btn panel-btn-primary" type="submit">Upload Raw Footage</button>
            </form>
            <form method="post" action="<?php echo e(route('admin.projects.raw-zip.store', $project)); ?>" class="panel-stack" enctype="multipart/form-data">
              <?php echo csrf_field(); ?>
              <label class="panel-muted">Upload Raw Footage ZIP</label>
              <input class="panel-input" type="file" name="raw_zip" accept=".zip,application/zip" required>
              <button class="panel-btn" type="submit">Upload Raw ZIP</button>
            </form>
          </article>

          <section class="panel-card media-file-list-card">
            <h4 class="panel-section-title">Raw Footage Media</h4>
            <div class="media-file-list">
              <?php $__empty_2 = true; $__currentLoopData = $rawItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $mediaItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_2 = false; ?>
              <?php
                $mediaName = $mediaItem->original_name;
              ?>
              <article class="media-file-row <?php if($index >= 2): ?> is-hidden-by-default <?php endif; ?>" data-gallery-row>
                <div class="media-file-meta">
                  <span class="media-file-kind"><?php echo e(strtoupper($mediaItem->type)); ?></span>
                  <span class="media-file-name"><?php echo e($mediaName); ?></span>
                  <span class="panel-muted">Uploaded by <?php echo e($mediaItem->uploader?->name ?: 'System'); ?> <?php if($mediaItem->uploader?->role): ?>&bull; <?php echo e(ucfirst($mediaItem->uploader->role)); ?> <?php endif; ?></span>
                </div>
                <div class="media-file-actions">
                  <a class="panel-link" href="<?php echo e(route('admin.projects.media.view', ['project' => $project, 'media' => $mediaItem])); ?>" target="_blank" rel="noopener">View</a>
                  <?php if($canDeleteMedia): ?>
                  <form method="post" action="<?php echo e(route('admin.projects.media.delete', ['project' => $project, 'media' => $mediaItem])); ?>" data-delete-form data-delete-name="<?php echo e($mediaItem->original_name); ?>">
                    <?php echo csrf_field(); ?>
                    <button class="panel-btn panel-btn-danger panel-btn-icon" type="button" data-delete-trigger title="Delete media" aria-label="Delete media"><span class="panel-icon-trash" aria-hidden="true"><?php if (isset($component)) { $__componentOriginal669c17867d57615948fae15a035429b3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal669c17867d57615948fae15a035429b3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.panel-icon','data' => ['name' => 'trash']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('panel-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'trash']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal669c17867d57615948fae15a035429b3)): ?>
<?php $attributes = $__attributesOriginal669c17867d57615948fae15a035429b3; ?>
<?php unset($__attributesOriginal669c17867d57615948fae15a035429b3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal669c17867d57615948fae15a035429b3)): ?>
<?php $component = $__componentOriginal669c17867d57615948fae15a035429b3; ?>
<?php unset($__componentOriginal669c17867d57615948fae15a035429b3); ?>
<?php endif; ?></span></button>
                  </form>
                  <?php endif; ?>
                </div>
              </article>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_2): ?>
              <p class="panel-muted">No raw footage media yet.</p>
              <?php endif; ?>
              <?php if($rawZipItems->isNotEmpty()): ?>
                <?php $__currentLoopData = $rawZipItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rawZipItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <article class="media-file-row">
                  <div class="media-file-meta">
                    <span class="media-file-kind">RAW ZIP</span>
                    <span class="media-file-name"><?php echo e($rawZipItem->original_name); ?></span>
                    <span class="panel-muted">Uploaded by <?php echo e($rawZipItem->uploader?->name ?: 'System'); ?> <?php if($rawZipItem->uploader?->role): ?>&bull; <?php echo e(ucfirst($rawZipItem->uploader->role)); ?> <?php endif; ?></span>
                  </div>
                  <div class="media-file-actions">
                    <a class="panel-link" href="<?php echo e(route('admin.projects.media.view', ['project' => $project, 'media' => $rawZipItem])); ?>" target="_blank" rel="noopener">View ZIP</a>
                    <?php if($canDeleteMedia): ?>
                    <form method="post" action="<?php echo e(route('admin.projects.media.delete', ['project' => $project, 'media' => $rawZipItem])); ?>" data-delete-form data-delete-name="<?php echo e($rawZipItem->original_name); ?>">
                      <?php echo csrf_field(); ?>
                      <button class="panel-btn panel-btn-danger panel-btn-icon" type="button" data-delete-trigger title="Delete raw ZIP" aria-label="Delete raw ZIP"><span class="panel-icon-trash" aria-hidden="true"><?php if (isset($component)) { $__componentOriginal669c17867d57615948fae15a035429b3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal669c17867d57615948fae15a035429b3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.panel-icon','data' => ['name' => 'trash']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('panel-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'trash']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal669c17867d57615948fae15a035429b3)): ?>
<?php $attributes = $__attributesOriginal669c17867d57615948fae15a035429b3; ?>
<?php unset($__attributesOriginal669c17867d57615948fae15a035429b3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal669c17867d57615948fae15a035429b3)): ?>
<?php $component = $__componentOriginal669c17867d57615948fae15a035429b3; ?>
<?php unset($__componentOriginal669c17867d57615948fae15a035429b3); ?>
<?php endif; ?></span></button>
                    </form>
                    <?php endif; ?>
                  </div>
                </article>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
              <?php endif; ?>
              <?php if($rawItems->count() > 2): ?>
              <div class="panel-form-row media-file-list-cta media-file-list-cta-group">
                <button class="panel-btn" type="button" data-gallery-list-toggle aria-expanded="false">Show All Raw Media (<?php echo e($rawItems->count()); ?>)</button>
                <button class="panel-btn panel-btn-primary" type="button" data-gallery-open data-project-id="<?php echo e($project->id); ?>" data-gallery-items='<?php echo json_encode($rawGalleryPayload, 15, 512) ?>'>View Raw Footage (<?php echo e($rawItems->count()); ?>)</button>
              </div>
              <?php endif; ?>
            </div>
          </section>
        </div>
      </div>
      <?php endif; ?>

      <div class="media-stage-section">
        <div class="media-delivery-upload-grid">
          <?php if($canUploadProjectMedia): ?>
          <article class="media-delivery-upload-card">
            <h4 class="panel-section-title">Edited/Final Media Upload</h4>
            <form method="post" action="<?php echo e(route('admin.projects.media.store', $project)); ?>" class="panel-stack" enctype="multipart/form-data">
              <?php echo csrf_field(); ?>
              <input type="hidden" name="media_stage" value="edited">
              <label class="panel-muted">Upload Edited/Final Media Files</label>
              <input class="panel-input" type="file" name="media_files[]" accept="image/*,video/*" multiple required>
              <button class="panel-btn panel-btn-primary" type="submit">Upload Edited/Final Media</button>
            </form>
          </article>
          <?php endif; ?>

          <section class="panel-card media-file-list-card">
            <h4 class="panel-section-title">Edited/Final Media Files</h4>
            <div class="media-file-list">
              <?php $__empty_2 = true; $__currentLoopData = $editedItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $mediaItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_2 = false; ?>
              <?php
                $mediaName = $mediaItem->original_name;
              ?>
              <article class="media-file-row <?php if($index >= 2): ?> is-hidden-by-default <?php endif; ?>" data-gallery-row>
                <div class="media-file-meta">
                  <span class="media-file-kind"><?php echo e(strtoupper($mediaItem->type)); ?></span>
                  <span class="media-file-name"><?php echo e($mediaName); ?></span>
                  <span class="panel-muted">Uploaded by <?php echo e($mediaItem->uploader?->name ?: 'System'); ?> <?php if($mediaItem->uploader?->role): ?>&bull; <?php echo e(ucfirst($mediaItem->uploader->role)); ?> <?php endif; ?></span>
                </div>
                <div class="media-file-actions">
                  <a class="panel-link" href="<?php echo e(route('admin.projects.media.view', ['project' => $project, 'media' => $mediaItem])); ?>" target="_blank" rel="noopener">View</a>
                  <?php if($canDeleteMedia): ?>
                  <form method="post" action="<?php echo e(route('admin.projects.media.delete', ['project' => $project, 'media' => $mediaItem])); ?>" data-delete-form data-delete-name="<?php echo e($mediaItem->original_name); ?>">
                    <?php echo csrf_field(); ?>
                    <button class="panel-btn panel-btn-danger panel-btn-icon" type="button" data-delete-trigger title="Delete media" aria-label="Delete media"><span class="panel-icon-trash" aria-hidden="true"><?php if (isset($component)) { $__componentOriginal669c17867d57615948fae15a035429b3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal669c17867d57615948fae15a035429b3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.panel-icon','data' => ['name' => 'trash']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('panel-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'trash']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal669c17867d57615948fae15a035429b3)): ?>
<?php $attributes = $__attributesOriginal669c17867d57615948fae15a035429b3; ?>
<?php unset($__attributesOriginal669c17867d57615948fae15a035429b3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal669c17867d57615948fae15a035429b3)): ?>
<?php $component = $__componentOriginal669c17867d57615948fae15a035429b3; ?>
<?php unset($__componentOriginal669c17867d57615948fae15a035429b3); ?>
<?php endif; ?></span></button>
                  </form>
                  <?php endif; ?>
                </div>
              </article>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_2): ?>
              <p class="panel-muted">No edited/final media files yet.</p>
              <?php endif; ?>
              <?php if($editedItems->count() > 2): ?>
              <div class="panel-form-row media-file-list-cta media-file-list-cta-group">
                <button class="panel-btn" type="button" data-gallery-list-toggle aria-expanded="false">Show All Edited/Final Media (<?php echo e($editedItems->count()); ?>)</button>
                <button class="panel-btn panel-btn-primary" type="button" data-gallery-open data-project-id="<?php echo e($project->id); ?>" data-gallery-items='<?php echo json_encode($editedGalleryPayload, 15, 512) ?>'>View Edited/Final Media (<?php echo e($editedItems->count()); ?>)</button>
              </div>
              <?php endif; ?>
            </div>
          </section>
        </div>
      </div>

      <div class="media-stage-section">
        <div class="media-delivery-upload-grid">
          <?php if($canUploadProjectMedia): ?>
          <article class="media-delivery-upload-card">
            <h4 class="panel-section-title">Final ZIP Upload</h4>
            <form method="post" action="<?php echo e(route('admin.projects.delivery-zip.store', $project)); ?>" class="panel-stack" enctype="multipart/form-data">
              <?php echo csrf_field(); ?>
              <label class="panel-muted">Upload Final Delivery ZIP</label>
              <input class="panel-input" type="file" name="delivery_zip" accept=".zip,application/zip" required>
              <button class="panel-btn" type="submit">Upload Final ZIP</button>
            </form>
          </article>
          <?php endif; ?>

          <section class="panel-card media-file-list-card">
            <h4 class="panel-section-title">Final Delivery ZIP</h4>
            <div class="media-file-list">
              <?php $__empty_2 = true; $__currentLoopData = $zipItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $zipItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_2 = false; ?>
              <?php
                $zipName = $zipItem->original_name;
              ?>
              <article class="media-file-row">
                <div class="media-file-meta">
                  <span class="media-file-kind">ZIP</span>
                  <span class="media-file-name"><?php echo e($zipName); ?></span>
                  <span class="panel-muted">Uploaded by <?php echo e($zipItem->uploader?->name ?: 'System'); ?> <?php if($zipItem->uploader?->role): ?>&bull; <?php echo e(ucfirst($zipItem->uploader->role)); ?> <?php endif; ?></span>
                </div>
                <div class="media-file-actions">
                  <a class="panel-link" href="<?php echo e(route('admin.projects.media.view', ['project' => $project, 'media' => $zipItem])); ?>" target="_blank" rel="noopener">View ZIP</a>
                  <?php if($canDeleteMedia): ?>
                  <form method="post" action="<?php echo e(route('admin.projects.media.delete', ['project' => $project, 'media' => $zipItem])); ?>" data-delete-form data-delete-name="<?php echo e($zipItem->original_name); ?>">
                    <?php echo csrf_field(); ?>
                    <button class="panel-btn panel-btn-danger panel-btn-icon" type="button" data-delete-trigger title="Delete ZIP" aria-label="Delete ZIP"><span class="panel-icon-trash" aria-hidden="true"><?php if (isset($component)) { $__componentOriginal669c17867d57615948fae15a035429b3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal669c17867d57615948fae15a035429b3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.panel-icon','data' => ['name' => 'trash']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('panel-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'trash']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal669c17867d57615948fae15a035429b3)): ?>
<?php $attributes = $__attributesOriginal669c17867d57615948fae15a035429b3; ?>
<?php unset($__attributesOriginal669c17867d57615948fae15a035429b3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal669c17867d57615948fae15a035429b3)): ?>
<?php $component = $__componentOriginal669c17867d57615948fae15a035429b3; ?>
<?php unset($__componentOriginal669c17867d57615948fae15a035429b3); ?>
<?php endif; ?></span></button>
                  </form>
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
      </div>
    </article>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
    <p class="panel-muted">No projects found.</p>
    <?php endif; ?>
  </div>

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

  <?php if(!$canUploadMedia): ?>
  <p class="panel-muted" style="margin-top: 1rem;">Your role is read-only for media uploads. Contact an admin/owner/manager to upload files.</p>
  <?php endif; ?>
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

<?php if (isset($component)) { $__componentOriginalba6d36c42ca97636bd10fd4e4bd0ee08 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalba6d36c42ca97636bd10fd4e4bd0ee08 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.panel-gallery-viewer','data' => ['modalId' => 'media-delivery-viewer','openSelector' => '[data-gallery-open]','titleDefault' => 'Gallery Viewer','deleteEnabled' => true,'deleteUrlTemplate' => ''.e(url('/admin/projects/__PROJECT__/media/__MEDIA__/delete')).'','csrfToken' => ''.e(csrf_token()).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('panel-gallery-viewer'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['modal-id' => 'media-delivery-viewer','open-selector' => '[data-gallery-open]','title-default' => 'Gallery Viewer','delete-enabled' => true,'delete-url-template' => ''.e(url('/admin/projects/__PROJECT__/media/__MEDIA__/delete')).'','csrf-token' => ''.e(csrf_token()).'']); ?>
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
<?php if (isset($component)) { $__componentOriginal1ca76583b5ec7cbecd512de15b7fa213 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1ca76583b5ec7cbecd512de15b7fa213 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.panel-delete-confirm-modal','data' => ['modalId' => 'media-delete-confirm-modal','triggerSelector' => '[data-delete-trigger]','title' => 'Delete Media File']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('panel-delete-confirm-modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['modal-id' => 'media-delete-confirm-modal','trigger-selector' => '[data-delete-trigger]','title' => 'Delete Media File']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1ca76583b5ec7cbecd512de15b7fa213)): ?>
<?php $attributes = $__attributesOriginal1ca76583b5ec7cbecd512de15b7fa213; ?>
<?php unset($__attributesOriginal1ca76583b5ec7cbecd512de15b7fa213); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1ca76583b5ec7cbecd512de15b7fa213)): ?>
<?php $component = $__componentOriginal1ca76583b5ec7cbecd512de15b7fa213; ?>
<?php unset($__componentOriginal1ca76583b5ec7cbecd512de15b7fa213); ?>
<?php endif; ?>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.panel', [
  'title' => 'Media Delivery',
  'heading' => 'Media Delivery Workspace',
  'subheading' => 'Upload gallery media, upload final ZIP, and manage paid/unpaid delivery in one place.',
], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/asifk/projects/maccento/resources/views/admin/media-delivery-index.blade.php ENDPATH**/ ?>