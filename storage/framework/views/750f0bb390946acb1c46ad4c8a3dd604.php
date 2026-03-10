<?php $__env->startSection('content'); ?>
<div class="client-portal-shell">
  <?php
    $quoteStatusClass = static function (?string $status): string {
      return 'client-status-chip status-' . \Illuminate\Support\Str::slug((string) $status);
    };
  ?>
  <section class="panel-grid panel-grid-kpi-compact">
    <article class="client-portal-kpi">
      <span class="panel-kpi-label">Pending Quotes</span>
      <p class="client-portal-kpi-value"><?php echo e($portalStats['pending_quotes']); ?></p>
    </article>
    <article class="client-portal-kpi">
      <span class="panel-kpi-label">Lead Records</span>
      <p class="client-portal-kpi-value"><?php echo e($portalStats['lead_count']); ?></p>
    </article>
    <article class="client-portal-kpi">
      <span class="panel-kpi-label">Portal Messages</span>
      <p class="client-portal-kpi-value"><?php echo e($portalStats['message_count']); ?></p>
    </article>
  </section>

  <section class="panel-card client-portal-table client-portal-card-accent">
    <div class="client-portal-section-head">
      <div class="client-portal-section-copy">
        <h2 class="panel-section-title" style="margin: 0;">Quote History</h2>
        <p class="client-portal-subtle" style="margin: 8px 0 0;">Review estimate progress, compare submitted scopes, and open detailed quote records.</p>
      </div>
      <a class="panel-btn" href="<?php echo e(route('user.messages.index')); ?>">Request Changes</a>
    </div>
    <div class="panel-table-wrap">
      <table class="panel-table">
        <thead>
          <tr>
            <th>Quote ID</th>
            <th>Listing</th>
            <th>Services</th>
            <th>Status</th>
            <th>Total</th>
            <th>Submitted</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php $__empty_1 = true; $__currentLoopData = $quotes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $quote): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr>
              <td><?php echo e($quote->quote_id); ?></td>
              <td><?php echo e($quote->listing_type ?: '-'); ?></td>
              <td><?php echo e(is_array($quote->services) ? implode(', ', $quote->services) : '-'); ?></td>
              <td><span class="<?php echo e($quoteStatusClass($quote->status)); ?>"><?php echo e($quote->status); ?></span></td>
              <td><span class="client-portal-money"><?php echo e(number_format((int) $quote->estimated_total)); ?> <?php echo e($quote->currency); ?></span></td>
              <td><?php echo e($quote->submitted_at?->format('Y-m-d H:i') ?: '-'); ?></td>
              <td><a class="panel-btn panel-btn-primary" href="<?php echo e(route('user.quotes.show', $quote)); ?>">Open Quote</a></td>
            </tr>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr>
              <td colspan="7">
                <div class="client-portal-empty"><strong>No quotations yet</strong>Your pricing proposals and estimate revisions will appear here once they are created.</div>
              </td>
            </tr>
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
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.panel', [
  'title' => 'Quotations',
  'heading' => 'Quotations',
  'subheading' => 'Track estimate history, status changes, and open detailed quote pages.',
], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/asifk/projects/maccento/resources/views/user/quotes-index.blade.php ENDPATH**/ ?>