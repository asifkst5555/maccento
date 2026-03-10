<?php $__env->startSection('content'); ?>
<style>
  .corp-admin-shell {
    --corp-ink: #10233a;
    --corp-ink-soft: #586b83;
    --corp-line: #d6e0ec;
    --corp-surface: #ffffff;
    --corp-soft: #f3f7fc;
    --corp-accent: #c11f37;
    --corp-shadow: 0 14px 30px rgba(16, 35, 58, 0.08);
  }

  .corp-admin-shell .panel-card {
    border: 1px solid var(--corp-line);
    border-radius: 14px;
    background: var(--corp-surface);
    box-shadow: var(--corp-shadow);
  }

  .corp-admin-shell .panel-kpi-label {
    color: var(--corp-ink-soft);
    font-weight: 700;
    letter-spacing: 0.03em;
    text-transform: uppercase;
    font-size: 0.72rem;
  }

  .corp-admin-shell .panel-kpi-value {
    color: var(--corp-ink);
  }

  .corp-admin-shell .panel-muted {
    color: var(--corp-ink-soft);
  }

  .corp-admin-shell .panel-link {
    color: #143557;
    font-weight: 600;
    text-decoration: none;
  }

  .corp-admin-shell .panel-link:hover {
    color: var(--corp-accent);
  }

  .corp-admin-shell .panel-input,
  .corp-admin-shell .panel-select,
  .corp-admin-shell .panel-textarea {
    border-radius: 10px;
    border: 1px solid #c9d6e5;
    background-color: #fff;
  }

  .corp-admin-shell .panel-btn {
    border-radius: 10px;
    border: 1px solid #bfcfe0;
    font-weight: 600;
  }

  .corp-admin-shell .panel-btn-primary {
    background: linear-gradient(90deg, #b71d34 0%, #cc2741 100%);
    border-color: #a5172d;
  }

  .corp-admin-shell .panel-sticky-filters {
    background: linear-gradient(180deg, #f8fbff 0%, #eef4fb 100%);
    border: 1px solid #d9e3ef;
    border-radius: 12px;
    padding: 0.7rem;
  }

  .corp-admin-shell .panel-table-wrap {
    border: 1px solid var(--corp-line);
    border-radius: 12px;
    overflow: hidden;
    background: #fff;
  }

  .corp-admin-shell .panel-table thead th {
    background: var(--corp-soft);
    color: #324963;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    font-size: 0.74rem;
  }

  .corp-admin-shell .panel-table tbody tr:nth-child(even) {
    background: #fbfdff;
  }

  .corp-admin-shell .panel-badge {
    border-radius: 999px;
    border: 1px solid #c5d3e3;
    background: #eff5fc;
    color: #203b59;
    font-weight: 700;
    font-size: 0.7rem;
    letter-spacing: 0.02em;
  }

  .corp-admin-shell .panel-row-overdue {
    background: #fff7f8;
  }

  .corp-admin-shell .row-between-center {
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
  }

  .corp-admin-shell .tax-settings-form {
    margin-bottom: 12px;
    align-items: end;
  }

  .corp-admin-shell .inline-checkbox-label {
    display: flex;
    align-items: center;
    gap: 6px;
  }

  .corp-admin-shell .stacked-label {
    display: flex;
    flex-direction: column;
    gap: 4px;
  }

  .corp-admin-shell .tax-input {
    max-width: 140px;
  }

  .corp-admin-shell .status-form-tight {
    margin-bottom: 6px;
  }

  .corp-admin-shell .inline-delete-form {
    display: inline-block;
    margin-left: 8px;
  }

  @media (max-width: 1024px) {
    .corp-admin-shell .panel-sticky-filters {
      position: static;
    }
  }
</style>
<div class="corp-admin-shell">
<section class="panel-grid panel-grid-kpi">
  <article class="panel-card">
    <span class="panel-kpi-label">Total invoices</span>
    <p class="panel-kpi-value"><?php echo e($kpi['total_invoices']); ?></p>
  </article>
  <article class="panel-card">
    <span class="panel-kpi-label">Paid invoices</span>
    <p class="panel-kpi-value"><?php echo e($kpi['paid_invoices']); ?></p>
  </article>
  <article class="panel-card">
    <span class="panel-kpi-label">Unpaid invoices</span>
    <p class="panel-kpi-value"><?php echo e($kpi['unpaid_invoices']); ?></p>
  </article>
  <article class="panel-card">
    <span class="panel-kpi-label">Overdue invoices</span>
    <p class="panel-kpi-value"><?php echo e($kpi['overdue_invoices']); ?></p>
  </article>
  <article class="panel-card">
    <span class="panel-kpi-label">Total amount</span>
    <p class="panel-kpi-value"><?php echo e(number_format($kpi['total_amount'], 2)); ?></p>
  </article>
  <article class="panel-card">
    <span class="panel-kpi-label">Paid amount</span>
    <p class="panel-kpi-value"><?php echo e(number_format($kpi['paid_amount'], 2)); ?></p>
  </article>
  <article class="panel-card">
    <span class="panel-kpi-label">Unpaid amount</span>
    <p class="panel-kpi-value"><?php echo e(number_format($kpi['unpaid_amount'], 2)); ?></p>
  </article>
</section>

<section class="panel-card">
  <?php
    $canManageInvoiceSettings = in_array(strtolower(trim((string) auth()->user()?->role)), ['owner', 'admin'], true);
  ?>
  <?php if($canManageInvoiceSettings): ?>
  <div class="panel-form-row row-between-center">
    <strong>Invoice PDF Tax Settings</strong>
    <span class="panel-muted">Admin-controlled</span>
  </div>
  <form method="post" action="<?php echo e(route('admin.invoices.settings.update')); ?>" class="panel-form-row tax-settings-form">
    <?php echo csrf_field(); ?>
    <label class="panel-muted inline-checkbox-label">
      <input type="hidden" name="include_tax_on_pdf" value="0">
      <input type="checkbox" name="include_tax_on_pdf" value="1" <?php if((bool) ($invoiceSettings->include_tax_on_pdf ?? false)): echo 'checked'; endif; ?>>
      Include tax on admin invoice PDF
    </label>
    <label class="panel-muted stacked-label">
      Tax %
      <input class="panel-input tax-input" type="number" step="0.01" min="0" max="100" name="tax_rate_percent" value="<?php echo e(number_format((float) ($invoiceSettings->tax_rate_percent ?? 0), 2, '.', '')); ?>">
    </label>
    <button class="panel-btn panel-btn-primary" type="submit">Save Tax Settings</button>
  </form>
  <?php endif; ?>

  <?php if(!empty($filters['invoice_project'])): ?>
  <div class="panel-form-row row-between-center">
    <span class="panel-badge">Project Filter: <?php echo e($filters['invoice_project_title'] ?: ('Project #' . $filters['invoice_project'])); ?></span>
    <a class="panel-link" href="<?php echo e(route('admin.invoices.index')); ?>">Clear Project Filter</a>
  </div>
  <?php endif; ?>

  <div class="panel-sticky-filters">
    <form method="get" class="panel-form-row">
      <?php if(!empty($filters['invoice_project'])): ?>
      <input type="hidden" name="invoice_project" value="<?php echo e($filters['invoice_project']); ?>">
      <?php endif; ?>
      <input class="panel-input" type="text" name="invoice_search" placeholder="Search invoice/client/project" value="<?php echo e($filters['invoice_search']); ?>">
      <select class="panel-select" name="invoice_status">
        <option value="">All invoices</option>
        <option value="paid" <?php if($filters['invoice_status'] === 'paid'): echo 'selected'; endif; ?>>Paid</option>
        <option value="unpaid" <?php if($filters['invoice_status'] === 'unpaid'): echo 'selected'; endif; ?>>Unpaid</option>
        <option value="draft" <?php if($filters['invoice_status'] === 'draft'): echo 'selected'; endif; ?>>Draft</option>
        <option value="sent" <?php if($filters['invoice_status'] === 'sent'): echo 'selected'; endif; ?>>Sent</option>
        <option value="partial" <?php if($filters['invoice_status'] === 'partial'): echo 'selected'; endif; ?>>Partial</option>
        <option value="overdue" <?php if($filters['invoice_status'] === 'overdue'): echo 'selected'; endif; ?>>Overdue</option>
      </select>
      <button class="panel-btn panel-btn-primary" type="submit">Filter</button>
      <a class="panel-link" href="<?php echo e(route('admin.invoices.index')); ?>">Clear</a>
    </form>
  </div>

  <div class="panel-table-wrap">
    <table class="panel-table">
      <thead>
        <tr>
          <th>Invoice</th>
          <th>Client</th>
          <th>Project</th>
          <th>Amount</th>
          <th>Status</th>
          <th>Issued</th>
          <th>Due</th>
          <th>Paid At</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $invoices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $invoice): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <?php
          $isOverdue = $invoice->status !== 'paid' && $invoice->due_date && $invoice->due_date->isPast();
        ?>
        <tr class="<?php echo e($isOverdue ? 'panel-row-overdue' : ''); ?>">
          <td><?php echo e($invoice->invoice_number); ?></td>
          <td>
            <?php echo e($invoice->client?->name ?: '-'); ?><br>
            <span class="panel-muted"><?php echo e($invoice->client?->email ?: ($invoice->client?->phone ?: '-')); ?></span>
          </td>
          <td><?php echo e($invoice->project?->title ?: '-'); ?></td>
          <td><?php echo e(number_format((float) $invoice->amount, 2)); ?> <?php echo e(strtoupper((string) $invoice->currency)); ?></td>
          <td><span class="panel-badge"><?php echo e($invoice->status); ?></span></td>
          <td><?php echo e($invoice->issued_at?->format('Y-m-d') ?: '-'); ?></td>
          <td><?php echo e($invoice->due_date?->format('Y-m-d') ?: '-'); ?></td>
          <td><?php echo e($invoice->paid_at?->format('Y-m-d H:i') ?: '-'); ?></td>
          <td>
            <form method="post" action="<?php echo e(route('admin.invoices.status', $invoice)); ?>" class="panel-form-row status-form-tight">
              <?php echo csrf_field(); ?>
              <select class="panel-select" name="status">
                <?php $__currentLoopData = ['draft','sent','partial','paid','overdue']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($status); ?>" <?php if($invoice->status === $status): echo 'selected'; endif; ?>><?php echo e(ucfirst($status)); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
              </select>
              <button class="panel-btn" type="submit">Save</button>
            </form>
            <?php if($invoice->client): ?>
            <a class="panel-link" href="<?php echo e(route('admin.clients.show', $invoice->client)); ?>">Open client</a>
            <?php endif; ?>
            <br>
            <a class="panel-link" href="<?php echo e(route('admin.invoices.download', $invoice)); ?>">Download PDF</a>
            <form method="post" action="<?php echo e(route('admin.invoices.delete', $invoice)); ?>" data-app-confirm="1" data-confirm-message="Delete invoice <?php echo e($invoice->invoice_number); ?>?" class="inline-delete-form">
              <?php echo csrf_field(); ?>
              <button class="panel-btn panel-btn-danger panel-btn-icon" type="submit" title="Delete invoice" aria-label="Delete invoice"><span class="panel-icon-trash" aria-hidden="true"><?php if (isset($component)) { $__componentOriginal669c17867d57615948fae15a035429b3 = $component; } ?>
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
        <tr><td colspan="9" class="panel-muted">No invoices found.</td></tr>
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
  'subheading' => 'Track all client invoices with paid and unpaid visibility.',
], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/asifk/projects/maccento/resources/views/admin/invoices-index.blade.php ENDPATH**/ ?>