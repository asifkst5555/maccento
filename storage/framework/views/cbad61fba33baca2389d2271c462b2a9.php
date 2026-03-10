<?php $__env->startSection('content'); ?>
<div class="corp-admin-shell panel-stack">
  <section class="panel-card panel-stack">
    <div class="panel-form-row" style="justify-content: space-between; align-items: center;">
      <div>
        <span class="panel-badge">Client CRM Portal</span>
        <h2 class="panel-section-title" style="margin-top: 12px;">Welcome<?php echo e($client?->name ? ', ' . $client->name : ''); ?></h2>
        <p class="panel-muted">Keep track of your active jobs, open invoices, quotations, and delivery progress from one professional workspace.</p>
      </div>
      <div class="panel-form-row" style="margin-bottom: 0;">
        <a class="panel-btn panel-btn-primary" href="<?php echo e(route('user.projects.index')); ?>">View Projects</a>
        <a class="panel-btn" href="<?php echo e(route('user.invoices.index')); ?>">Billing Center</a>
        <a class="panel-btn" href="<?php echo e(route('user.messages.index')); ?>">Messages</a>
      </div>
    </div>
  </section>

  <section class="panel-grid panel-grid-kpi">
    <article class="panel-card"><span class="panel-kpi-label">Active Projects</span><p class="panel-kpi-value"><?php echo e($portalStats['active_projects']); ?></p></article>
    <article class="panel-card"><span class="panel-kpi-label">Unpaid Invoices</span><p class="panel-kpi-value"><?php echo e($portalStats['unpaid_invoices']); ?></p></article>
    <article class="panel-card"><span class="panel-kpi-label">Pending Quotes</span><p class="panel-kpi-value"><?php echo e($portalStats['pending_quotes']); ?></p></article>
    <article class="panel-card"><span class="panel-kpi-label">Deliveries Ready</span><p class="panel-kpi-value"><?php echo e($portalStats['deliveries_ready']); ?></p></article>
  </section>

  <section class="panel-grid" style="grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 1rem;">
    <article class="panel-card panel-stack">
      <div class="panel-form-row" style="justify-content: space-between; align-items: center;">
        <h2 class="panel-section-title" style="margin: 0;">Active Projects</h2>
        <a class="panel-link" href="<?php echo e(route('user.projects.index')); ?>">Open all</a>
      </div>
      <div class="panel-table-wrap">
        <table class="panel-table">
          <thead>
            <tr>
              <th>Project</th>
              <th>Schedule</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $recentProjects; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $project): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
              <tr>
                <td data-label="Project">
                  <?php echo e($project->title); ?>

                  <div class="panel-muted"><?php echo e($project->service_type ?: 'Service pending'); ?></div>
                  <?php if(!blank($project->property_address)): ?>
                    <div class="panel-muted"><?php echo e($project->property_address); ?></div>
                  <?php endif; ?>
                </td>
                <td data-label="Schedule"><?php echo e($project->scheduled_at?->format('Y-m-d H:i') ?: 'To be confirmed'); ?></td>
                <td data-label="Status"><span class="panel-badge"><?php echo e($project->status); ?></span></td>
                <td data-label="Action">
                  <a class="panel-btn panel-btn-primary" href="<?php echo e(route('user.projects.show', $project)); ?>">Open</a>
                </td>
              </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
              <tr><td colspan="4" class="panel-muted">No projects are linked to your account yet.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </article>

    <article class="panel-card panel-stack">
      <div class="panel-form-row" style="justify-content: space-between; align-items: center;">
        <h2 class="panel-section-title" style="margin: 0;">Open Invoices</h2>
        <a class="panel-link" href="<?php echo e(route('user.invoices.index')); ?>">Open billing</a>
      </div>
      <div class="panel-table-wrap">
        <table class="panel-table">
          <thead>
            <tr>
              <th>Invoice</th>
              <th>Due</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $recentInvoices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $invoice): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
              <tr>
                <td data-label="Invoice">
                  <?php echo e($invoice->invoice_number); ?>

                  <div class="panel-muted"><?php echo e($invoice->project?->title ?: 'General invoice'); ?></div>
                </td>
                <td data-label="Due"><?php echo e($invoice->due_date?->format('Y-m-d') ?: 'Not set'); ?></td>
                <td data-label="Status"><span class="panel-badge"><?php echo e($invoice->status); ?></span></td>
                <td data-label="Action">
                  <a class="panel-btn panel-btn-primary" href="<?php echo e(route('user.invoices.download', $invoice)); ?>">Download</a>
                </td>
              </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
              <tr><td colspan="4" class="panel-muted">No invoices are available right now.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </article>
  </section>

  <section class="panel-grid" style="grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 1rem;">
    <article class="panel-card panel-stack">
      <div class="panel-form-row" style="justify-content: space-between; align-items: center;">
        <h2 class="panel-section-title" style="margin: 0;">Recent Quotations</h2>
        <a class="panel-link" href="<?php echo e(route('user.quotes.index')); ?>">Open quotes</a>
      </div>
      <div class="panel-table-wrap">
        <table class="panel-table">
          <thead>
            <tr>
              <th>Quote</th>
              <th>Submitted</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $quotes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $quote): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
              <tr>
                <td data-label="Quote">
                  <?php echo e($quote->quote_id); ?>

                  <div class="panel-muted"><?php echo e(is_array($quote->services) ? implode(', ', $quote->services) : 'Services pending'); ?></div>
                </td>
                <td data-label="Submitted"><?php echo e($quote->submitted_at?->format('Y-m-d H:i') ?: '-'); ?></td>
                <td data-label="Status"><span class="panel-badge"><?php echo e($quote->status); ?></span></td>
                <td data-label="Action">
                  <a class="panel-btn" href="<?php echo e(route('user.quotes.show', $quote)); ?>">Open</a>
                </td>
              </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
              <tr><td colspan="4" class="panel-muted">No quotations have been created for your account yet.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </article>

    <article class="panel-card panel-stack">
      <div class="panel-form-row" style="justify-content: space-between; align-items: center;">
        <h2 class="panel-section-title" style="margin: 0;">Recent Messages</h2>
        <a class="panel-link" href="<?php echo e(route('user.messages.index')); ?>">Open messages</a>
      </div>
      <div class="panel-table-wrap">
        <table class="panel-table">
          <thead>
            <tr>
              <th>Thread</th>
              <th>Message</th>
              <th>Date</th>
              <th>Role</th>
            </tr>
          </thead>
          <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $recentMessages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $message): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
              <tr>
                <td data-label="Thread"><?php echo e($message->project?->title ?: 'General account message'); ?></td>
                <td data-label="Message"><?php echo e(\Illuminate\Support\Str::limit($message->message, 90)); ?></td>
                <td data-label="Date"><?php echo e($message->sent_at?->format('Y-m-d H:i') ?: $message->created_at?->format('Y-m-d H:i')); ?></td>
                <td data-label="Role"><span class="panel-badge"><?php echo e(strtoupper($message->sender_role)); ?></span></td>
              </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
              <tr><td colspan="4" class="panel-muted">No client communication has been logged yet.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </article>
  </section>

  <section class="panel-grid" style="grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 1rem;">
    <article class="panel-card panel-stack">
      <h2 class="panel-section-title">Request a New Service</h2>
      <p class="panel-muted">Send a new request directly to the team from your portal.</p>
      <form method="post" action="<?php echo e(route('user.service-requests.store')); ?>" class="panel-stack">
        <?php echo csrf_field(); ?>
        <?php if($recentProjects->isNotEmpty()): ?>
          <select class="panel-select" name="client_project_id">
            <option value="">General request (not linked to a project)</option>
            <?php $__currentLoopData = $recentProjects; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $project): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <option value="<?php echo e($project->id); ?>"><?php echo e($project->title); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </select>
        <?php endif; ?>
        <input class="panel-input" type="text" name="requested_service" placeholder="Service needed" required>
        <input class="panel-input" type="text" name="subject" placeholder="Short subject (optional)">
        <input class="panel-input" type="date" name="preferred_date">
        <textarea class="panel-textarea" name="details" placeholder="Tell us about your request"></textarea>
        <button class="panel-btn panel-btn-primary" type="submit">Submit Request</button>
      </form>
    </article>

    <article class="panel-card panel-stack">
      <h2 class="panel-section-title">Recent Lead Activity</h2>
      <div class="panel-table-wrap">
        <table class="panel-table">
          <thead>
            <tr>
              <th>Lead</th>
              <th>Service</th>
              <th>Location</th>
              <th>Status</th>
              <th>Score</th>
            </tr>
          </thead>
          <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $leads; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $lead): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
              <tr>
                <td data-label="Lead">Lead #<?php echo e($lead->id); ?></td>
                <td data-label="Service"><?php echo e($lead->service_type ?: 'Service not specified'); ?></td>
                <td data-label="Location"><?php echo e($lead->location ?: 'Location pending'); ?></td>
                <td data-label="Status"><span class="panel-badge"><?php echo e($lead->status); ?></span></td>
                <td data-label="Score"><?php echo e($lead->score); ?></td>
              </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
              <tr><td colspan="5" class="panel-muted">No website lead activity is linked to your account yet.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </article>
  </section>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.panel', [
  'title' => 'Client Workspace',
  'heading' => 'Client Workspace',
  'subheading' => 'Track projects, invoices, quotes, and deliveries in one client portal.',
], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/asifk/projects/maccento/resources/views/user/dashboard.blade.php ENDPATH**/ ?>