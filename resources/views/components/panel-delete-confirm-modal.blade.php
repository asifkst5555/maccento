@props([
    'modalId' => 'panel-delete-confirm-modal',
    'triggerSelector' => '[data-delete-trigger]',
    'title' => 'Confirm Deletion',
])

@php
    $nameId = $modalId . '-name';
    $confirmId = $modalId . '-confirm';
@endphp

<div id="{{ $modalId }}" class="panel-modal" hidden>
  <div class="panel-modal-backdrop" data-delete-close></div>
  <div class="panel-modal-dialog" style="max-width: 560px;">
    <div class="panel-modal-head">
      <h3 class="panel-modal-title">{{ $title }}</h3>
      <button class="panel-modal-close" type="button" data-delete-close aria-label="Close delete confirmation">×</button>
    </div>

    <div class="panel-modal-body">
      <p class="panel-muted" style="margin:0 0 8px;">You are about to permanently delete this file:</p>
      <p id="{{ $nameId }}" style="margin:0; font-weight:600; color:#10223e; word-break:break-word;">-</p>
      <p class="panel-muted" style="margin:10px 0 0;">This action cannot be undone.</p>
    </div>

    <div class="panel-modal-foot" style="gap:10px;">
      <button class="panel-btn" type="button" data-delete-close>Cancel</button>
      <button class="panel-btn panel-btn-danger panel-btn-icon" type="button" id="{{ $confirmId }}" title="Confirm delete" aria-label="Confirm delete"><span class="panel-icon-trash" aria-hidden="true"><svg viewBox="0 0 20 20"><path d="M5 6h10M8 6V4h4v2m-6 0l.5 9h7L14 6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span></button>
    </div>
  </div>
</div>

<script>
  (function () {
    const modal = document.getElementById(@json($modalId));
    const nameEl = document.getElementById(@json($nameId));
    const confirmBtn = document.getElementById(@json($confirmId));

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

    document.querySelectorAll(@json($triggerSelector)).forEach(function (button) {
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
