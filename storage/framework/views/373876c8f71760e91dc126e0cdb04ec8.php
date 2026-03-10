<?php $__env->startSection('content'); ?>
<section class="panel-card">
  <div class="panel-sticky-filters">
    <form method="get" class="panel-form-row">
      <input class="panel-input" type="text" name="lead_search" placeholder="Search AI lead" value="<?php echo e($filters['lead_search']); ?>">
      <select class="panel-select" name="lead_status">
        <option value="">All statuses</option>
        <?php $__currentLoopData = ['new','qualified','contacted','won','lost','nurturing']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <option value="<?php echo e($status); ?>" <?php if($filters['lead_status'] === $status): echo 'selected'; endif; ?>><?php echo e(ucfirst($status)); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </select>
      <button class="panel-btn panel-btn-primary" type="submit">Filter</button>
      <a class="panel-link" href="<?php echo e(route('admin.leads.ai.index')); ?>">Clear</a>
    </form>
    <div class="panel-form-row">
      <?php if($widgetVisibility['can_export_data']): ?>
      <form method="get" action="<?php echo e(route('admin.exports.leads')); ?>" class="panel-form-row">
        <input type="hidden" name="lead_search" value="<?php echo e($filters['lead_search']); ?>">
        <input type="hidden" name="lead_status" value="<?php echo e($filters['lead_status']); ?>">
        <input type="hidden" name="lead_channel" value="website_widget">
        <input class="panel-input" type="date" name="from_date" value="<?php echo e($filters['leads_from_date']); ?>">
        <input class="panel-input" type="date" name="to_date" value="<?php echo e($filters['leads_to_date']); ?>">
        <button class="panel-btn" type="submit">Export CSV</button>
      </form>
      <?php else: ?>
      <span class="panel-badge">Manager: export disabled</span>
      <?php endif; ?>
    </div>
  </div>

  <div class="panel-table-wrap">
    <table class="panel-table">
      <thead><tr><th>ID</th><th>Name</th><th>Contact</th><th>Service</th><th>Status</th><th>Score</th><th>Source</th><th>Action</th></tr></thead>
      <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $leads; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $lead): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <tr>
          <td>#<?php echo e($lead->id); ?></td>
          <td><?php echo e($lead->name ?: '-'); ?></td>
          <td><?php echo e($lead->email ?: ($lead->phone ?: '-')); ?></td>
          <td><?php echo e($lead->service_type ?: '-'); ?></td>
          <td><span class="panel-badge"><?php echo e($lead->status); ?></span></td>
          <td><?php echo e($lead->score); ?></td>
          <td><span class="panel-badge"><?php echo e($lead->conversation?->channel ?: 'website_widget'); ?></span></td>
          <td><a class="panel-link panel-btn-icon" href="<?php echo e(route('admin.leads.show', $lead)); ?>" title="Open lead" aria-label="Open lead"><span class="panel-icon" aria-hidden="true"><svg viewBox="0 0 20 20"><path d="M4 10h12M10 4l6 6-6 6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span></a></td>
        </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <tr><td colspan="8" class="panel-muted">No AI assistant leads yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if (isset($component)) { $__componentOriginal9f9d3eae18f18ccf28f34f84596c1d92 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9f9d3eae18f18ccf28f34f84596c1d92 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.panel-pagination','data' => ['paginator' => $leads]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('panel-pagination'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['paginator' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($leads)]); ?>
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
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.panel', [
  'title' => 'Leads from AI Assistant',
  'heading' => 'Leads from AI Assistant',
  'subheading' => 'Only leads captured via AI chat widget (channel: website_widget).',
], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/asifk/projects/maccento/resources/views/admin/ai-leads-index.blade.php ENDPATH**/ ?>