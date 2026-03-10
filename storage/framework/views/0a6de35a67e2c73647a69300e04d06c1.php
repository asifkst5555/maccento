<?php $__env->startSection('content'); ?>
<div class="corp-admin-shell panel-stack">
  <section class="panel-grid panel-grid-kpi">
    <article class="panel-card">
      <span class="panel-kpi-label">Active Projects</span>
      <p class="panel-kpi-value"><?php echo e($portalStats['active_projects']); ?></p>
    </article>
    <article class="panel-card">
      <span class="panel-kpi-label">Deliveries Ready</span>
      <p class="panel-kpi-value"><?php echo e($portalStats['deliveries_ready']); ?></p>
    </article>
    <article class="panel-card">
      <span class="panel-kpi-label">Portal Messages</span>
      <p class="panel-kpi-value"><?php echo e($portalStats['message_count']); ?></p>
    </article>
  </section>

  <section class="panel-card panel-stack">
    <div class="panel-form-row" style="justify-content: space-between; align-items: center;">
      <h2 class="panel-section-title" style="margin: 0;">Project Portfolio</h2>
      <div class="panel-form-row" style="margin-bottom: 0;">
        <a class="panel-btn" href="<?php echo e(route('user.deliveries.index')); ?>">Open Deliveries</a>
        <a class="panel-btn panel-btn-primary" href="<?php echo e(route('user.messages.index')); ?>">Request Service</a>
      </div>
    </div>

    <div class="panel-table-wrap">
      <table class="panel-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Project</th>
            <th>Service</th>
            <th>Schedule</th>
            <th>Due</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php $__empty_1 = true; $__currentLoopData = $projects; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $project): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr>
              <td data-label="ID">#<?php echo e($project->id); ?></td>
              <td data-label="Project">
                <?php echo e($project->title); ?><br>
                <span class="panel-muted"><?php echo e($project->property_address ?: '-'); ?></span>
              </td>
              <td data-label="Service"><?php echo e($project->service_type ?: '-'); ?></td>
              <td data-label="Schedule"><?php echo e($project->scheduled_at?->format('Y-m-d H:i') ?: '-'); ?></td>
              <td data-label="Due"><?php echo e($project->due_at?->format('Y-m-d H:i') ?: '-'); ?></td>
              <td data-label="Status"><span class="panel-badge"><?php echo e($project->status); ?></span></td>
              <td data-label="Action">
                <div class="panel-form-row" style="margin-bottom: 0;">
                  <a class="panel-btn panel-btn-primary" href="<?php echo e(route('user.projects.show', $project)); ?>">Open project</a>
                  <a class="panel-btn" href="<?php echo e(route('user.deliveries.index')); ?>#project-<?php echo e($project->id); ?>">Delivery</a>
                  <?php if($project->quoteBuild): ?>
                    <a class="panel-btn" href="<?php echo e(route('user.quotes.show', $project->quoteBuild)); ?>">Linked Quote</a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr>
              <td colspan="7" class="panel-muted">No projects are currently linked to your client account.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
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
  </section>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.panel', [
  'title' => 'My Projects',
  'heading' => 'Projects',
  'subheading' => 'Monitor schedules, status, and production progress across your account.',
], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/asifk/projects/maccento/resources/views/user/projects-index.blade.php ENDPATH**/ ?>