

<?php $__env->startSection('content'); ?>
<?php ($mode = $mode ?? 'clients'); ?>
<style>
  .messages-chat-shell {
    --messages-bg: radial-gradient(circle at top, #f7f8fb 0%, #eef2f7 55%, #e9eef5 100%);
    --messages-ink: #0f2137;
    --messages-muted: #5f7286;
    --messages-line: #d6dde7;
    --messages-panel: #ffffff;
    --messages-panel-alt: #f4f7fb;
    --messages-shadow: 0 18px 38px rgba(15, 33, 55, 0.08);
    --messages-accent: #b71c2d;
    --messages-accent-dark: #8f1624;
    --messages-danger: #b71c2d;
    --messages-danger-dark: #8f1624;
    display: grid;
    gap: 1rem;
  }

  .messages-chat-shell .panel-card {
    border: 1px solid var(--messages-line);
    border-radius: 20px;
    background: var(--messages-panel);
    box-shadow: none;
  }

  .messages-chat-shell .panel-input,
  .messages-chat-shell .panel-select,
  .messages-chat-shell .panel-textarea,
  .messages-chat-shell .panel-btn {
    border-radius: 14px;
    background-color: #fff;
  }

  .messages-chat-shell .panel-btn-primary {
    background: linear-gradient(135deg, var(--messages-accent) 0%, var(--messages-accent-dark) 100%);
    border-color: transparent;
    color: #ffffff;
    box-shadow: none;
  }

  .messages-chat-shell .panel-btn-primary:hover {
    filter: brightness(0.98);
  }

  .messages-chat-shell .panel-btn {
    border-color: #cfd7e3;
    color: var(--messages-ink);
  }

  .messages-chat-layout {
    display: grid;
    grid-template-columns: minmax(300px, 360px) minmax(0, 1fr);
    gap: 1rem;
    min-height: 72vh;
  }

  .messages-thread-panel {
    padding: 1rem;
    display: grid;
    grid-template-rows: auto auto minmax(0, 1fr);
    gap: 0.9rem;
    background: linear-gradient(180deg, #ffffff 0%, #f5f7fb 100%);
    color: var(--messages-ink);
  }

  .messages-thread-panel .panel-kpi-label,
  .messages-thread-panel .panel-muted {
    color: var(--messages-muted);
  }

  .messages-thread-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
  }

  .messages-thread-title {
    margin: 0;
    font-size: 1.55rem;
    line-height: 1.1;
    color: var(--messages-ink);
  }

  .messages-thread-sub {
    margin: 0.3rem 0 0;
    color: var(--messages-muted);
    font-size: 0.94rem;
  }

  .messages-thread-search {
    display: grid;
    gap: 0.7rem;
  }

  .messages-chat-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 0.6rem;
  }

  .messages-chat-tab {
    padding: 0.55rem 1rem;
    border-radius: 12px;
    border: 1px solid var(--messages-line);
    background: #ffffff;
    color: var(--messages-ink);
    font-weight: 600;
    font-size: 0.9rem;
    text-decoration: none;
    transition: border-color 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease;
  }

  .messages-chat-tab:hover {
    border-color: #bfcad8;
    box-shadow: none;
  }

  .messages-chat-tab.is-active {
    background: linear-gradient(135deg, var(--messages-accent) 0%, var(--messages-accent-dark) 100%);
    border-color: transparent;
    color: #ffffff;
    box-shadow: none;
  }

  .messages-thread-search .panel-form-row {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto auto;
    align-items: center;
    gap: 0.6rem;
  }

  .messages-thread-clear {
    text-decoration: none;
    padding: 0.65rem 1.1rem;
    border-radius: 14px;
    border: 1px solid #cfd7e3;
    background: #ffffff;
    color: var(--messages-ink);
    font-weight: 600;
    font-size: 0.9rem;
    line-height: 1;
    transition: border-color 0.18s ease, background-color 0.18s ease;
  }

  .messages-thread-clear:hover {
    border-color: #bfcad8;
    background: #f8fafc;
  }

  .messages-thread-search .panel-input,
  .messages-thread-search .panel-select {
    background: #ffffff;
    border-color: #d4dce8;
    color: var(--messages-ink);
  }

  .messages-thread-search .panel-input::placeholder {
    color: #8b98aa;
  }

  .messages-thread-search .panel-link {
    color: var(--messages-muted);
  }

  .messages-thread-list {
    display: grid;
    gap: 0.55rem;
    overflow-y: auto;
    padding-right: 0.2rem;
    align-content: start;
    grid-auto-rows: max-content;
  }

  .messages-thread-item {
    display: grid;
    grid-template-columns: 44px minmax(0, 1fr);
    gap: 0.6rem;
    padding: 0.65rem;
    border: 1px solid #e2e8f1;
    border-radius: 16px;
    background: #ffffff;
    transition: border-color 0.18s ease, background-color 0.18s ease, transform 0.18s ease, box-shadow 0.18s ease;
    align-items: center;
    min-height: auto;
  }

  .messages-thread-item:hover {
    border-color: #c9d5e4;
    background: #fbfdff;
    transform: translateY(-1px);
    box-shadow: none;
  }

  
  .messages-thread-item.is-active {
    border-color: #b71c2d;
    background: #b71c2d;
    color: #ffffff;
    box-shadow: none;
  }

  .messages-thread-item.is-active .messages-thread-name {
    color: #ffffff;
  }

  .messages-thread-item.is-active .messages-thread-avatar {
    background: rgba(255, 255, 255, 0.2);
    color: #ffffff;
    border-color: rgba(255, 255, 255, 0.35);
  }

  .messages-thread-item.is-active .messages-thread-main {
    align-items: center;
  }

  .messages-thread-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.65rem;
    margin-bottom: 0;
  }

  .messages-thread-name {
    margin: 0;
    color: var(--messages-ink);
    font-size: 0.96rem;
    line-height: 1.25;
  }

  .messages-thread-time {
    color: #8a97a8;
    font-size: 0.75rem;
    white-space: nowrap;
  }

  .messages-thread-meta,
  .messages-thread-preview {
    margin: 0;
    font-size: 0.82rem;
    line-height: 1.35;
  }

  .messages-thread-meta {
    color: var(--messages-muted);
  }

  .messages-thread-preview {
    color: #2f3d4f;
  }

  .messages-chat-panel {
    display: grid;
    grid-template-rows: auto minmax(0, 1fr) auto;
    overflow: hidden;
    background: var(--messages-bg);
  }

  .messages-chat-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem 1.1rem;
    border-bottom: 1px solid var(--messages-line);
    background: #ffffff;
  }

  .messages-chat-head h2 {
    margin: 0;
    font-size: 1.26rem;
    color: var(--messages-ink);
  }

  .messages-chat-head p {
    margin: 0.28rem 0 0;
    color: var(--messages-muted);
  }

  .messages-chat-head-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.55rem;
    justify-content: flex-end;
  }

  .messages-chat-head-meta .panel-badge {
    border-radius: 999px;
  }

  .messages-chat-stream {
    overflow-y: auto;
    padding: 1.2rem 1.6rem;
    display: grid;
    gap: 0.7rem;
    align-content: start;
    justify-items: stretch;
    background: linear-gradient(180deg, #f7f9fc 0%, #eef3f8 100%);
    border-radius: 16px;
  }

  .messages-chat-date {
    justify-self: stretch;
    display: flex;
    align-items: center;
    gap: 0.8rem;
    color: #7a8797;
    font-size: 0.78rem;
    font-weight: 600;
  }

  .messages-chat-date::before,
  .messages-chat-date::after {
    content: '';
    height: 1px;
    flex: 1;
    background: #dfe6f1;
  }

  .messages-chat-date::before {
    margin-right: 0.6rem;
  }

  .messages-chat-date::after {
    margin-left: 0.6rem;
  }

  .messages-chat-date span {
    background: #f4f7fb;
    border: 1px solid #dfe6f1;
    border-radius: 999px;
    padding: 0.25rem 0.7rem;
    color: #6c7a8c;
  }

  .messages-chat-row {
    display: flex;
    align-items: flex-end;
    gap: 0.6rem;
    justify-content: flex-start;
  }

  .messages-chat-row.is-admin {
    flex-direction: row-reverse;
    justify-content: flex-end;
  }

  .messages-chat-body {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 0.25rem;
    max-width: min(520px, 60%);
  }

  .messages-chat-row.is-admin .messages-chat-body {
    align-items: flex-end;
  }

  

  .messages-chat-bubble {
    display: inline-block;
    width: auto;
    max-width: 100%;
    padding: 0.55rem 0.8rem;
    border-radius: 12px;
    border: 1px solid #dde5f0;
    background: #f3f6fb;
    color: var(--messages-ink);
    box-shadow: none;
    white-space: pre-wrap;
    word-break: break-word;
    line-height: 1.4;
  }

  .messages-chat-row.is-admin .messages-chat-bubble {
    border-color: #991525;
    background: #b71c2d;
    color: #ffffff;
    box-shadow: none;
  }

  .messages-chat-column {
    width: 100%;
    max-width: 100%;
    display: grid;
    gap: 0.7rem;
  }

  .messages-chat-note {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    color: #8b93a5;
    font-size: 0.72rem;
    padding: 0 0.1rem;
  }


  .messages-chat-row.is-admin .messages-chat-note {
    justify-content: flex-end;
  }

  .messages-chat-compose {
    padding: 1.1rem 1.2rem 1.25rem;
    border-top: 1px solid var(--messages-line);
    background: #ffffff;
    display: grid;
    gap: 0.9rem;
  }

  .messages-chat-compose-top {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 0.9rem;
    align-items: center;
  }

  .messages-compose-meta {
    display: grid;
    gap: 0.35rem;
    padding: 0.6rem 0.75rem;
    border-radius: 12px;
    background: #f5f7fb;
    border: 1px solid #dde5ef;
    color: var(--messages-muted);
    font-size: 0.84rem;
  }

  .messages-chat-compose .panel-textarea {
    min-height: 120px;
    border-radius: 16px;
    border-color: #d6dde7;
  }

  .messages-chat-compose-actions {
    display: flex;
    justify-content: flex-end;
    align-items: center;
  }

  .messages-chat-compose .panel-btn {
    min-width: 150px;
    height: 44px;
    padding: 0 1.2rem;
    border-radius: 12px;
    font-weight: 600;
  }

  .messages-empty-state {
    min-height: 320px;
    display: grid;
    place-items: center;
    text-align: center;
    padding: 2rem;
    color: var(--messages-muted);
  }

  .messages-empty-state strong {
    display: block;
    margin-bottom: 0.35rem;
    color: var(--messages-ink);
    font-size: 1.05rem;
  }

  @media (max-width: 1180px) {
    .messages-chat-layout {
      grid-template-columns: 320px minmax(0, 1fr);
    }

    .messages-chat-compose-top {
      grid-template-columns: 1fr;
    }
  }

  @media (max-width: 920px) {
    .messages-chat-layout {
      grid-template-columns: 1fr;
      min-height: auto;
    }

    .messages-thread-panel,
    .messages-chat-panel {
      min-height: auto;
    }

    .messages-thread-list {
      max-height: 360px;
    }

    .messages-chat-bubble {
    display: inline-block;
    width: auto;
    max-width: 100%;
    padding: 0.55rem 0.8rem;
    border-radius: 12px;
    border: 1px solid #dde5f0;
    background: #f3f6fb;
    color: var(--messages-ink);
    box-shadow: none;
    white-space: pre-wrap;
    word-break: break-word;
    line-height: 1.4;
  }
  .messages-chat-row.is-admin .messages-chat-bubble {
    border-color: #991525;
    background: #b71c2d;
    color: #ffffff;
    box-shadow: none;
  }

}</style>

<div class="messages-chat-shell">
  <div class="messages-chat-tabs">
    <?php if($can_view_all_chats ?? false): ?>
    <a class="messages-chat-tab<?php echo e($mode === 'clients' ? ' is-active' : ''); ?>" href="<?php echo e(route('admin.messages.index', ['mode' => 'clients'])); ?>">Client Threads</a>
    <?php endif; ?>
    <a class="messages-chat-tab<?php echo e($mode === 'users' ? ' is-active' : ''); ?>" href="<?php echo e(route('admin.messages.index', ['mode' => 'users'])); ?>">User Threads</a>
  </div>
  <section class="panel-grid panel-grid-kpi-compact">
    <article class="client-portal-kpi">
      <span class="panel-kpi-label">Total Messages</span>
      <p class="client-portal-kpi-value"><?php echo e(number_format((int) ($messageStats['total_messages'] ?? 0))); ?></p>
    </article>
    <article class="client-portal-kpi">
      <span class="panel-kpi-label"><?php echo e($statsLabels['threads'] ?? 'Client Threads'); ?></span>
      <p class="client-portal-kpi-value"><?php echo e(number_format((int) ($messageStats['client_threads'] ?? 0))); ?></p>
    </article>
    <article class="client-portal-kpi">
      <span class="panel-kpi-label">Admin Sent</span>
      <p class="client-portal-kpi-value"><?php echo e(number_format((int) ($messageStats['admin_sent'] ?? 0))); ?></p>
    </article>
    <article class="client-portal-kpi">
      <span class="panel-kpi-label"><?php echo e($statsLabels['client_sent'] ?? 'Client Sent'); ?></span>
      <p class="client-portal-kpi-value"><?php echo e(number_format((int) ($messageStats['client_sent'] ?? 0))); ?></p>
    </article>
  </section>

    <section class="panel-card messages-chat-layout">
    <aside class="messages-thread-panel">
      <div class="messages-thread-top">
        <div>
          <h2 class="messages-thread-title">Chats</h2>
          <p class="messages-thread-sub">Open any client conversation and continue the thread directly from CRM.</p>
        </div>
      </div>

      <form method="get" class="messages-thread-search">
        <input type="hidden" name="mode" value="<?php echo e($mode); ?>">
        <input class="panel-input" type="text" name="search" value="<?php echo e($filters['search'] ?? ''); ?>" placeholder="<?php echo e($mode === 'users' ? 'Search user name or email' : 'Search client, project, or message text'); ?>">
        <div class="panel-form-row" style="margin-bottom:0;">
          <?php if($mode === 'clients'): ?>
            <select class="panel-select" name="sender_role">
              <option value="">All senders</option>
              <option value="admin" <?php if(($filters['sender_role'] ?? '') === 'admin'): echo 'selected'; endif; ?>>Admin</option>
              <option value="client" <?php if(($filters['sender_role'] ?? '') === 'client'): echo 'selected'; endif; ?>>Client</option>
            </select>
          <?php endif; ?>
          <button class="panel-btn panel-btn-primary" type="submit">Filter</button>
          <a class="messages-thread-clear" href="<?php echo e(route('admin.messages.index', ['mode' => $mode])); ?>">Clear</a>
        </div>
      </form>

      <div class="messages-thread-list">
        <?php if($mode === 'users'): ?>
          <?php $__empty_1 = true; $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $userItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <a
              href="<?php echo e(route('admin.messages.index', ['mode' => 'users', 'user_id' => $userItem->id, 'search' => $filters['search'] ?? null])); ?>"
              class="messages-thread-item<?php echo e((int) ($activeUser?->id ?? 0) === (int) $userItem->id ? ' is-active' : ''); ?>"
            >
              <div class="messages-thread-avatar"><?php echo e(strtoupper(substr($userItem->name ?: 'U', 0, 2))); ?></div>
              <div class="messages-thread-main">
                <h3 class="messages-thread-name"><?php echo e($userItem->name ?: ('User #' . $userItem->id)); ?></h3>
              </div>
            </a>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <p class="panel-muted">No users available yet.</p>
          <?php endif; ?>
        <?php else: ?>
          <?php $__empty_1 = true; $__currentLoopData = $clients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $client): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <a
              href="<?php echo e(route('admin.messages.index', ['client_id' => $client->id, 'search' => $filters['search'] ?? null, 'sender_role' => $filters['sender_role'] ?? null])); ?>"
              class="messages-thread-item<?php echo e((int) ($activeClient?->id ?? 0) === (int) $client->id ? ' is-active' : ''); ?>"
            >
              <div class="messages-thread-avatar"><?php echo e(strtoupper(substr($client->name ?: 'C', 0, 2))); ?></div>
              <div class="messages-thread-main">
                <h3 class="messages-thread-name"><?php echo e($client->name ?: ('Client #' . $client->id)); ?></h3>
              </div>
            </a>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <p class="panel-muted">No clients available yet.</p>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </aside>

    <section class="messages-chat-panel">
      <?php if($mode === 'users'): ?>
        <?php if($activeUser): ?>
          <header class="messages-chat-head">
            <div>
              <h2><?php echo e($activeUser->name ?: ('User #' . $activeUser->id)); ?></h2>
              <p>
                <?php echo e($activeUser->email ?: 'No email on file'); ?>

                <?php if($activeUser->role): ?>
                  &bull; <?php echo e(ucfirst($activeUser->role)); ?>

                <?php endif; ?>
              </p>
            </div>
            <div class="messages-chat-head-meta">
              <span class="panel-badge"><?php echo e(ucfirst($activeUser->role ?: 'user')); ?></span>
            </div>
          </header>

          <div class="messages-chat-stream">
            <div class="messages-chat-column">
              <?php ($lastDate = null); ?>
              <?php $__empty_1 = true; $__currentLoopData = $activeMessages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $message): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php ($currentDate = optional($message->sent_at ?? $message->created_at)?->format('Y-m-d')); ?>
                <?php if($currentDate !== $lastDate): ?>
                  <div class="messages-chat-date"><span><?php echo e(optional($message->sent_at ?? $message->created_at)?->format('M j, Y')); ?></span></div>
                  <?php ($lastDate = $currentDate); ?>
                <?php endif; ?>
                <article class="messages-chat-row<?php echo e((int) $message->sender_user_id === (int) auth()->id() ? ' is-admin' : ''); ?>">
                  <div class="messages-chat-body">
                    <div class="messages-chat-bubble"><?php echo e($message->message); ?></div>
                    <div class="messages-chat-note">
                      <span><?php echo e($message->sender?->name ?: 'User'); ?></span>
                      <span>&bull;</span>
                      <span><?php echo e(optional($message->sent_at ?? $message->created_at)?->format('M j, g:i A')); ?></span>
                    </div>
                  </div>
                </article>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="messages-empty-state">
                  <div>
                    <strong>No messages yet</strong>
                    Start the first direct conversation with this user from the composer below.
                  </div>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <form method="post" action="<?php echo e(route('admin.user-messages.store')); ?>" class="messages-chat-compose">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="recipient_user_id" value="<?php echo e($activeUser->id); ?>">
            <div class="messages-chat-compose-top">
              <div class="panel-muted" style="display:grid;align-items:center;padding:0 0.25rem;">
                Send a direct message to this user. Keep it clear and actionable.
              </div>
              <div class="messages-chat-compose-actions">
                <button class="panel-btn panel-btn-primary" type="submit">Send Message</button>
              </div>
            </div>
            <textarea class="panel-textarea" name="message" placeholder="Write a clear message for this user" required><?php echo e(old('message')); ?></textarea>
          </form>
        <?php else: ?>
          <div class="messages-empty-state">
            <div>
              <strong>No user selected</strong>
              Pick a user from the left column to open the conversation workspace.
            </div>
          </div>
        <?php endif; ?>
      <?php elseif($activeClient): ?>
        <header class="messages-chat-head">
          <div>
            <h2><?php echo e($activeClient->name ?: ('Client #' . $activeClient->id)); ?></h2>
            <p>
              <?php echo e($activeClient->email ?: 'No email on file'); ?>

              <?php if($activeClient->company): ?>
                &bull; <?php echo e($activeClient->company); ?>

              <?php endif; ?>
              <?php if($activeClient->phone): ?>
                &bull; <?php echo e($activeClient->phone); ?>

              <?php endif; ?>
            </p>
          </div>
          <div class="messages-chat-head-meta">
            <span class="panel-badge"><?php echo e(ucfirst($activeClient->status ?: 'active')); ?></span>
            <a class="panel-btn" href="<?php echo e(route('admin.clients.show', $activeClient)); ?>">Open Client</a>
          </div>
        </header>

        <div class="messages-chat-stream">
          <div class="messages-chat-column">
            <?php ($lastDate = null); ?>
            <?php $__empty_1 = true; $__currentLoopData = $activeMessages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $message): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
              <?php ($currentDate = optional($message->sent_at ?? $message->created_at)?->format('Y-m-d')); ?>
              <?php if($currentDate !== $lastDate): ?>
                <div class="messages-chat-date"><span><?php echo e(optional($message->sent_at ?? $message->created_at)?->format('M j, Y')); ?></span></div>
                <?php ($lastDate = $currentDate); ?>
              <?php endif; ?>
              <article class="messages-chat-row<?php echo e($message->sender_role === 'admin' ? ' is-admin' : ''); ?>">
                <div class="messages-chat-body">
                  <div class="messages-chat-bubble"><?php echo e($message->message); ?></div>
                  <div class="messages-chat-note">
                    <span><?php echo e($message->sender?->name ?: strtoupper($message->sender_role)); ?></span>
                    <?php if($message->project?->title): ?>
                      <span>&bull;</span>
                      <span><?php echo e($message->project->title); ?></span>
                    <?php endif; ?>
                    <span>&bull;</span>
                    <span><?php echo e(optional($message->sent_at ?? $message->created_at)?->format('M j, g:i A')); ?></span>
                  </div>
                </div>
              </article>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
              <div class="messages-empty-state">
                <div>
                  <strong>No messages yet</strong>
                  Start the first direct conversation with this client from the composer below.
                </div>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <form method="post" action="<?php echo e(route('admin.messages.store')); ?>" class="messages-chat-compose">
          <?php echo csrf_field(); ?>
          <input type="hidden" name="client_id" value="<?php echo e($activeClient->id); ?>">
          <div class="messages-chat-compose-top">
            <div class="messages-compose-meta">
              <strong style="color: var(--messages-ink);">Message context</strong>
              Optional project context helps keep threads organized.
            </div>
            <div class="messages-chat-compose-actions">
              <button class="panel-btn panel-btn-primary" type="submit">Send Message</button>
            </div>
          </div>
          <select class="panel-select" name="client_project_id">
            <option value="">General client message</option>
            <?php $__currentLoopData = $activeClient->projects; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $project): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <option value="<?php echo e($project->id); ?>" <?php if((int) old('client_project_id', 0) === (int) $project->id): echo 'selected'; endif; ?>><?php echo e($project->title); ?> <?php if($project->status): ?> - <?php echo e(ucfirst($project->status)); ?> <?php endif; ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </select>
          <textarea class="panel-textarea" name="message" placeholder="Write a clear message for the client thread" required><?php echo e(old('message')); ?></textarea>
        </form>
      <?php else: ?>
        <div class="messages-empty-state">
          <div>
            <strong>No client selected</strong>
            Pick a client from the left column to open the conversation workspace.
          </div>
        </div>
      <?php endif; ?>
    </section>
  </section>
<?php $__env->stopSection(); ?>






























































<?php echo $__env->make('layouts.panel', [
  'title' => 'User Messages',
  'heading' => 'User Messages',
  'subheading' => 'Direct CRM messaging workspace for client conversations, updates, and follow-up communication.',
], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/asifk/projects/maccento/resources/views/admin/client-messages-index.blade.php ENDPATH**/ ?>