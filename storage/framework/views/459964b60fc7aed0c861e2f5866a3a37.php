<?php $__env->startSection('content'); ?>
<?php
  $packageCode = strtolower((string) data_get($quote->options, 'package_code', 'custom'));
  $packageTitle = (string) data_get($quote->options, 'package_title', ucfirst($packageCode ?: 'custom'));
  $displayTotal = (string) data_get($quote->options, 'display_total', '');
  $isFixedPackage = in_array($packageCode, ['essential', 'signature', 'prestige'], true);
  $canManagePipeline = in_array(strtolower((string) auth()->user()?->role), ['owner', 'admin', 'manager'], true);

  $humanizeValue = static function ($value): string {
    if (is_array($value)) {
      $items = array_values(array_filter(array_map(static function ($item): string {
        if (is_scalar($item) || $item === null) {
          return trim((string) $item);
        }

        return json_encode($item, JSON_UNESCAPED_SLASHES) ?: '';
      }, $value), static fn ($item): bool => $item !== ''));

      return count($items) > 0 ? implode(', ', $items) : '-';
    }

    if (is_bool($value)) {
      return $value ? 'yes' : 'no';
    }

    if (is_scalar($value) || $value === null) {
      $text = trim((string) $value);
      return $text !== '' ? $text : '-';
    }

    return json_encode($value, JSON_UNESCAPED_SLASHES) ?: '-';
  };

  $humanizePayload = static function ($payload) use ($humanizeValue): array {
    if (!is_array($payload) || count($payload) === 0) {
      return [];
    }

    $lines = [];
    foreach ($payload as $key => $value) {
      $label = \Illuminate\Support\Str::headline((string) $key);
      $lines[] = $label . ': ' . $humanizeValue($value);
    }

    return $lines;
  };
?>
<section class="panel-grid">
  <article class="panel-card panel-stack">
    <h2 class="panel-section-title">Quote Details</h2>
    <p><strong>Package:</strong> <?php echo e($packageTitle); ?></p>
    <p><strong>Status:</strong> <span class="panel-badge"><?php echo e($quote->status); ?></span></p>
    <p><strong>Listing:</strong> <?php echo e($quote->listing_type ?: '-'); ?></p>
    <p><strong>Services:</strong> <?php echo e(is_array($quote->services) ? implode(', ', $quote->services) : '-'); ?></p>
    <p><strong>Total:</strong>
      <?php if($displayTotal !== ''): ?>
        <?php echo e($displayTotal); ?> <?php echo e($quote->currency); ?>

      <?php else: ?>
        <?php echo e(number_format((int) $quote->estimated_total)); ?> <?php echo e($quote->currency); ?>

      <?php endif; ?>
    </p>
    <p><strong>Internal note:</strong> <?php echo e($quote->notes ?: '-'); ?></p>
  </article>

  <article class="panel-card">
    <h2 class="panel-section-title">Line Items</h2>
    <div class="panel-table-wrap">
      <table class="panel-table">
        <thead><tr><th>Label</th><th>Amount</th></tr></thead>
        <tbody>
          <?php $__empty_1 = true; $__currentLoopData = ($quote->line_items ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
          <tr>
            <td><?php echo e($item['label'] ?? '-'); ?></td>
            <td><?php echo e(number_format((int) ($item['amount'] ?? 0))); ?> <?php echo e($quote->currency); ?></td>
          </tr>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
          <tr><td colspan="2" class="panel-muted">No line items.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </article>

  <article class="panel-card panel-stack">
    <h2 class="panel-section-title">Client Details</h2>
    <p><strong>Name:</strong> <?php echo e(data_get($quote->options, 'contact_name', $quote->leadProfile?->name ?: '-')); ?></p>
    <p><strong>Email:</strong> <?php echo e(data_get($quote->options, 'contact_email', $quote->leadProfile?->email ?: '-')); ?></p>
    <p><strong>Phone:</strong> <?php echo e(data_get($quote->options, 'contact_phone', $quote->leadProfile?->phone ?: '-')); ?></p>
  </article>

  <article class="panel-card">
    <h2 class="panel-section-title">Quote Actions</h2>
    <?php if($canManagePipeline): ?>
    <form method="post" action="<?php echo e(route('admin.quotes.status', $quote)); ?>" class="panel-stack">
      <?php echo csrf_field(); ?>
      <select class="panel-select" name="status" required>
        <?php $__currentLoopData = ['new','reviewed','contacted','booked','lost']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <option value="<?php echo e($status); ?>" <?php if($quote->status === $status): echo 'selected'; endif; ?>><?php echo e(ucfirst($status)); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </select>
      <textarea class="panel-textarea" name="note" placeholder="Optional note"></textarea>
      <button class="panel-btn panel-btn-primary" type="submit">Save status</button>
    </form>
    <hr class="panel-hr">
    <form method="post" action="<?php echo e(route('admin.quotes.resend-email', $quote)); ?>">
      <?php echo csrf_field(); ?>
      <button class="panel-btn" type="submit">Resend email</button>
    </form>
    <hr class="panel-hr">
    <form method="post" action="<?php echo e(route('admin.quotes.delete', $quote)); ?>" data-confirm="Delete this quote? This action cannot be undone.">
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
    <?php else: ?>
    <p class="panel-muted">Read only access. Quote update actions are available for owner/admin/manager roles.</p>
    <?php endif; ?>
  </article>

  <article class="panel-card panel-stack">
    <h2 class="panel-section-title">Manual Quote Editor</h2>
    <?php if(!$canManagePipeline): ?>
    <p class="panel-muted">Read only access. Manual editing is disabled for this role.</p>
    <?php elseif($isFixedPackage): ?>
    <p class="panel-muted">This is a fixed preset package (<?php echo e(ucfirst($packageCode)); ?>). Line items are locked to keep exact package pricing/features.</p>
    <?php endif; ?>
    <?php if($canManagePipeline): ?>
    <form method="post" action="<?php echo e(route('admin.quotes.line-items', $quote)); ?>" class="panel-stack" data-line-item-editor>
      <?php echo csrf_field(); ?>
      <div class="panel-form-row">
        <input class="panel-input" type="text" name="currency" maxlength="8" value="<?php echo e(old('currency', $quote->currency)); ?>" placeholder="Currency (USD)" <?php if($isFixedPackage): echo 'disabled'; endif; ?>>
        <textarea class="panel-textarea" name="notes" placeholder="Internal note" <?php if($isFixedPackage): echo 'disabled'; endif; ?>><?php echo e(old('notes', $quote->notes)); ?></textarea>
      </div>

      <div class="panel-table-wrap">
        <table class="panel-table">
          <thead><tr><th>Label</th><th>Amount</th><th>Action</th></tr></thead>
          <tbody data-line-item-body>
            <?php ($oldItems = old('line_items', $quote->line_items ?? [])); ?>
            <?php $__currentLoopData = $oldItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
              <td><input class="panel-input" type="text" name="line_items[<?php echo e($index); ?>][label]" value="<?php echo e($item['label'] ?? ''); ?>" maxlength="150" required <?php if($isFixedPackage): echo 'disabled'; endif; ?>></td>
              <td><input class="panel-input" type="number" name="line_items[<?php echo e($index); ?>][amount]" value="<?php echo e((int) ($item['amount'] ?? 0)); ?>" min="0" required <?php if($isFixedPackage): echo 'disabled'; endif; ?>></td>
              <td><button class="panel-btn" type="button" data-remove-line-item <?php if($isFixedPackage): echo 'disabled'; endif; ?>>Remove</button></td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </tbody>
        </table>
      </div>

      <div class="panel-form-row">
        <button class="panel-btn" type="button" data-add-line-item <?php if($isFixedPackage): echo 'disabled'; endif; ?>>Add line item</button>
        <button class="panel-btn panel-btn-primary" type="submit" <?php if($isFixedPackage): echo 'disabled'; endif; ?>>Save line items</button>
      </div>
    </form>
    <?php endif; ?>
  </article>

  <article class="panel-card">
    <h2 class="panel-section-title">Timeline</h2>
    <div class="panel-table-wrap">
      <table class="panel-table">
        <thead><tr><th>Time</th><th>Event</th><th>By</th><th>Payload</th></tr></thead>
        <tbody>
          <?php $__empty_1 = true; $__currentLoopData = $quote->events; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $event): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
          <tr>
            <td><?php echo e($event->created_at?->format('Y-m-d H:i')); ?></td>
            <td><?php echo e($event->event_type); ?></td>
            <td><?php echo e($event->creator?->email ?: 'system'); ?></td>
            <td>
              <?php ($payloadLines = $humanizePayload($event->payload)); ?>
              <?php if(count($payloadLines) > 0): ?>
                <?php $__currentLoopData = $payloadLines; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $line): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="panel-muted" style="margin:0 0 4px;"><?php echo e($line); ?></div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
              <?php else: ?>
              -
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
          <tr><td colspan="4" class="panel-muted">No events.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </article>
</section>

<script>
  (function () {
    const form = document.querySelector('[data-line-item-editor]');
    if (!form) return;

    const body = form.querySelector('[data-line-item-body]');
    const addBtn = form.querySelector('[data-add-line-item]');
    if (!body || !addBtn) return;

    const nextIndex = () => body.querySelectorAll('tr').length;

    const makeRow = (index) => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td><input class="panel-input" type="text" name="line_items[${index}][label]" maxlength="150" required></td>
        <td><input class="panel-input" type="number" name="line_items[${index}][amount]" value="0" min="0" required></td>
        <td><button class="panel-btn" type="button" data-remove-line-item>Remove</button></td>
      `;
      return tr;
    };

    addBtn.addEventListener('click', function () {
      body.appendChild(makeRow(nextIndex()));
    });

    body.addEventListener('click', function (event) {
      const button = event.target.closest('[data-remove-line-item]');
      if (!button) return;
      const row = button.closest('tr');
      if (!row) return;
      if (body.querySelectorAll('tr').length <= 1) return;
      row.remove();
    });

    if (body.querySelectorAll('tr').length === 0) {
      body.appendChild(makeRow(0));
    }
  })();
</script>
<?php $__env->stopSection(); ?>



<?php echo $__env->make('layouts.panel', [
  'title' => 'Quote ' . $quote->quote_id,
  'heading' => 'Quote ' . $quote->quote_id,
  'subheading' => 'Submitted ' . ($quote->submitted_at?->format('Y-m-d H:i') ?: '-'),
], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/asifk/projects/maccento/resources/views/admin/quote-show.blade.php ENDPATH**/ ?>