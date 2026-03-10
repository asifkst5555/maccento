<?php $__env->startSection('content'); ?>
<?php
  $requestStatusClass = static function (?string $status): string {
    return 'client-status-chip status-' . \Illuminate\Support\Str::slug((string) $status);
  };
?>

<div class="corp-admin-shell panel-stack">
  <section class="panel-grid panel-grid-kpi">
    <article class="panel-card">
      <span class="panel-kpi-label">Active Projects</span>
      <p class="panel-kpi-value"><?php echo e($portalStats['active_projects']); ?></p>
    </article>
    <article class="panel-card">
      <span class="panel-kpi-label">Deliveries Ready</span>
      <p class="panel-kpi-value"><?php echo e($portalStats['deliveries_ready']); ?></p>
    </article>
    <article class="panel-card">
      <span class="panel-kpi-label">Portal Messages</span>
      <p class="panel-kpi-value"><?php echo e($portalStats['message_count']); ?></p>
    </article>
  </section>

  <div class="messages-chat-shell">
    <section class="panel-card messages-chat-layout">
      <aside class="messages-thread-panel">
        <div class="messages-thread-top">
          <div>
            <h2 class="messages-thread-title">Chats</h2>
            <p class="messages-thread-sub">Open any admin conversation or project thread and keep updates centralized.</p>
          </div>
        </div>

        <form method="get" class="messages-thread-search">
          <select class="panel-select" name="admin_id">
            <?php $__empty_1 = true; $__currentLoopData = $adminUsers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $adminUser): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
              <option value="<?php echo e($adminUser->id); ?>" <?php if((int) ($activeAdmin?->id ?? 0) === (int) $adminUser->id): echo 'selected'; endif; ?>>
                <?php echo e($adminUser->name ?: ('Admin #' . $adminUser->id)); ?> <?php if($adminUser->role): ?> - <?php echo e(ucfirst($adminUser->role)); ?> <?php endif; ?>
              </option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
              <option value="">No admins available</option>
            <?php endif; ?>
          </select>
          <div class="panel-form-row" style="margin-bottom:0;">
            <button class="panel-btn panel-btn-primary" type="submit">Open</button>
            <a class="messages-thread-clear" href="<?php echo e(route('user.messages.index')); ?>">Clear</a>
          </div>
        </form>

        <div class="messages-thread-list">
          <?php $__empty_1 = true; $__currentLoopData = $adminUsers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $adminUser): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php ($summary = $adminThreadSummaries->firstWhere('thread_admin_id', $adminUser->id)); ?>
            <a
              href="<?php echo e(route('user.messages.index', ['admin_id' => $adminUser->id])); ?>"
              class="messages-thread-item<?php echo e((int) ($activeAdmin?->id ?? 0) === (int) $adminUser->id ? ' is-active' : ''); ?>"
            >
              <div class="messages-thread-avatar"><?php echo e(strtoupper(substr($adminUser->name ?: 'A', 0, 2))); ?></div>
              <div class="messages-thread-main">
                <div class="messages-thread-row">
                  <h3 class="messages-thread-name"><?php echo e($adminUser->name ?: ('Admin #' . $adminUser->id)); ?></h3>
                  <span class="messages-thread-time"><?php echo e($summary?->sent_at?->diffForHumans() ?: 'No chat yet'); ?></span>
                </div>
                <p class="messages-thread-meta">
                  <?php echo e($adminUser->email ?: 'No email on file'); ?>

                  <?php if($adminUser->role): ?>
                    &bull; <?php echo e(ucfirst($adminUser->role)); ?>

                  <?php endif; ?>
                </p>
                <p class="messages-thread-preview">
                  <?php if($summary): ?>
                    <strong><?php echo e($summary->sender?->name ?: 'Admin'); ?>:</strong> <?php echo e(mb_strimwidth((string) $summary->message, 0, 90, '...')); ?>

                  <?php else: ?>
                    No direct conversation yet. Start the thread from CRM.
                  <?php endif; ?>
                </p>
              </div>
            </a>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <p class="panel-muted">No admins available yet.</p>
          <?php endif; ?>

          <div class="messages-thread-divider">Project Threads</div>
          <?php $__empty_1 = true; $__currentLoopData = $projects; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $project): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <a class="messages-thread-item" href="<?php echo e(route('user.projects.show', $project)); ?>">
              <div class="messages-thread-avatar"><?php echo e(strtoupper(substr($project->title ?: 'P', 0, 2))); ?></div>
              <div class="messages-thread-main">
                <div class="messages-thread-row">
                  <h3 class="messages-thread-name"><?php echo e($project->title); ?></h3>
                  <span class="messages-thread-time"><?php echo e($project->messages_count); ?> msgs</span>
                </div>
                <p class="messages-thread-meta"><?php echo e($project->service_requests_count); ?> service requests</p>
                <p class="messages-thread-preview">Open the project thread</p>
              </div>
            </a>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <p class="panel-muted">No project threads yet.</p>
          <?php endif; ?>
        </div>
      </aside>

      <section class="messages-chat-panel">
        <?php if($activeAdmin): ?>
          <header class="messages-chat-head">
            <div>
              <h2><?php echo e($activeAdmin->name ?: ('Admin #' . $activeAdmin->id)); ?></h2>
              <p>
                <?php echo e($activeAdmin->email ?: 'No email on file'); ?>

                <?php if($activeAdmin->role): ?>
                  &bull; <?php echo e(ucfirst($activeAdmin->role)); ?>

                <?php endif; ?>
              </p>
            </div>
            <div class="messages-chat-head-meta">
              <span class="panel-badge">Active</span>
            </div>
          </header>

          <div class="messages-chat-stream">
            <?php ($lastDate = null); ?>
            <?php $__empty_1 = true; $__currentLoopData = $adminMessages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $message): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
              <?php ($currentDate = optional($message->sent_at ?? $message->created_at)?->format('Y-m-d')); ?>
              <?php if($currentDate !== $lastDate): ?>
                <div class="messages-chat-date"><?php echo e(optional($message->sent_at ?? $message->created_at)?->format('M j, Y')); ?></div>
                <?php ($lastDate = $currentDate); ?>
              <?php endif; ?>
              <article class="messages-chat-row<?php echo e((int) $message->sender_user_id === (int) ($currentUser?->id ?? 0) ? ' is-admin' : ''); ?>">
                <div class="messages-chat-bubble"><?php echo e($message->message); ?></div>
                <div class="messages-chat-note">
                  <span><?php echo e($message->sender?->name ?: 'Admin'); ?></span>
                  <span>&bull;</span>
                  <span><?php echo e(optional($message->sent_at ?? $message->created_at)?->format('M j, g:i A')); ?></span>
                </div>
              </article>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
              <div class="messages-empty-state">
                <div>
                  <strong>No messages yet</strong>
                  Start the first direct conversation with this admin from the composer below.
                </div>
              </div>
            <?php endif; ?>
          </div>

          <form method="post" action="<?php echo e(route('user.messages.store')); ?>" class="messages-chat-compose">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="admin_user_id" value="<?php echo e($activeAdmin->id); ?>">
            <div class="messages-chat-compose-top">
              <div class="panel-muted" style="display:grid;align-items:center;padding:0 0.25rem;">
                Send a direct message to the admin team. Optional project context keeps the thread organized.
              </div>
              <button class="panel-btn panel-btn-primary" type="submit">Send Message</button>
            </div>
            <textarea class="panel-textarea" name="message" placeholder="Write a clear message for the admin team" required><?php echo e(old('message')); ?></textarea>
          </form>
        <?php else: ?>
          <div class="messages-empty-state">
            <div>
              <strong>No admin selected</strong>
              Choose an admin to start a direct message.
            </div>
          </div>
        <?php endif; ?>
      </section>
    </section>
  </div>

  <section class="panel-grid" style="grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1rem;">
    <article class="panel-card panel-stack">
      <h2 class="panel-section-title">Send a New Request</h2>
      <p class="panel-muted">Use this to request additional services, revisions, or schedule updates.</p>
      <form method="post" action="<?php echo e(route('user.service-requests.store')); ?>" class="panel-stack">
        <?php echo csrf_field(); ?>
        <select class="panel-select" name="client_project_id">
          <option value="">General request (not linked to a project)</option>
          <?php $__currentLoopData = $projects; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $project): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($project->id); ?>"><?php echo e($project->title); ?></option>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <input class="panel-input" type="text" name="requested_service" placeholder="Requested service" required>
        <input class="panel-input" type="text" name="subject" placeholder="Subject (optional)">
        <input class="panel-input" type="date" name="preferred_date">
        <textarea class="panel-textarea" name="details" placeholder="Add details for the team"></textarea>
        <button class="panel-btn panel-btn-primary" type="submit">Submit Request</button>
      </form>
    </article>

    <article class="panel-card panel-stack">
      <h2 class="panel-section-title">Team Message Timeline</h2>
      <?php $__empty_1 = true; $__currentLoopData = $messages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $message): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <div class="panel-chat-item <?php echo e($message->sender_role === 'client' ? 'is-user' : 'is-assistant'); ?>">
          <p class="panel-chat-role">
            <?php echo e(strtoupper($message->sender_role)); ?>

            <?php if($message->project): ?>
              &bull; <?php echo e($message->project->title); ?>

            <?php endif; ?>
          </p>
          <p class="panel-chat-text"><?php echo e($message->message); ?></p>
          <p class="panel-muted"><?php echo e($message->sent_at?->format('Y-m-d H:i') ?: $message->created_at?->format('Y-m-d H:i')); ?></p>
        </div>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div class="panel-muted"><strong>No message history yet</strong>Team replies and client portal updates will appear in this timeline.</div>
      <?php endif; ?>
      <?php if (isset($component)) { $__componentOriginal9f9d3eae18f18ccf28f34f84596c1d92 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9f9d3eae18f18ccf28f34f84596c1d92 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.panel-pagination','data' => ['paginator' => $messages]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('panel-pagination'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['paginator' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($messages)]); ?>
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
    </article>
  </section>

  <section class="panel-card panel-stack">
    <h2 class="panel-section-title">Service Request History</h2>
    <div class="panel-table-wrap">
      <table class="panel-table">
        <thead>
          <tr>
            <th>Requested Service</th>
            <th>Project</th>
            <th>Status</th>
            <th>Preferred Date</th>
          </tr>
        </thead>
        <tbody>
          <?php $__empty_1 = true; $__currentLoopData = $serviceRequests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $requestItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr>
              <td data-label="Requested Service">
                <?php echo e($requestItem->requested_service); ?>

                <?php if(!blank($requestItem->subject)): ?>
                  <div class="panel-muted"><?php echo e($requestItem->subject); ?></div>
                <?php endif; ?>
              </td>
              <td data-label="Project"><?php echo e($requestItem->project?->title ?: 'General request'); ?></td>
              <td data-label="Status"><span class="<?php echo e($requestStatusClass($requestItem->status)); ?>"><?php echo e($requestItem->status); ?></span></td>
              <td data-label="Preferred Date"><?php echo e($requestItem->preferred_date?->format('Y-m-d') ?: '-'); ?></td>
            </tr>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr>
              <td colspan="4" class="panel-muted">
                <strong>No service requests yet</strong>Request history will appear here after you send your first portal request.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <?php if (isset($component)) { $__componentOriginal9f9d3eae18f18ccf28f34f84596c1d92 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9f9d3eae18f18ccf28f34f84596c1d92 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.panel-pagination','data' => ['paginator' => $serviceRequests]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('panel-pagination'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['paginator' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($serviceRequests)]); ?>
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
  'title' => 'Messages',
  'heading' => 'Messages',
  'subheading' => 'Keep service requests and team communication organized in one client timeline.',
], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/asifk/projects/maccento/resources/views/user/messages-index.blade.php ENDPATH**/ ?>