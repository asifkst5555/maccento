<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
    'modalId' => 'panel-delete-confirm-modal',
    'triggerSelector' => '[data-delete-trigger]',
    'title' => 'Confirm Deletion',
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
    'modalId' => 'panel-delete-confirm-modal',
    'triggerSelector' => '[data-delete-trigger]',
    'title' => 'Confirm Deletion',
]); ?>
<?php foreach (array_filter(([
    'modalId' => 'panel-delete-confirm-modal',
    'triggerSelector' => '[data-delete-trigger]',
    'title' => 'Confirm Deletion',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<?php
    $nameId = $modalId . '-name';
    $confirmId = $modalId . '-confirm';
?>

<div id="<?php echo e($modalId); ?>" class="panel-modal" hidden>
  <div class="panel-modal-backdrop" data-delete-close></div>
  <div class="panel-modal-dialog" style="max-width: 560px;">
    <div class="panel-modal-head">
      <h3 class="panel-modal-title"><?php echo e($title); ?></h3>
      <button class="panel-modal-close" type="button" data-delete-close aria-label="Close delete confirmation">ÃƒÆ’Ã¢â‚¬â€</button>
    </div>

    <div class="panel-modal-body">
      <p class="panel-muted" style="margin:0 0 8px;">You are about to permanently delete this file:</p>
      <p id="<?php echo e($nameId); ?>" style="margin:0; font-weight:600; color:#10223e; word-break:break-word;">-</p>
      <p class="panel-muted" style="margin:10px 0 0;">This action cannot be undone.</p>
    </div>

    <div class="panel-modal-foot" style="gap:10px;">
      <button class="panel-btn" type="button" data-delete-close>Cancel</button>
      <button class="panel-btn panel-btn-danger panel-btn-icon" type="button" id="<?php echo e($confirmId); ?>" title="Confirm delete" aria-label="Confirm delete"><span class="panel-icon-trash" aria-hidden="true"><?php if (isset($component)) { $__componentOriginal669c17867d57615948fae15a035429b3 = $component; } ?>
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
    </div>
  </div>
</div>

<script>
  (function () {
    const modal = document.getElementById(<?php echo json_encode($modalId, 15, 512) ?>);
    const nameEl = document.getElementById(<?php echo json_encode($nameId, 15, 512) ?>);
    const confirmBtn = document.getElementById(<?php echo json_encode($confirmId, 15, 512) ?>);

    if (!modal || !nameEl || !confirmBtn) {
      return;
    }

    let activeForm = null;

    const closeModal = function () {
      modal.hidden = true;
      document.body.classList.remove('panel-modal-open');
      activeForm = null;
      nameEl.textContent = '-';
    };

    document.querySelectorAll(<?php echo json_encode($triggerSelector, 15, 512) ?>).forEach(function (button) {
      button.addEventListener('click', function (event) {
        event.preventDefault();
        const form = button.closest('form[data-delete-form]');
        if (!form) {
          return;
        }

        activeForm = form;
        const fileName = form.getAttribute('data-delete-name') || 'Selected file';
        nameEl.textContent = fileName;
        modal.hidden = false;
        document.body.classList.add('panel-modal-open');
      });
    });

    modal.querySelectorAll('[data-delete-close]').forEach(function (button) {
      button.addEventListener('click', closeModal);
    });

    confirmBtn.addEventListener('click', function () {
      if (!activeForm) {
        return;
      }

      if (typeof activeForm.requestSubmit === 'function') {
        activeForm.requestSubmit();
      } else {
        activeForm.submit();
      }
    });

    document.addEventListener('keydown', function (event) {
      if (modal.hidden) {
        return;
      }
      if (event.key === 'Escape') {
        closeModal();
      }
    });
  })();
</script>
<?php /**PATH /home/asifk/projects/maccento/resources/views/components/panel-delete-confirm-modal.blade.php ENDPATH**/ ?>