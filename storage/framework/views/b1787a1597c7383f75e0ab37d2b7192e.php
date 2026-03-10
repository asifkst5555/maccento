<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
    'project',
    'galleryCount' => 0,
    'rawCount' => null,
    'editedCount' => null,
    'zipCount' => 0,
    'isPaid' => false,
    'showClient' => false,
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
    'project',
    'galleryCount' => 0,
    'rawCount' => null,
    'editedCount' => null,
    'zipCount' => 0,
    'isPaid' => false,
    'showClient' => false,
]); ?>
<?php foreach (array_filter(([
    'project',
    'galleryCount' => 0,
    'rawCount' => null,
    'editedCount' => null,
    'zipCount' => 0,
    'isPaid' => false,
    'showClient' => false,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<div>
  <h3 class="panel-section-title" style="margin-bottom: 4px;"><?php echo e($project->title); ?></h3>

  <?php if($showClient): ?>
  <p class="panel-muted" style="margin: 0;">
    <?php echo e($project->client?->name ?: ('Client #' . $project->client_id)); ?>

    • <?php echo e($project->service_type ?: 'Service n/a'); ?>

    • <?php echo e($project->status); ?>

  </p>
  <?php else: ?>
  <p class="panel-muted" style="margin: 0;">
    Status: <?php echo e($project->status); ?>

    <?php if(!blank($project->service_type)): ?>
      • <?php echo e($project->service_type); ?>

    <?php endif; ?>
  </p>
  <?php endif; ?>

  <p class="panel-muted" style="margin: 6px 0 0;">
    <?php if($rawCount !== null || $editedCount !== null): ?>
      Raw: <?php echo e($rawCount ?? 0); ?> | Edited/Final: <?php echo e($editedCount ?? 0); ?> | Final ZIP: <?php echo e($zipCount); ?> |
    <?php else: ?>
      Gallery: <?php echo e($galleryCount); ?> | Final ZIP: <?php echo e($zipCount); ?> |
    <?php endif; ?>
    Payment: <strong><?php echo e($isPaid ? 'Paid' : 'Unpaid'); ?></strong>
  </p>
</div>
<?php /**PATH /home/asifk/projects/maccento/resources/views/components/project-media-summary.blade.php ENDPATH**/ ?>