<?php $__env->startSection('content'); ?>
<?php
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

  $humanizeTranscriptMessage = static function (?string $content) use ($humanizePayload): string {
    $text = trim((string) $content);
    if ($text === '') {
      return '-';
    }

    $decoded = json_decode($text, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
      $lines = $humanizePayload($decoded);
      return count($lines) > 0 ? implode("\n", $lines) : $text;
    }

    return $text;
  };
?>
<section class="panel-two-col">
  <div class="panel-main-col">
    <article class="panel-card">
      <h2 class="panel-section-title">Lead Details</h2>
      <div class="panel-kv-grid">
        <div class="panel-kv-item"><span class="panel-kv-label">Status</span><span class="panel-kv-value"><span class="panel-badge"><?php echo e($lead->status); ?></span></span></div>
        <div class="panel-kv-item"><span class="panel-kv-label">Score</span><span class="panel-kv-value"><?php echo e($lead->score); ?></span></div>
        <div class="panel-kv-item"><span class="panel-kv-label">Service</span><span class="panel-kv-value"><?php echo e($lead->service_type ?: '-'); ?></span></div>
        <div class="panel-kv-item"><span class="panel-kv-label">Property</span><span class="panel-kv-value"><?php echo e($lead->property_type ?: '-'); ?></span></div>
        <div class="panel-kv-item"><span class="panel-kv-label">Location</span><span class="panel-kv-value"><?php echo e($lead->location ?: '-'); ?></span></div>
        <div class="panel-kv-item"><span class="panel-kv-label">Preferred Contact</span><span class="panel-kv-value"><?php echo e($lead->preferred_contact ?: '-'); ?></span></div>
      </div>
    </article>

    <article class="panel-card">
      <h2 class="panel-section-title">Conversation Transcript</h2>
      <?php
        $allMessages = ($lead->conversation?->messages ?? collect())->values();
        $previewCount = 3;
        $previewMessages = $allMessages->count() > $previewCount ? $allMessages->slice(-$previewCount)->values() : $allMessages;
      ?>
      <div class="panel-chat-list">
        <?php $__empty_1 = true; $__currentLoopData = $previewMessages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $message): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <div class="panel-chat-item <?php echo e($message->role === 'user' ? 'is-user' : 'is-assistant'); ?>">
          <p class="panel-chat-role"><?php echo e(strtoupper($message->role)); ?></p>
          <p class="panel-chat-text"><?php echo nl2br(e($humanizeTranscriptMessage((string) $message->content))); ?></p>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <p class="panel-muted">No messages.</p>
        <?php endif; ?>
      </div>
      <?php if($allMessages->isNotEmpty()): ?>
      <div class="panel-form-row panel-transcript-actions">
        <?php if($allMessages->count() > $previewCount): ?>
        <button type="button" class="panel-btn panel-btn-danger" data-transcript-open>See full conversation (<?php echo e($allMessages->count()); ?>)</button>
        <?php endif; ?>
        <a href="<?php echo e(route('admin.leads.conversation-pdf', $lead)); ?>" class="panel-btn panel-btn-danger panel-btn-export">Export Chat PDF</a>
      </div>
      <?php endif; ?>
    </article>

    <article class="panel-card">
      <h2 class="panel-section-title">Lead Timeline</h2>
      <div class="panel-table-wrap">
        <table class="panel-table">
          <thead><tr><th>Time</th><th>Event</th><th>By</th><th>Payload</th></tr></thead>
          <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $lead->events; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $event): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
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
            <tr><td colspan="4" class="panel-muted">No events yet.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </article>
  </div>

  <aside class="panel-side-col">
    <div class="panel-side-sticky">
      <article class="panel-card">
        <h2 class="panel-section-title">Update Status</h2>
        <?php if($canManagePipeline): ?>
        <form method="post" action="<?php echo e(route('admin.leads.status', $lead)); ?>" class="panel-stack">
          <?php echo csrf_field(); ?>
          <select class="panel-select" name="status" required>
            <?php $__currentLoopData = ['new','qualified','contacted','won','lost','nurturing']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($status); ?>" <?php if($lead->status === $status): echo 'selected'; endif; ?>><?php echo e(ucfirst($status)); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </select>
          <textarea class="panel-textarea" name="note" placeholder="Optional note"></textarea>
          <button class="panel-btn panel-btn-primary" type="submit">Save status</button>
        </form>
        <?php else: ?>
        <p class="panel-muted">Read only access. Status updates are available for owner/admin/manager roles.</p>
        <?php endif; ?>
      </article>

      <article class="panel-card">
        <h2 class="panel-section-title">Schedule Follow-up</h2>
        <?php if($canManagePipeline): ?>
        <form method="post" action="<?php echo e(route('admin.leads.follow-up', $lead)); ?>" class="panel-stack">
          <?php echo csrf_field(); ?>
          <select class="panel-select" name="method" required>
            <option value="call">Call</option>
            <option value="email">Email</option>
            <option value="sms">SMS</option>
          </select>
          <input class="panel-input" type="datetime-local" name="due_at" required>
          <textarea class="panel-textarea" name="notes" placeholder="Notes"></textarea>
          <button class="panel-btn panel-btn-primary" type="submit">Add follow-up</button>
        </form>
        <?php else: ?>
        <p class="panel-muted">Read only access. Follow-up creation is disabled for this role.</p>
        <?php endif; ?>
      </article>

      <?php if($canManagePipeline): ?>
      <article class="panel-card">
        <h2 class="panel-section-title">Danger Zone</h2>
        <form method="post" action="<?php echo e(route('admin.leads.delete', $lead)); ?>" data-confirm="Delete this lead permanently?">
          <?php echo csrf_field(); ?>
          <button class="panel-btn panel-btn-danger panel-btn-icon" type="submit" title="Delete lead" aria-label="Delete lead"><span class="panel-icon-trash" aria-hidden="true"><?php if (isset($component)) { $__componentOriginal669c17867d57615948fae15a035429b3 = $component; } ?>
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
      </article>
      <?php endif; ?>

      <article class="panel-card">
        <h2 class="panel-section-title">Follow-up Queue</h2>
        <div class="panel-table-wrap">
          <table class="panel-table">
            <thead><tr><th>Due</th><th>Method</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
              <?php $__empty_1 = true; $__currentLoopData = $lead->followUps; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $followUp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
              <tr>
                <td><?php echo e($followUp->due_at?->format('Y-m-d H:i') ?: '-'); ?></td>
                <td><?php echo e(strtoupper($followUp->method)); ?></td>
                <td><span class="panel-badge"><?php echo e($followUp->status); ?></span></td>
                <td>
                  <?php if($canManagePipeline): ?>
                  <form method="post" action="<?php echo e(route('admin.follow-ups.status', $followUp)); ?>" class="panel-form-row">
                    <?php echo csrf_field(); ?>
                    <select class="panel-select" name="status">
                      <?php $__currentLoopData = ['pending','completed','cancelled']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                      <option value="<?php echo e($status); ?>" <?php if($followUp->status === $status): echo 'selected'; endif; ?>><?php echo e(ucfirst($status)); ?></option>
                      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <button class="panel-btn" type="submit">Update</button>
                  </form>
                  <?php else: ?>
                  <span class="panel-badge">Read only</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
              <tr><td colspan="4" class="panel-muted">No follow-ups yet.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </article>
    </div>
  </aside>
</section>

<?php if(($lead->conversation?->messages ?? collect())->count() > 3): ?>
<div class="panel-modal" data-transcript-modal hidden>
  <div class="panel-modal-backdrop" data-transcript-close></div>
  <div class="panel-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="transcript-modal-title">
    <div class="panel-modal-head">
      <h3 id="transcript-modal-title" class="panel-modal-title">Full Conversation Transcript</h3>
      <button type="button" class="panel-modal-close" data-transcript-close aria-label="Close transcript">&times;</button>
    </div>
    <div class="panel-modal-body">
      <div class="panel-chat-list panel-chat-list-full">
        <?php $__currentLoopData = ($lead->conversation?->messages ?? collect()); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $message): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="panel-chat-item <?php echo e($message->role === 'user' ? 'is-user' : 'is-assistant'); ?>">
          <p class="panel-chat-role"><?php echo e(strtoupper($message->role)); ?></p>
          <p class="panel-chat-text"><?php echo nl2br(e($humanizeTranscriptMessage((string) $message->content))); ?></p>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </div>
    </div>
    <div class="panel-modal-foot">
      <button type="button" class="panel-btn panel-btn-primary" data-transcript-close>Close</button>
    </div>
  </div>
</div>

<script>
  (function () {
    const modal = document.querySelector('[data-transcript-modal]');
    const openBtn = document.querySelector('[data-transcript-open]');
    if (!modal || !openBtn) return;

    const closeButtons = modal.querySelectorAll('[data-transcript-close]');
    const open = function () {
      modal.hidden = false;
      document.body.classList.add('panel-modal-open');
    };
    const close = function () {
      modal.hidden = true;
      document.body.classList.remove('panel-modal-open');
    };

    openBtn.addEventListener('click', open);
    closeButtons.forEach(function (button) {
      button.addEventListener('click', close);
    });
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && !modal.hidden) {
        close();
      }
    });
  })();
</script>
<?php endif; ?>
<?php $__env->stopSection(); ?>



<?php echo $__env->make('layouts.panel', [
  'title' => 'Lead #' . $lead->id,
  'heading' => 'Lead #' . $lead->id,
  'subheading' => ($lead->name ?: 'Unnamed lead') . ' - ' . ($lead->email ?: ($lead->phone ?: 'No contact')),
], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/asifk/projects/maccento/resources/views/admin/lead-show.blade.php ENDPATH**/ ?>