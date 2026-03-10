<?php $__env->startSection('content'); ?>
<section class="panel-card">
  <form method="get" class="panel-form-row">
    <input class="panel-input" type="text" name="search" placeholder="Search name, email, phone..." value="<?php echo e($filters['search']); ?>">
    <select class="panel-select" name="status">
      <option value="">All statuses</option>
      <?php $__currentLoopData = ['new','reviewed','qualified','won','lost']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <option value="<?php echo e($status); ?>" <?php if($filters['status'] === $status): echo 'selected'; endif; ?>><?php echo e(ucfirst($status)); ?></option>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
    <button class="panel-btn panel-btn-primary" type="submit">Filter</button>
  </form>
  <div class="panel-table-wrap">
    <table class="panel-table">
      <thead><tr><th>ID</th><th>Submitted</th><th>Name</th><th>Contact</th><th>Service</th><th>Region</th><th>Status</th><th>Action</th></tr></thead>
      <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $submissions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $submission): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <tr>
          <td>#<?php echo e($submission->id); ?></td>
          <td><?php echo e($submission->submitted_at?->format('Y-m-d H:i') ?: '-'); ?></td>
          <td><?php echo e($submission->name ?: '-'); ?></td>
          <td><?php echo e($submission->email ?: ($submission->phone ?: '-')); ?></td>
          <td><?php echo e($submission->service ?: '-'); ?></td>
          <td><?php echo e($submission->region ?: '-'); ?></td>
          <td><span class="panel-badge"><?php echo e($submission->status); ?></span></td>
          <td><a class="panel-link panel-btn-icon" href="<?php echo e(route('admin.form-submissions.show', $submission)); ?>" title="Open submission" aria-label="Open submission"><span class="panel-icon" aria-hidden="true"><svg viewBox="0 0 20 20"><path d="M4 10h12M10 4l6 6-6 6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span></a></td>
        </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <tr><td colspan="8" class="panel-muted">No submissions.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if (isset($component)) { $__componentOriginal9f9d3eae18f18ccf28f34f84596c1d92 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9f9d3eae18f18ccf28f34f84596c1d92 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.panel-pagination','data' => ['paginator' => $submissions]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('panel-pagination'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['paginator' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($submissions)]); ?>
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
  'title' => 'Website Form Submissions',
  'heading' => 'Website Form Submissions',
  'subheading' => 'Inbound website leads from contact forms.',
], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/asifk/projects/maccento/resources/views/admin/form-submissions.blade.php ENDPATH**/ ?>