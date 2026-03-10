<?php $__env->startSection('content'); ?>
<?php
  $galleryItems = $project->media->whereIn('type', ['image', 'video'])->values();
  $zipItems = $project->media->where('type', 'final_zip')->values();
?>
<div class="client-portal-shell">
  <section class="panel-card client-portal-hero">
    <div class="client-portal-hero-head">
      <div>
        <span class="client-portal-eyebrow">Project Workspace</span>
        <h2 class="panel-section-title" style="margin-top: 12px;"><?php echo e($project->title); ?></h2>
        <p class="client-portal-summary">
          <?php echo e($project->service_type ?: 'Service pending'); ?>

          <?php if(!blank($project->property_address)): ?>
            &bull; <?php echo e($project->property_address); ?>

          <?php endif; ?>
          &bull; Status: <?php echo e($project->status); ?>

        </p>
      </div>
      <div class="client-portal-actions">
        <a class="panel-btn" href="<?php echo e(route('user.deliveries.index')); ?>#project-<?php echo e($project->id); ?>">Open Deliveries</a>
        <a class="panel-btn" href="<?php echo e(route('user.messages.index')); ?>">Messages</a>
        <?php if($project->quoteBuild): ?>
          <a class="panel-btn panel-btn-primary" href="<?php echo e(route('user.quotes.show', $project->quoteBuild)); ?>">Linked Quote</a>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <section class="panel-grid panel-grid-kpi-compact">
    <article class="client-portal-kpi">
      <span class="panel-kpi-label">Project Status</span>
      <p class="client-portal-kpi-value"><?php echo e(ucfirst($project->status ?: 'pending')); ?></p>
    </article>
    <article class="client-portal-kpi">
      <span class="panel-kpi-label">Gallery Files</span>
      <p class="client-portal-kpi-value"><?php echo e(collect($galleryPayload)->count()); ?></p>
    </article>
    <article class="client-portal-kpi">
      <span class="panel-kpi-label">Payment State</span>
      <p class="client-portal-kpi-value"><?php echo e($isPaid ? 'Paid' : 'Pending'); ?></p>
    </article>
  </section>

  <section class="client-portal-grid-two">
    <article class="panel-card client-portal-stack">
      <h2 class="panel-section-title">Project Details</h2>
      <div class="client-portal-detail-grid">
        <div class="client-portal-detail">
          <span class="client-portal-detail-label">Project ID</span>
          <p class="client-portal-detail-value">#<?php echo e($project->id); ?></p>
        </div>
        <div class="client-portal-detail">
          <span class="client-portal-detail-label">Schedule</span>
          <p class="client-portal-detail-value"><?php echo e($project->scheduled_at?->format('Y-m-d H:i') ?: 'To be confirmed'); ?></p>
        </div>
        <div class="client-portal-detail">
          <span class="client-portal-detail-label">Client</span>
          <p class="client-portal-detail-value"><?php echo e($project->client?->name ?: '-'); ?></p>
        </div>
      </div>
      <?php if(!blank($project->notes)): ?>
        <div class="client-portal-empty"><?php echo e($project->notes); ?></div>
      <?php endif; ?>
    </article>

    <article class="panel-card client-portal-stack">
      <h2 class="panel-section-title">Request Additional Service</h2>
      <p class="panel-muted">Need an add-on for this project? Send it directly to the team from this workspace.</p>
      <form method="post" action="<?php echo e(route('user.service-requests.store')); ?>" class="panel-stack">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="client_project_id" value="<?php echo e($project->id); ?>">
        <input class="panel-input" type="text" name="requested_service" placeholder="Additional service request" required>
        <input class="panel-input" type="text" name="subject" placeholder="Subject (optional)">
        <input class="panel-input" type="date" name="preferred_date">
        <textarea class="panel-textarea" name="details" placeholder="Describe the request for this project"></textarea>
        <button class="panel-btn panel-btn-primary" type="submit">Send Project Request</button>
      </form>
    </article>
  </section>

  <section class="panel-card client-media-workspace">
    <div class="panel-form-row media-project-header">
      <div class="media-project-meta">
        <h2 class="panel-section-title" style="margin: 0;">Project Media Delivery</h2>
        <p class="media-project-summary">Gallery: <?php echo e($galleryItems->count()); ?> | Final ZIP: <?php echo e($zipItems->count()); ?> | Payment: <strong><?php echo e($isPaid ? 'Paid' : 'Unpaid'); ?></strong></p>
      </div>
      <div class="media-project-controls">
        <?php if($galleryItems->isNotEmpty()): ?>
          <button
            class="panel-btn panel-btn-primary"
            type="button"
            data-gallery-open
            data-project-id="<?php echo e($project->id); ?>"
            data-gallery-items='<?php echo json_encode($galleryPayload, 15, 512) ?>'
          >
            View Media
          </button>
        <?php endif; ?>
        <?php if($zipItems->isNotEmpty() && $isPaid): ?>
          <a class="panel-btn panel-btn-primary" href="<?php echo e(route('user.projects.media.download-zip', $project)); ?>">Download Final ZIP</a>
        <?php endif; ?>
      </div>
    </div>

    <div class="panel-grid media-delivery-files-grid">
      <section class="panel-card media-file-list-card">
        <h4 class="panel-section-title">Gallery Files</h4>
        <div class="media-file-list">
          <?php $__empty_1 = true; $__currentLoopData = $galleryItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $mediaItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <article class="media-file-row">
              <div class="media-file-meta">
                <span class="media-file-kind"><?php echo e(strtoupper($mediaItem->type)); ?></span>
                <span class="media-file-name"><?php echo e($mediaItem->original_name); ?></span>
              </div>
              <div class="media-file-actions">
                <a class="panel-btn" href="<?php echo e(route('user.projects.media.preview', ['project' => $project, 'media' => $mediaItem])); ?>" target="_blank" rel="noopener">Preview</a>
                <?php if($isPaid): ?>
                  <a class="panel-btn" href="<?php echo e(route('user.projects.media.download', ['project' => $project, 'media' => $mediaItem])); ?>">Download</a>
                <?php endif; ?>
              </div>
            </article>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="client-portal-empty">No gallery files are available for this project yet.</div>
          <?php endif; ?>
        </div>
      </section>

      <section class="panel-card media-file-list-card">
        <h4 class="panel-section-title">Final Delivery ZIP</h4>
        <div class="media-file-list">
          <?php $__empty_1 = true; $__currentLoopData = $zipItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $zipItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
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
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="client-portal-empty">No final ZIP is uploaded for this project yet.</div>
          <?php endif; ?>
        </div>
      </section>
    </div>
  </section>

  <section class="client-portal-grid-two">
    <article class="panel-card client-portal-table">
      <h2 class="panel-section-title">Invoices</h2>
      <div class="panel-table-wrap">
        <table class="panel-table">
          <thead>
            <tr>
              <th>Invoice</th>
              <th>Amount</th>
              <th>Status</th>
              <th>Due Date</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $project->invoices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $invoice): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
              <tr>
                <td><?php echo e($invoice->invoice_number); ?></td>
                <td><?php echo e(number_format((float) $invoice->amount, 2)); ?> <?php echo e($invoice->currency); ?></td>
                <td><span class="panel-badge"><?php echo e($invoice->status); ?></span></td>
                <td><?php echo e($invoice->due_date?->format('Y-m-d') ?: '-'); ?></td>
                <td><a class="panel-btn" href="<?php echo e(route('user.invoices.download', $invoice)); ?>">Download PDF</a></td>
              </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
              <tr><td colspan="5" class="panel-muted">No invoices are linked to this project yet.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </article>

    <article class="panel-card client-portal-table">
      <h2 class="panel-section-title">Service Timeline</h2>
      <div class="panel-table-wrap">
        <table class="panel-table">
          <thead>
            <tr>
              <th>Service Request</th>
              <th>Status</th>
              <th>Preferred Date</th>
            </tr>
          </thead>
          <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $project->serviceRequests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $requestItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
              <tr>
                <td>
                  <?php echo e($requestItem->requested_service); ?>

                  <?php if(!blank($requestItem->subject)): ?>
                    <div class="panel-muted"><?php echo e($requestItem->subject); ?></div>
                  <?php endif; ?>
                </td>
                <td><span class="panel-badge"><?php echo e($requestItem->status); ?></span></td>
                <td><?php echo e($requestItem->preferred_date?->format('Y-m-d') ?: '-'); ?></td>
              </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
              <tr><td colspan="3" class="panel-muted">No service activity is logged for this project yet.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </article>
  </section>

  <section class="project-team-grid">
    <article class="panel-card project-team-card">
      <h2 class="panel-section-title">Assigned Team</h2>
      <div class="project-assignee-list">
        <?php $__empty_1 = true; $__currentLoopData = $project->assignments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $assignment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
          <span class="project-assignee-chip">
            <span><?php echo e($assignment->user?->name ?: 'Unknown team member'); ?></span>
            <span class="project-assignee-role"><?php echo e(ucfirst($assignment->user?->role ?: 'staff')); ?></span>
          </span>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
          <span class="panel-muted">No team members are assigned to this project yet.</span>
        <?php endif; ?>
      </div>
    </article>

    <article class="panel-card project-discussion-card">
      <h2 class="panel-section-title">Project Discussion</h2>
      <div class="project-discussion-stream">
        <?php $__empty_1 = true; $__currentLoopData = $project->comments->sortBy('id'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $comment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
          <article class="project-comment-card <?php echo e($comment->sender_role === 'client' ? '' : 'is-internal'); ?>">
            <div class="project-comment-head">
              <div class="project-comment-author">
                <span class="project-comment-avatar"><?php echo e(\Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($comment->user?->name ?: ($comment->sender_role ?: 'U'), 0, 2))); ?></span>
                <div>
                  <p class="project-comment-name"><?php echo e($comment->user?->name ?: ucfirst($comment->sender_role)); ?></p>
                  <p class="project-comment-meta"><?php echo e(ucfirst(str_replace('_', ' ', $comment->sender_role))); ?></p>
                </div>
              </div>
              <span class="project-comment-time"><?php echo e($comment->created_at?->format('M j, Y g:i A')); ?></span>
            </div>
            <p class="project-comment-body"><?php echo e($comment->body); ?></p>
          </article>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
          <div class="client-portal-empty">No project discussion is available yet.</div>
        <?php endif; ?>
      </div>

      <form method="post" action="<?php echo e(route('user.projects.comments.store', $project)); ?>" class="project-discussion-form">
        <?php echo csrf_field(); ?>
        <textarea class="panel-textarea" name="body" placeholder="Write a comment for the project team" required><?php echo e(old('body')); ?></textarea>
        <button class="panel-btn panel-btn-primary" type="submit">Post Comment</button>
      </form>
    </article>
  </section>
</div>

<?php if (isset($component)) { $__componentOriginalba6d36c42ca97636bd10fd4e4bd0ee08 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalba6d36c42ca97636bd10fd4e4bd0ee08 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.panel-gallery-viewer','data' => ['modalId' => 'user-project-gallery-viewer','openSelector' => '[data-gallery-open]']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('panel-gallery-viewer'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['modal-id' => 'user-project-gallery-viewer','open-selector' => '[data-gallery-open]']); ?>
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
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.panel', [
  'title' => $project->title,
  'heading' => $project->title,
  'subheading' => 'Project workspace with delivery, billing, and communication history.',
], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/asifk/projects/maccento/resources/views/user/project-show.blade.php ENDPATH**/ ?>