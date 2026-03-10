<?php $__env->startSection('content'); ?>
<section class="panel-card panel-stack">
  <form method="post" action="<?php echo e(route('admin.form-submissions.status', $submission)); ?>" class="panel-form-row">
    <?php echo csrf_field(); ?>
    <select class="panel-select" name="status">
      <?php $__currentLoopData = ['new','reviewed','qualified','won','lost']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <option value="<?php echo e($status); ?>" <?php if($submission->status === $status): echo 'selected'; endif; ?>><?php echo e(ucfirst($status)); ?></option>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
    <button class="panel-btn panel-btn-primary" type="submit">Update status</button>
  </form>
  <hr class="panel-hr">
  <p><strong>Status:</strong> <span class="panel-badge"><?php echo e($submission->status); ?></span></p>
  <p><strong>Name:</strong> <?php echo e($submission->name ?: '-'); ?></p>
  <p><strong>Company:</strong> <?php echo e($submission->company ?: '-'); ?></p>
  <p><strong>Email:</strong> <?php echo e($submission->email ?: '-'); ?></p>
  <p><strong>Phone:</strong> <?php echo e($submission->phone ?: '-'); ?></p>
  <p><strong>Service:</strong> <?php echo e($submission->service ?: '-'); ?></p>
  <p><strong>Region:</strong> <?php echo e($submission->region ?: '-'); ?></p>
  <p><strong>Page URL:</strong> <?php echo e($submission->page_url ?: '-'); ?></p>
  <p><strong>IP Address:</strong> <?php echo e($submission->ip_address ?: '-'); ?></p>
  <p><strong>Source:</strong> <?php echo e($submission->source ?: '-'); ?></p>
  <hr class="panel-hr">
  <div>
    <h2 class="panel-section-title">Message</h2>
    <p class="panel-muted"><?php echo e($submission->message ?: '-'); ?></p>
  </div>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.panel', [
  'title' => 'Submission #' . $submission->id,
  'heading' => 'Submission #' . $submission->id,
  'subheading' => 'Received ' . ($submission->submitted_at?->format('Y-m-d H:i') ?: '-'),
], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/asifk/projects/maccento/resources/views/admin/form-submission-show.blade.php ENDPATH**/ ?>