<?php $__env->startSection('content'); ?>
<div class="client-portal-shell">
  <?php
    $invoiceStatusClass = static function (?string $status): string {
      return 'client-status-chip status-' . \Illuminate\Support\Str::slug((string) $status);
    };
  ?>
  <section class="panel-grid panel-grid-kpi-compact">
    <article class="client-portal-kpi">
      <span class="panel-kpi-label">Unpaid Invoices</span>
      <p class="client-portal-kpi-value"><?php echo e($portalStats['unpaid_invoices']); ?></p>
    </article>
    <article class="client-portal-kpi">
      <span class="panel-kpi-label">Active Projects</span>
      <p class="client-portal-kpi-value"><?php echo e($portalStats['active_projects']); ?></p>
    </article>
    <article class="client-portal-kpi">
      <span class="panel-kpi-label">Pending Quotes</span>
      <p class="client-portal-kpi-value"><?php echo e($portalStats['pending_quotes']); ?></p>
    </article>
  </section>

  <section class="panel-card client-portal-table client-portal-card-accent">
    <div class="client-portal-section-head">
      <div class="client-portal-section-copy">
        <h2 class="panel-section-title" style="margin: 0;">Billing Records</h2>
        <p class="client-portal-subtle" style="margin: 8px 0 0;">Download invoice PDFs, review payment status, and keep your billing trail organized.</p>
      </div>
      <a class="panel-btn" href="<?php echo e(route('user.account.index')); ?>">Account Details</a>
    </div>
    <div class="panel-table-wrap">
      <table class="panel-table">
        <thead>
          <tr>
            <th>Invoice</th>
            <th>Project</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Due Date</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php $__empty_1 = true; $__currentLoopData = $invoices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $invoice): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr>
              <td><?php echo e($invoice->invoice_number); ?></td>
              <td><?php echo e($invoice->project?->title ?: 'General invoice'); ?></td>
              <td><span class="client-portal-money"><?php echo e(number_format((float) $invoice->amount, 2)); ?> <?php echo e($invoice->currency); ?></span></td>
              <td><span class="<?php echo e($invoiceStatusClass($invoice->status)); ?>"><?php echo e($invoice->status); ?></span></td>
              <td><?php echo e($invoice->due_date?->format('Y-m-d') ?: '-'); ?></td>
              <td><a class="panel-btn panel-btn-primary" href="<?php echo e(route('user.invoices.download', $invoice)); ?>">Download PDF</a></td>
            </tr>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr>
              <td colspan="6">
                <div class="client-portal-empty"><strong>No invoices yet</strong>Billing records will appear here once your projects move into invoicing.</div>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <?php if (isset($component)) { $__componentOriginal9f9d3eae18f18ccf28f34f84596c1d92 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9f9d3eae18f18ccf28f34f84596c1d92 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.panel-pagination','data' => ['paginator' => $invoices]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('panel-pagination'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['paginator' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($invoices)]); ?>
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
  'title' => 'Invoices',
  'heading' => 'Invoices',
  'subheading' => 'Review billing, payment status, and downloadable invoice PDFs.',
], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/asifk/projects/maccento/resources/views/user/invoices-index.blade.php ENDPATH**/ ?>