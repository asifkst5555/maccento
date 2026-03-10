<?php $__env->startSection('content'); ?>
<?php
  $quoteStatusClass = 'client-status-chip status-' . \Illuminate\Support\Str::slug((string) $quote->status);
?>
<div class="client-portal-shell">
  <section class="panel-card client-portal-hero">
    <div class="client-portal-hero-head">
      <div>
        <span class="client-portal-eyebrow">Quotation Detail</span>
        <h2 class="panel-section-title" style="margin-top: 12px;"><?php echo e($quote->quote_id); ?></h2>
        <p class="client-portal-summary">
          <?php echo e($quote->listing_type ?: 'Listing type pending'); ?>

          &bull; <?php echo e(is_array($quote->services) ? implode(', ', $quote->services) : 'Services pending'); ?>

        </p>
      </div>
      <div class="client-portal-actions">
        <span class="<?php echo e($quoteStatusClass); ?>"><?php echo e($quote->status); ?></span>
        <a class="panel-btn" href="<?php echo e(route('user.quotes.index')); ?>">Back to Quotes</a>
      </div>
    </div>
  </section>

  <section class="panel-grid panel-grid-kpi-compact">
    <article class="client-portal-kpi">
      <span class="panel-kpi-label">Estimated Total</span>
      <p class="client-portal-kpi-value"><?php echo e(number_format((int) $quote->estimated_total)); ?></p>
      <p class="client-portal-kpi-note"><?php echo e($quote->currency); ?></p>
    </article>
    <article class="client-portal-kpi">
      <span class="panel-kpi-label">Submitted</span>
      <p class="client-portal-kpi-value" style="font-size: 20px;"><?php echo e($quote->submitted_at?->format('Y-m-d') ?: '-'); ?></p>
      <p class="client-portal-kpi-note"><?php echo e($quote->submitted_at?->format('H:i') ?: ''); ?></p>
    </article>
    <article class="client-portal-kpi">
      <span class="panel-kpi-label">Line Items</span>
      <p class="client-portal-kpi-value"><?php echo e(count($quote->line_items ?? [])); ?></p>
      <p class="client-portal-kpi-note">Pricing components in this quotation.</p>
    </article>
  </section>

  <section class="client-portal-grid-two">
    <article class="panel-card client-portal-stack client-portal-card-accent">
      <div class="client-portal-section-head">
        <div class="client-portal-section-copy">
          <h2 class="panel-section-title" style="margin: 0;">Quote Summary</h2>
          <p class="client-portal-subtle" style="margin: 8px 0 0;">A concise view of your quote scope, estimate, and submission details.</p>
        </div>
      </div>
      <div class="client-portal-detail-grid">
        <div class="client-portal-detail">
          <span class="client-portal-detail-label">Listing Type</span>
          <p class="client-portal-detail-value"><?php echo e($quote->listing_type ?: '-'); ?></p>
        </div>
        <div class="client-portal-detail">
          <span class="client-portal-detail-label">Status</span>
          <p class="client-portal-detail-value"><?php echo e($quote->status); ?></p>
        </div>
        <div class="client-portal-detail">
          <span class="client-portal-detail-label">Currency</span>
          <p class="client-portal-detail-value"><?php echo e($quote->currency); ?></p>
        </div>
      </div>
      <div class="client-portal-empty">
        <strong>Services Included</strong>
        <?php echo e(is_array($quote->services) && count($quote->services) > 0 ? implode(', ', $quote->services) : 'No services listed.'); ?>

      </div>
    </article>

    <article class="panel-card client-portal-stack">
      <div class="client-portal-section-head">
        <div class="client-portal-section-copy">
          <h2 class="panel-section-title" style="margin: 0;">Request Quote Revision</h2>
          <p class="client-portal-subtle" style="margin: 8px 0 0;">Need changes in scope, pricing assumptions, or deliverables? Send the admin team a structured revision request.</p>
        </div>
      </div>
      <form method="post" action="<?php echo e(route('user.quotes.revision-request', $quote)); ?>" class="panel-stack">
        <?php echo csrf_field(); ?>
        <textarea class="panel-textarea" name="revision_note" maxlength="1000" required placeholder="Example: Please update this quote to include drone video and 31-45 photos."><?php echo e(old('revision_note')); ?></textarea>
        <select class="panel-select" name="preferred_contact">
          <option value="">Preferred contact method (optional)</option>
          <option value="email" <?php if(old('preferred_contact') === 'email'): echo 'selected'; endif; ?>>Email</option>
          <option value="phone" <?php if(old('preferred_contact') === 'phone'): echo 'selected'; endif; ?>>Phone</option>
          <option value="call" <?php if(old('preferred_contact') === 'call'): echo 'selected'; endif; ?>>Call</option>
        </select>
        <button class="panel-btn panel-btn-primary" type="submit">Send Revision Request</button>
      </form>
    </article>
  </section>

  <section class="client-portal-grid-two">
    <article class="panel-card client-portal-table">
      <h2 class="panel-section-title">Line Items</h2>
      <div class="panel-table-wrap">
        <table class="panel-table">
          <thead>
            <tr>
              <th>Item</th>
              <th>Amount</th>
            </tr>
          </thead>
          <tbody>
            <?php $__empty_1 = true; $__currentLoopData = ($quote->line_items ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
              <tr>
                <td><?php echo e($item['label'] ?? '-'); ?></td>
                <td><span class="client-portal-money"><?php echo e(number_format((int) ($item['amount'] ?? 0))); ?> <?php echo e($quote->currency); ?></span></td>
              </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
              <tr>
                <td colspan="2">
                  <div class="client-portal-empty"><strong>No line items</strong>This quote does not have a detailed line-item breakdown yet.</div>
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </article>

    <article class="panel-card client-portal-table">
      <h2 class="panel-section-title">Timeline</h2>
      <div class="panel-table-wrap">
        <table class="panel-table">
          <thead>
            <tr>
              <th>Time</th>
              <th>Event</th>
              <th>Details</th>
            </tr>
          </thead>
          <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $quote->events; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $event): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
              <tr>
                <td><?php echo e($event->created_at?->format('Y-m-d H:i') ?: '-'); ?></td>
                <td><?php echo e($event->event_type); ?></td>
                <td><?php echo e($event->payload ? json_encode($event->payload) : '-'); ?></td>
              </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
              <tr>
                <td colspan="3">
                  <div class="client-portal-empty"><strong>No quote timeline yet</strong>Status changes and revision activity will appear here.</div>
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </article>
  </section>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.panel', [
  'title' => 'Quote ' . $quote->quote_id,
  'heading' => 'Quote ' . $quote->quote_id,
  'subheading' => 'Detailed quotation record with pricing, scope, and revision workflow.',
], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/asifk/projects/maccento/resources/views/user/quote-show.blade.php ENDPATH**/ ?>