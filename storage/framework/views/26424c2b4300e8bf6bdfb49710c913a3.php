<?php $__env->startSection('content'); ?>
<section class="panel-card">
  <h2 class="panel-section-title">Create Quote Manually</h2>
  <form method="post" action="<?php echo e(route('admin.quotes.manual-store')); ?>" class="panel-form-row">
    <?php echo csrf_field(); ?>
    <input class="panel-input" type="text" name="contact_name" placeholder="Client name" required>
    <input class="panel-input" type="email" name="contact_email" placeholder="Client email">
    <input class="panel-input" type="text" name="contact_phone" placeholder="Client phone">
    <input class="panel-input" type="text" name="services" placeholder="Services (comma separated)" required>
    <select class="panel-select" name="listing_type">
      <option value="home">Home</option>
      <option value="condo">Condo</option>
      <option value="rental">Rental</option>
      <option value="chalet">Chalet</option>
      <option value="other" selected>Other</option>
    </select>
    <input class="panel-input" type="number" name="estimated_total" min="0" placeholder="Estimated total" required>
    <input class="panel-input" type="text" name="currency" value="USD" placeholder="Currency">
    <button class="panel-btn panel-btn-primary" type="submit">Create Quote</button>
    <textarea class="panel-textarea" name="notes" placeholder="Optional notes" style="flex-basis:100%;"></textarea>
  </form>
</section>

<section class="panel-card">
  <div class="panel-sticky-filters">
    <form method="get" class="panel-form-row">
      <input class="panel-input" type="text" name="quote_search" placeholder="Search quote/contact" value="<?php echo e($filters['quote_search']); ?>">
      <select class="panel-select" name="quote_status">
        <option value="">All statuses</option>
        <?php $__currentLoopData = ['new','reviewed','contacted','booked','lost']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <option value="<?php echo e($status); ?>" <?php if($filters['quote_status'] === $status): echo 'selected'; endif; ?>><?php echo e(ucfirst($status)); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </select>
      <input class="panel-input" type="number" name="min_total" placeholder="Min total" value="<?php echo e($filters['min_total']); ?>">
      <input class="panel-input" type="number" name="max_total" placeholder="Max total" value="<?php echo e($filters['max_total']); ?>">
      <button class="panel-btn panel-btn-primary" type="submit">Filter</button>
      <a class="panel-link" href="<?php echo e(route('admin.quotes.index')); ?>">Clear</a>
    </form>
    <div class="panel-form-row">
      <?php if($widgetVisibility['can_export_data']): ?>
      <form method="get" action="<?php echo e(route('admin.exports.quotes')); ?>" class="panel-form-row">
        <input type="hidden" name="quote_search" value="<?php echo e($filters['quote_search']); ?>">
        <input type="hidden" name="quote_status" value="<?php echo e($filters['quote_status']); ?>">
        <input type="hidden" name="min_total" value="<?php echo e($filters['min_total']); ?>">
        <input type="hidden" name="max_total" value="<?php echo e($filters['max_total']); ?>">
        <input class="panel-input" type="date" name="from_date" value="<?php echo e($filters['quotes_from_date']); ?>">
        <input class="panel-input" type="date" name="to_date" value="<?php echo e($filters['quotes_to_date']); ?>">
        <button class="panel-btn" type="submit">Export CSV</button>
      </form>
      <?php else: ?>
      <span class="panel-badge">Manager: export disabled</span>
      <?php endif; ?>
    </div>
  </div>

  <div class="panel-table-wrap">
    <table class="panel-table">
      <thead><tr><th>Quote ID</th><th>Package</th><th>Status</th><th>Total</th><th>Submitted</th><th>Action</th></tr></thead>
      <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $quotes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $quote): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <tr>
          <td><?php echo e($quote->quote_id); ?></td>
          <td><?php echo e(data_get($quote->options, 'package_title', ucfirst((string) data_get($quote->options, 'package_code', 'custom')))); ?></td>
          <td><span class="panel-badge"><?php echo e($quote->status); ?></span></td>
          <td>
            <?php if(data_get($quote->options, 'display_total')): ?>
              <?php echo e(data_get($quote->options, 'display_total')); ?> <?php echo e($quote->currency); ?>

            <?php else: ?>
              <?php echo e(number_format((int) $quote->estimated_total)); ?> <?php echo e($quote->currency); ?>

            <?php endif; ?>
          </td>
          <td><?php echo e($quote->submitted_at?->format('Y-m-d H:i') ?: '-'); ?></td>
          <td>
            <a class="panel-link panel-btn-icon" href="<?php echo e(route('admin.quotes.show', $quote)); ?>" title="Open quote" aria-label="Open quote"><span class="panel-icon" aria-hidden="true"><svg viewBox="0 0 20 20"><path d="M4 10h12M10 4l6 6-6 6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span></a>
            <form method="post" action="<?php echo e(route('admin.quotes.delete', $quote)); ?>" style="display:inline-block; margin-left:8px;" data-confirm="Delete this quote?">
              <?php echo csrf_field(); ?>
              <button class="panel-btn panel-btn-danger panel-btn-icon" type="submit" title="Delete quote" aria-label="Delete quote"><span class="panel-icon-trash" aria-hidden="true"><?php if (isset($component)) { $__componentOriginal669c17867d57615948fae15a035429b3 = $component; } ?>
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
          </td>
        </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <tr><td colspan="6" class="panel-muted">No quotes yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if (isset($component)) { $__componentOriginal9f9d3eae18f18ccf28f34f84596c1d92 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9f9d3eae18f18ccf28f34f84596c1d92 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.panel-pagination','data' => ['paginator' => $quotes]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('panel-pagination'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['paginator' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($quotes)]); ?>
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
  'title' => 'Quote Pipeline',
  'heading' => 'Quote Pipeline',
  'subheading' => 'Dedicated quote management workspace with corporate separation.',
], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/asifk/projects/maccento/resources/views/admin/quotes-index.blade.php ENDPATH**/ ?>