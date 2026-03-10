

<?php $__env->startSection('content'); ?>
<?php
  $sourceCount = count($sourceSettings);
  $enabledCount = collect($sourceSettings)->filter(static fn ($item): bool => (bool) ($item['enabled'] ?? false))->count();
  $disabledCount = max(0, $sourceCount - $enabledCount);
?>

<section class="automation-page panel-stack">
  <article class="panel-card automation-hero">
    <div class="automation-hero__copy">
      <p class="automation-eyebrow">Lead Automation Console</p>
      <h2 class="automation-title">Design, control, and audit every welcome email flow</h2>
      <p class="automation-sub">Each source has its own tone, subject strategy, and AI instruction. Leads are persisted only when a valid email exists.</p>
    </div>
    <div class="automation-hero__stats" aria-label="Automation summary">
      <article class="automation-stat">
        <span class="automation-stat__label">Sources</span>
        <strong class="automation-stat__value"><?php echo e($sourceCount); ?></strong>
      </article>
      <article class="automation-stat">
        <span class="automation-stat__label">Enabled</span>
        <strong class="automation-stat__value"><?php echo e($enabledCount); ?></strong>
      </article>
      <article class="automation-stat">
        <span class="automation-stat__label">Disabled</span>
        <strong class="automation-stat__value"><?php echo e($disabledCount); ?></strong>
      </article>
    </div>
  </article>

  <div class="automation-layout">
    <form method="post" action="<?php echo e(route('admin.emails.automation.update')); ?>" class="automation-main panel-stack">
      <?php echo csrf_field(); ?>

      <?php $__currentLoopData = $sourceSettings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $setting): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <section class="panel-card automation-source-card">
        <div class="automation-source-card__head">
          <div>
            <h3 class="automation-source-card__title"><?php echo e($setting['label']); ?></h3>
            <p class="automation-source-card__desc"><?php echo e($setting['description']); ?></p>
          </div>

          <div class="automation-source-card__controls">
            <span class="panel-badge"><?php echo e(str_replace('_', ' ', (string) $setting['source'])); ?></span>
            <label class="automation-toggle" for="enabled_<?php echo e($setting['source']); ?>">
              <input type="hidden" name="enabled[<?php echo e($setting['source']); ?>]" value="0">
              <input
                id="enabled_<?php echo e($setting['source']); ?>"
                type="checkbox"
                name="enabled[<?php echo e($setting['source']); ?>]"
                value="1"
                <?php if((bool) ($setting['enabled'] ?? false)): ?> checked <?php endif; ?>
              >
              <span class="automation-toggle__track" aria-hidden="true"></span>
              <span class="automation-toggle__text">Automation enabled</span>
            </label>
          </div>
        </div>

        <div class="automation-field-grid">
          <label class="panel-field">
            <span class="automation-label">Tone Profile</span>
            <input
              class="panel-input"
              type="text"
              name="tone[<?php echo e($setting['source']); ?>]"
              maxlength="40"
              value="<?php echo e(old('tone.' . $setting['source'], $setting['tone'])); ?>"
              placeholder="professional, friendly, consultative"
              list="automation-tone-presets"
            >
          </label>

          <label class="panel-field">
            <span class="automation-label">Subject Prefix</span>
            <input
              class="panel-input"
              type="text"
              name="subject_prefix[<?php echo e($setting['source']); ?>]"
              maxlength="120"
              value="<?php echo e(old('subject_prefix.' . $setting['source'], $setting['subject_prefix'])); ?>"
              placeholder="Maccento Team:"
            >
          </label>
        </div>

        <label class="panel-field">
          <span class="automation-label">AI Template Instruction</span>
          <textarea
            class="panel-textarea"
            name="template_prompt[<?php echo e($setting['source']); ?>]"
            rows="5"
            maxlength="5000"
            placeholder="Guide the AI writing style and structure for this source."
          ><?php echo e(old('template_prompt.' . $setting['source'], $setting['template_prompt'])); ?></textarea>
        </label>
      </section>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

      <div class="automation-main__actions">
        <button class="panel-btn panel-btn-primary" type="submit">Save Automation Settings</button>
      </div>
    </form>

    <aside class="automation-side panel-stack">
      <section class="panel-card automation-ops-card">
        <p class="automation-eyebrow">Operations</p>
        <h3 class="automation-ops-card__title">One-time Historical Backfill</h3>
        <p class="automation-ops-card__desc">Process historical leads with email that missed the welcome workflow. Start with dry run to inspect impact before live send.</p>

        <div class="automation-ops-card__actions">
          <form method="post" action="<?php echo e(route('admin.emails.automation.backfill')); ?>">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="mode" value="dry-run">
            <button class="panel-btn" type="submit">Run Dry Run</button>
          </form>
          <form method="post" action="<?php echo e(route('admin.emails.automation.backfill')); ?>" data-confirm="Run live backfill now? This can send welcome emails to eligible historical leads.">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="mode" value="live">
            <button class="panel-btn panel-btn-danger" type="submit">Run Live</button>
          </form>
        </div>

        <?php if($errors->has('automation_backfill')): ?>
        <p class="automation-feedback is-error"><?php echo e($errors->first('automation_backfill')); ?></p>
        <?php endif; ?>

        <?php if(session('automation_backfill_output')): ?>
        <div class="automation-output">
          <p class="automation-output__label">Last output (<?php echo e(session('automation_backfill_mode', 'dry-run')); ?>):</p>
          <pre><?php echo e(session('automation_backfill_output')); ?></pre>
        </div>
        <?php endif; ?>
      </section>
    </aside>
  </div>

  <datalist id="automation-tone-presets">
    <option value="professional"></option>
    <option value="friendly"></option>
    <option value="consultative"></option>
    <option value="confident"></option>
    <option value="concise"></option>
  </datalist>
</section>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.panel', [
  'title' => 'Email Automation',
  'heading' => 'Email Automation',
  'subheading' => 'Configure source-specific AI welcome email behavior for captured leads.',
], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/asifk/projects/maccento/resources/views/admin/emails-automation.blade.php ENDPATH**/ ?>