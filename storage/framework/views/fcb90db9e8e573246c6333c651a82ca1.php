

<?php $__env->startSection('content'); ?>
<section class="panel-card crm-email-hero">
  <div>
    <h2 class="panel-section-title">CRM Email Operations</h2>
    <p class="panel-muted">Send high-priority notifications instantly, or craft custom outbound emails with CC, BCC, and reply-to routing.</p>
  </div>
  <div class="crm-email-kpis">
    <article class="panel-card">
      <span class="panel-kpi-label">New Leads</span>
      <p class="panel-kpi-value"><?php echo e(number_format((int) $pipelineSummary['leads_new'])); ?></p>
    </article>
    <article class="panel-card">
      <span class="panel-kpi-label">Qualified Leads</span>
      <p class="panel-kpi-value"><?php echo e(number_format((int) $pipelineSummary['leads_qualified'])); ?></p>
    </article>
    <article class="panel-card">
      <span class="panel-kpi-label">Booked Quotes</span>
      <p class="panel-kpi-value"><?php echo e(number_format((int) $pipelineSummary['quotes_booked'])); ?></p>
    </article>
    <article class="panel-card">
      <span class="panel-kpi-label">Overdue Invoices</span>
      <p class="panel-kpi-value"><?php echo e(number_format((int) $pipelineSummary['invoices_overdue'])); ?></p>
    </article>
  </div>
</section>

<section class="panel-card panel-stack">
  <h2 class="panel-section-title">One-Click Send</h2>
  <p class="panel-muted">Each quick action uses a prebuilt professional template and sends immediately with one click.</p>

  <div class="crm-email-quick-grid">
    <?php $__currentLoopData = $quickTemplates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $template): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <form method="post" action="<?php echo e(route('admin.emails.send')); ?>" class="crm-email-quick-card js-quick-card" data-base-subject="<?php echo e($template['subject_preview']); ?>">
      <?php echo csrf_field(); ?>
      <input type="hidden" name="mode" value="template">
      <input type="hidden" name="template_key" value="<?php echo e($template['key']); ?>">

      <div>
        <h3><?php echo e($template['title']); ?></h3>
        <p class="panel-muted"><?php echo e($template['description']); ?></p>
      </div>

      <label>
        <span>Recipient</span>
        <input class="panel-input js-quick-recipient" type="email" name="recipient_email" value="<?php echo e(old('recipient_email', $defaultRecipient)); ?>" required>
      </label>

      <label>
        <span>Reply-to (optional)</span>
        <input class="panel-input" type="email" name="reply_to" value="<?php echo e(old('reply_to', $defaultRecipient)); ?>">
      </label>

      <label>
        <span>Thread Project (optional)</span>
        <select class="panel-input js-quick-project" name="client_project_id">
          <option value="">Auto-detect from recipient</option>
          <?php $__currentLoopData = $projectOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $projectOption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <option value="<?php echo e($projectOption['id']); ?>" <?php if((string) old('client_project_id') === (string) $projectOption['id']): echo 'selected'; endif; ?>><?php echo e($projectOption['label']); ?></option>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
      </label>

      <small class="panel-muted crm-subject-preview">Final outgoing subject: <strong class="js-quick-subject-preview"><?php echo e($template['subject_preview'] ?: '-'); ?></strong></small>

      <button class="panel-btn panel-btn-primary" type="submit">Send Now</button>
    </form>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </div>
</section>

<section class="crm-email-compose-layout">
  <article class="panel-card panel-stack">
    <div>
      <h2 class="panel-section-title">Compose Custom Email</h2>
      <p class="panel-muted">For client communication, follow-ups, or team notifications with full control.</p>
    </div>

    <form method="post" action="<?php echo e(route('admin.emails.send')); ?>" class="panel-stack">
      <?php echo csrf_field(); ?>
      <input type="hidden" name="mode" value="custom">

      <div class="panel-form-row">
        <label>
          <span>To</span>
          <input id="customRecipientEmail" class="panel-input" type="email" name="recipient_email" value="<?php echo e(old('recipient_email', $defaultRecipient)); ?>" required>
        </label>
        <label>
          <span>Reply-to</span>
          <input class="panel-input" type="email" name="reply_to" value="<?php echo e(old('reply_to', $defaultRecipient)); ?>">
        </label>
      </div>

      <div class="panel-form-row">
        <label>
          <span>CC (comma separated)</span>
          <input class="panel-input" type="text" name="cc" value="<?php echo e(old('cc')); ?>" placeholder="team@maccento.ca, ops@maccento.ca">
        </label>
        <label>
          <span>BCC (comma separated)</span>
          <input class="panel-input" type="text" name="bcc" value="<?php echo e(old('bcc')); ?>" placeholder="archive@maccento.ca">
        </label>
      </div>

      <label>
        <span>Thread Project (optional)</span>
        <select id="customClientProjectId" class="panel-input" name="client_project_id">
          <option value="">Auto-detect from recipient (only when exactly one project)</option>
          <?php $__currentLoopData = $projectOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $projectOption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <option value="<?php echo e($projectOption['id']); ?>" <?php if((string) old('client_project_id') === (string) $projectOption['id']): echo 'selected'; endif; ?>><?php echo e($projectOption['label']); ?></option>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <small class="panel-muted">Selecting a project forces subject tagging as <strong>[P#id]</strong> for reliable reply threading.</small>
      </label>

      <label>
        <span>Subject</span>
        <input id="customSubjectInput" class="panel-input" type="text" name="subject" value="<?php echo e(old('subject')); ?>" maxlength="180" required>
        <small id="customSubjectPreview" class="panel-muted crm-subject-preview">Final outgoing subject: <strong id="customSubjectPreviewValue">-</strong></small>
      </label>

      <label>
        <span>Message</span>
        <textarea class="panel-textarea" name="message" rows="12" required><?php echo e(old('message')); ?></textarea>
      </label>

      <div class="panel-form-row" style="justify-content: space-between; align-items: center;">
        <div class="panel-badge">Outbound channel: SendGrid SMTP</div>
        <button class="panel-btn panel-btn-primary" type="submit">Send Custom Email</button>
      </div>
    </form>
  </article>

  <aside class="panel-card panel-stack crm-email-side">
    <h2 class="panel-section-title">Execution Checklist</h2>
    <ul>
      <li>Use an accurate subject line and include context in first sentence.</li>
      <li>Keep one call-to-action per email for better response rates.</li>
      <li>Use CC for collaborators and BCC for silent internal archive only.</li>
      <li>Delivery errors appear as panel alerts after submit.</li>
    </ul>

    <div class="panel-badge">Default notification inbox: <?php echo e($defaultRecipient); ?></div>
  </aside>
</section>

<section class="panel-card panel-stack">
  <div class="panel-form-row" style="justify-content: space-between; align-items: center;">
    <h2 class="panel-section-title" style="margin: 0;">Email History Log</h2>
    <span class="panel-badge"><?php echo e(number_format((int) $emailLogs->total())); ?> records</span>
  </div>

  <div class="panel-table-wrap">
    <table class="panel-table crm-email-log-table">
      <thead>
        <tr>
          <th>Date</th>
          <th>Status</th>
          <th>Mode</th>
          <th>Recipient</th>
          <th>Subject</th>
          <th>Sender</th>
          <th>Notes</th>
        </tr>
      </thead>
      <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $emailLogs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <?php
          $timeline = $emailEventTimeline->get($log->id, collect());
        ?>
        <tr>
          <td>
            <strong><?php echo e($log->created_at?->format('Y-m-d H:i') ?: '-'); ?></strong>
            <?php if($log->sent_at): ?>
            <p class="panel-muted" style="margin: 4px 0 0;">Sent <?php echo e($log->sent_at->diffForHumans()); ?></p>
            <?php endif; ?>
          </td>
          <td>
            <span class="panel-badge <?php if($log->status === 'failed'): ?> panel-badge-danger <?php endif; ?>"><?php echo e(strtoupper($log->status)); ?></span>
          </td>
          <td>
            <p style="margin: 0; text-transform: capitalize;"><?php echo e($log->mode); ?></p>
            <p class="panel-muted" style="margin: 4px 0 0;"><?php echo e($log->template_key ?: 'custom'); ?></p>
          </td>
          <td>
            <p style="margin: 0;"><?php echo e($log->recipient_email); ?></p>
            <?php if($log->cc): ?>
            <p class="panel-muted" style="margin: 4px 0 0;">CC: <?php echo e($log->cc); ?></p>
            <?php endif; ?>
            <?php if($log->bcc): ?>
            <p class="panel-muted" style="margin: 4px 0 0;">BCC: <?php echo e($log->bcc); ?></p>
            <?php endif; ?>
          </td>
          <td>
            <p style="margin: 0;"><?php echo e($log->subject); ?></p>
            <?php if($log->body_preview): ?>
            <p class="panel-muted" style="margin: 4px 0 0;"><?php echo e(\Illuminate\Support\Str::limit($log->body_preview, 130)); ?></p>
            <?php endif; ?>
            <?php if($log->provider_status): ?>
            <p class="panel-muted" style="margin: 4px 0 0;">Provider: <?php echo e(strtoupper($log->provider_status)); ?></p>
            <?php endif; ?>
          </td>
          <td>
            <p style="margin: 0;"><?php echo e($log->creator?->name ?: 'System'); ?></p>
            <p class="panel-muted" style="margin: 4px 0 0;"><?php echo e($log->creator?->email ?: '-'); ?></p>
          </td>
          <td>
            <?php if($log->status === 'failed'): ?>
            <span class="panel-badge panel-badge-danger"><?php echo e($log->error_message ?: 'Unknown transport error'); ?></span>
            <?php else: ?>
            <span class="panel-muted">Delivered to transport</span>
            <?php endif; ?>

            <?php if($timeline->count() > 0): ?>
            <div class="crm-email-timeline">
              <?php $__currentLoopData = $timeline; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $eventItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <div class="crm-email-timeline-item">
                <strong><?php echo e(strtoupper((string) $eventItem->event_type)); ?></strong>
                <span><?php echo e($eventItem->occurred_at?->format('Y-m-d H:i:s') ?: $eventItem->created_at?->format('Y-m-d H:i:s')); ?></span>
              </div>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <tr>
          <td colspan="7" class="panel-muted">No email logs yet. Send from this tab to start tracking history.</td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if (isset($component)) { $__componentOriginal9f9d3eae18f18ccf28f34f84596c1d92 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9f9d3eae18f18ccf28f34f84596c1d92 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.panel-pagination','data' => ['paginator' => $emailLogs]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('panel-pagination'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['paginator' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($emailLogs)]); ?>
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

<script>
  (function () {
    const projectOptions = <?php echo json_encode($projectOptions, 15, 512) ?>;
    const projectTagPattern = /\[(?:project|proj|p)\s*[-:#]?\s*\d+\]/i;

    const normalizeEmail = (value) => String(value || '').trim().toLowerCase();

    const appendProjectTag = (subject, projectId) => {
      const trimmedSubject = String(subject || '').trim();
      if (trimmedSubject === '' || !projectId || Number(projectId) <= 0) {
        return trimmedSubject;
      }

      if (projectTagPattern.test(trimmedSubject)) {
        return trimmedSubject;
      }

      return `${trimmedSubject} [P#${projectId}]`;
    };

    const resolveProjectIdFor = (recipientValue, selectedValue) => {
      const selectedId = Number(selectedValue || 0);
      if (selectedId > 0) {
        return selectedId;
      }

      const recipient = normalizeEmail(recipientValue);
      if (recipient === '') {
        return null;
      }

      const matchingProjectIds = projectOptions
        .filter((item) => normalizeEmail(item.client_email) === recipient)
        .map((item) => Number(item.id || 0))
        .filter((id) => id > 0);

      const unique = [...new Set(matchingProjectIds)];
      return unique.length === 1 ? unique[0] : null;
    };

    document.querySelectorAll('.js-quick-card').forEach((formEl) => {
      const recipientInput = formEl.querySelector('.js-quick-recipient');
      const projectSelect = formEl.querySelector('.js-quick-project');
      const previewValue = formEl.querySelector('.js-quick-subject-preview');
      const baseSubject = String(formEl.dataset.baseSubject || '').trim();

      if (!recipientInput || !projectSelect || !previewValue) {
        return;
      }

      const updateQuickPreview = () => {
        const projectId = resolveProjectIdFor(recipientInput.value, projectSelect.value);
        const finalSubject = appendProjectTag(baseSubject, projectId);
        previewValue.textContent = finalSubject === '' ? '-' : finalSubject;
      };

      recipientInput.addEventListener('input', updateQuickPreview);
      projectSelect.addEventListener('change', updateQuickPreview);
      updateQuickPreview();
    });

    const recipientInput = document.getElementById('customRecipientEmail');
    const projectSelect = document.getElementById('customClientProjectId');
    const subjectInput = document.getElementById('customSubjectInput');
    const previewValue = document.getElementById('customSubjectPreviewValue');

    if (!recipientInput || !projectSelect || !subjectInput || !previewValue) {
      return;
    }

    const updateSubjectPreview = () => {
      const forcedOrDetectedProjectId = resolveProjectIdFor(recipientInput.value, projectSelect.value);
      const finalSubject = appendProjectTag(subjectInput.value, forcedOrDetectedProjectId);
      previewValue.textContent = finalSubject === '' ? '-' : finalSubject;
    };

    recipientInput.addEventListener('input', updateSubjectPreview);
    projectSelect.addEventListener('change', updateSubjectPreview);
    subjectInput.addEventListener('input', updateSubjectPreview);

    updateSubjectPreview();
  })();
</script>

<style>
  .crm-email-hero {
    display: grid;
    gap: 16px;
  }

  .crm-email-kpis {
    display: grid;
    gap: 12px;
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }

  .crm-email-quick-grid {
    display: grid;
    gap: 14px;
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }

  .crm-email-quick-card {
    border: 1px solid rgba(15, 30, 56, 0.12);
    border-radius: 14px;
    padding: 14px;
    display: grid;
    gap: 10px;
    background: linear-gradient(160deg, #f7f9fc 0%, #ffffff 100%);
  }

  .crm-email-quick-card h3 {
    margin: 0 0 6px;
    font-size: 1rem;
    color: #17304f;
  }

  .crm-email-compose-layout {
    display: grid;
    gap: 14px;
    grid-template-columns: 2fr 1fr;
  }

  .crm-email-side ul {
    margin: 0;
    padding-left: 1rem;
    display: grid;
    gap: 8px;
  }

  .crm-email-log-table td {
    vertical-align: top;
  }

  .crm-email-timeline {
    margin-top: 8px;
    border-left: 2px solid rgba(20, 42, 74, 0.16);
    padding-left: 8px;
    display: grid;
    gap: 6px;
  }

  .crm-email-timeline-item {
    display: flex;
    gap: 8px;
    align-items: baseline;
    font-size: 0.8rem;
    color: #1f3554;
  }

  .crm-subject-preview {
    display: inline-block;
    margin-top: 6px;
  }

  @media (max-width: 1100px) {
    .crm-email-kpis,
    .crm-email-quick-grid,
    .crm-email-compose-layout {
      grid-template-columns: 1fr;
    }
  }
</style>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.panel', [
  'title' => 'Email Center',
  'heading' => 'Email Center',
  'subheading' => 'Professional one-click and custom email dispatch for CRM operations.',
], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/asifk/projects/maccento/resources/views/admin/emails-index.blade.php ENDPATH**/ ?>