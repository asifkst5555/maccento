<?php $__env->startSection('content'); ?>
<div class="client-portal-shell">
  <section class="panel-card client-portal-hero">
    <div class="client-portal-hero-head">
      <div>
        <span class="client-portal-eyebrow">Client Account</span>
        <h2 class="panel-section-title" style="margin-top: 12px;"><?php echo e($client?->name ?: auth()->user()->name); ?></h2>
        <p class="client-portal-summary">Your account details below are used for project, invoice, and communication matching inside the CRM.</p>
      </div>
      <div class="client-portal-actions">
        <a class="panel-btn" href="<?php echo e(route('user.messages.index')); ?>">Contact Team</a>
        <a class="panel-btn panel-btn-primary" href="<?php echo e(route('user.invoices.index')); ?>">Open Invoices</a>
      </div>
    </div>
  </section>

  <section class="client-portal-account-grid">
    <article class="panel-card client-portal-stack">
      <h2 class="panel-section-title">Portal Identity</h2>
      <div class="client-portal-detail-grid">
        <div class="client-portal-detail">
          <span class="client-portal-detail-label">Name</span>
          <p class="client-portal-detail-value"><?php echo e(auth()->user()->name); ?></p>
        </div>
        <div class="client-portal-detail">
          <span class="client-portal-detail-label">Login Email</span>
          <p class="client-portal-detail-value"><?php echo e(auth()->user()->email); ?></p>
        </div>
        <div class="client-portal-detail">
          <span class="client-portal-detail-label">Role</span>
          <p class="client-portal-detail-value"><?php echo e(auth()->user()->role); ?></p>
        </div>
      </div>
    </article>

    <article class="panel-card client-portal-stack">
      <h2 class="panel-section-title">Client Record</h2>
      <div class="client-portal-detail-grid">
        <div class="client-portal-detail">
          <span class="client-portal-detail-label">Client Name</span>
          <p class="client-portal-detail-value"><?php echo e($client?->name ?: 'Not linked yet'); ?></p>
        </div>
        <div class="client-portal-detail">
          <span class="client-portal-detail-label">Primary Email</span>
          <p class="client-portal-detail-value"><?php echo e($client?->email ?: auth()->user()->email); ?></p>
        </div>
        <div class="client-portal-detail">
          <span class="client-portal-detail-label">Phone</span>
          <p class="client-portal-detail-value"><?php echo e($client?->phone ?: (auth()->user()->phone ?: '-')); ?></p>
        </div>
        <div class="client-portal-detail">
          <span class="client-portal-detail-label">Company</span>
          <p class="client-portal-detail-value"><?php echo e($client?->company ?: '-'); ?></p>
        </div>
        <div class="client-portal-detail">
          <span class="client-portal-detail-label">Status</span>
          <p class="client-portal-detail-value"><?php echo e($client?->status ?: 'active'); ?></p>
        </div>
        <div class="client-portal-detail">
          <span class="client-portal-detail-label">Client Record ID</span>
          <p class="client-portal-detail-value"><?php echo e($client?->id ? '#' . $client->id : '-'); ?></p>
        </div>
      </div>
    </article>
  </section>

  <section class="panel-card client-portal-stack">
    <h2 class="panel-section-title">Portal Snapshot</h2>
    <div class="panel-grid panel-grid-kpi-compact">
      <article class="client-portal-kpi">
        <span class="panel-kpi-label">Active Projects</span>
        <p class="client-portal-kpi-value"><?php echo e($portalStats['active_projects']); ?></p>
      </article>
      <article class="client-portal-kpi">
        <span class="panel-kpi-label">Unpaid Invoices</span>
        <p class="client-portal-kpi-value"><?php echo e($portalStats['unpaid_invoices']); ?></p>
      </article>
      <article class="client-portal-kpi">
        <span class="panel-kpi-label">Pending Quotes</span>
        <p class="client-portal-kpi-value"><?php echo e($portalStats['pending_quotes']); ?></p>
      </article>
    </div>
  </section>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.panel', [
  'title' => 'Account',
  'heading' => 'Account',
  'subheading' => 'Review your client profile, primary contact details, and portal access information.',
], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/asifk/projects/maccento/resources/views/user/account-index.blade.php ENDPATH**/ ?>