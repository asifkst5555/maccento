

<?php $__env->startSection('content'); ?>
<style>
  .corp-admin-shell {
    --corp-ink: #10233a;
    --corp-ink-soft: #586b83;
    --corp-line: #d6e0ec;
    --corp-surface: #ffffff;
    --corp-soft: #f3f7fc;
    --corp-accent: #c11f37;
    --corp-shadow: 0 14px 30px rgba(16, 35, 58, 0.08);
  }

  .corp-admin-shell .panel-card {
    border: 1px solid var(--corp-line);
    border-radius: 14px;
    background: var(--corp-surface);
    box-shadow: var(--corp-shadow);
  }

  .corp-admin-shell .panel-section-title,
  .corp-admin-shell .panel-kanban-col-head h3,
  .corp-admin-shell .panel-kanban-title {
    color: var(--corp-ink);
  }

  .corp-admin-shell .panel-kpi-label,
  .corp-admin-shell .panel-muted {
    color: var(--corp-ink-soft);
  }

  .corp-admin-shell .panel-kpi-label {
    font-weight: 700;
    letter-spacing: 0.03em;
    text-transform: uppercase;
    font-size: 0.72rem;
  }

  .corp-admin-shell .panel-link {
    color: #143557;
    font-weight: 600;
    text-decoration: none;
  }

  .corp-admin-shell .panel-link:hover {
    color: var(--corp-accent);
  }

  .corp-admin-shell .panel-input,
  .corp-admin-shell .panel-select,
  .corp-admin-shell .panel-textarea {
    border-radius: 10px;
    border: 1px solid #c9d6e5;
    background-color: #fff;
  }

  .corp-admin-shell .panel-select {
    background-position: right 19px center !important;
    padding-right: 42px !important;
    background-size: 18px 18px !important;
  }

  .corp-admin-shell .panel-multi {
    border: 1px solid #c9d6e5;
    border-radius: 10px;
    padding: 0.65rem;
    background: #ffffff;
    display: grid;
    gap: 0.45rem;
    max-height: 220px;
    overflow: auto;
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .corp-admin-shell .panel-multi label {
    display: grid;
    grid-template-columns: 18px minmax(0, 1fr);
    gap: 0.55rem;
    align-items: center;
    padding: 0.4rem 0.55rem;
    border-radius: 8px;
    border: 1px solid transparent;
    background: #f8fbff;
    color: var(--corp-ink);
    font-weight: 600;
    font-size: 0.88rem;
  }

  @media (max-width: 960px) {
    .corp-admin-shell .panel-multi {
      grid-template-columns: minmax(0, 1fr);
    }
  }

  .corp-admin-shell .project-dates-row {
    align-items: flex-end;
  }

  .corp-admin-shell .project-dates-row > .panel-stack {
    flex: 1 1 0;
  }

  .corp-admin-shell .panel-multi label:hover {
    border-color: #cfd9e6;
  }

  .corp-admin-shell .panel-multi input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: var(--corp-accent);
  }

  .corp-admin-shell .panel-btn {
    border-radius: 10px;
    border: 1px solid #bfcfe0;
    font-weight: 600;
  }

  .corp-admin-shell .panel-btn-primary {
    background: linear-gradient(90deg, #b71d34 0%, #cc2741 100%);
    border-color: #a5172d;
  }

    .corp-admin-shell .panel-btn-icon {
    width: 48px;
    height: 48px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    border: 1px solid #f0c7cd;
    background: #fff5f6;
    color: #b71d34;
  }

  .corp-admin-shell .panel-btn-icon svg {
    width: 32px !important;
    height: 32px !important;
  }

  .corp-admin-shell .panel-btn-icon:hover {
    background: #fdecee;
    border-color: #e7aeb8;
  }

  .panel-modal {
    position: fixed;
    inset: 0;
    display: none;
    align-items: center;
    justify-content: center;
    background: rgba(12, 21, 33, 0.45);
    z-index: 1200;
    padding: 1.5rem;
  }

  .panel-modal.is-open {
    display: flex;
  }

  .panel-modal-card {
    width: min(520px, 92vw);
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid #d7e2ef;
    box-shadow: 0 24px 60px rgba(12, 21, 33, 0.18);
    padding: 1.2rem 1.35rem;
    display: grid;
    gap: 0.85rem;
  }

  .panel-modal-title {
    margin: 0;
    font-size: 1.15rem;
    color: var(--corp-ink);
  }

  .panel-modal-body {
    margin: 0;
    color: var(--corp-ink-soft);
    line-height: 1.5;
  }

  .panel-modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.6rem;
  }

  .corp-admin-shell .panel-btn-danger {
    background: #b71d34;
    border-color: #9f162b;
    color: #ffffff;
  }

  .corp-admin-shell .panel-sticky-filters {
    background: linear-gradient(180deg, #f8fbff 0%, #eef4fb 100%);
    border: 1px solid #d9e3ef;
    border-radius: 12px;
    padding: 0.7rem;
  }

  .corp-admin-shell .panel-table-wrap {
    border: 1px solid var(--corp-line);
    border-radius: 12px;
    overflow: hidden;
    background: #fff;
    overflow-x: auto;
    padding-bottom: 0.4rem;
  }

  .corp-admin-shell .panel-table {
    min-width: 100%;
  }

  .corp-admin-shell .panel-table thead th {
    background: var(--corp-soft);
    color: #324963;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    font-size: 0.74rem;
  }

  .corp-admin-shell .panel-table tbody tr:nth-child(even) {
    background: #fbfdff;
  }

  .corp-admin-shell .panel-badge {
    border-radius: 999px;
    border: 1px solid #c5d3e3;
    background: #eff5fc;
    color: #203b59;
    font-weight: 700;
    font-size: 0.7rem;
    letter-spacing: 0.02em;
  }

  .corp-admin-shell .panel-kanban-col {
    border: 1px solid #d5e1ef;
    background: linear-gradient(180deg, #f9fbff 0%, #f2f6fc 100%);
    border-radius: 12px;
  }

  .corp-admin-shell .panel-kanban-card {
    border: 1px solid #d2ddeb;
    border-radius: 10px;
    background: #fff;
    box-shadow: 0 6px 16px rgba(16, 35, 58, 0.06);
  }

  .corp-admin-shell .panel-row-overdue,
  .corp-admin-shell .panel-kanban-card.is-overdue {
    background: #fff7f8;
  }

  .corp-admin-shell .create-project-card {
    margin-bottom: 0.9rem;
  }

  .corp-admin-shell .row-no-margin {
    margin-bottom: 0;
  }

  .corp-admin-shell .kanban-status-form {
    margin-top: 8px;
    margin-bottom: 0;
  }

  .corp-admin-shell .status-form-tight {
    margin-bottom: 6px;
  }

  .corp-admin-shell .project-create-assignment-block {
    gap: 0.7rem;
    padding: 0.9rem 1rem;
    border: 1px solid #d8e2ef;
    border-radius: 12px;
    background: linear-gradient(180deg, #fbfdff 0%, #f3f7fc 100%);
  }

  .corp-admin-shell .project-team-select {
    min-height: 154px;
    padding-right: 14px !important;
    background-image: none !important;
  }

  .corp-admin-shell .project-team-select option {
    padding: 0.55rem 0.7rem;
  }

  @media (max-width: 1024px) {
    .corp-admin-shell .panel-sticky-filters {
      position: static;
    }
  }

  @media (max-width: 640px) {
    .corp-admin-shell .panel-card {
      border-radius: 12px;
    }

    .corp-admin-shell .panel-sticky-filters {
      padding: 0.65rem;
    }

    .corp-admin-shell .panel-sticky-filters .panel-form-row,
    .corp-admin-shell .create-project-card .panel-form-row {
      align-items: stretch;
    }

    .corp-admin-shell .panel-sticky-filters .panel-btn,
    .corp-admin-shell .panel-sticky-filters .panel-link,
    .corp-admin-shell .create-project-card .panel-btn,
    .corp-admin-shell .create-project-card .panel-link {
      width: 100%;
      justify-content: center;
    }

    .corp-admin-shell .panel-table td[data-label="Action"] .panel-form-row {
      flex-direction: column;
      align-items: stretch;
    }

    .corp-admin-shell .panel-table td[data-label="Action"] .panel-select,
    .corp-admin-shell .panel-table td[data-label="Action"] .panel-btn,
    .corp-admin-shell .panel-table td[data-label="Action"] .panel-link {
      width: 100%;
    }

    .corp-admin-shell .panel-table td[data-label="Project"],
    .corp-admin-shell .panel-table td[data-label="Client"] {
      font-weight: 600;
    }
  }
</style>
<div class="corp-admin-shell">
<section class="panel-grid panel-grid-kpi">
  <article class="panel-card">
    <span class="panel-kpi-label">Total projects</span>
    <p class="panel-kpi-value"><?php echo e($kpi['total_projects']); ?></p>
  </article>
  <article class="panel-card">
    <span class="panel-kpi-label">Ongoing</span>
    <p class="panel-kpi-value"><?php echo e($kpi['ongoing_projects']); ?></p>
  </article>
  <article class="panel-card">
    <span class="panel-kpi-label">Completed</span>
    <p class="panel-kpi-value"><?php echo e($kpi['completed_projects']); ?></p>
  </article>
  <article class="panel-card">
    <span class="panel-kpi-label">Due in 7 days</span>
    <p class="panel-kpi-value"><?php echo e($kpi['due_this_week']); ?></p>
  </article>
  <article class="panel-card">
    <span class="panel-kpi-label">Overdue</span>
    <p class="panel-kpi-value"><?php echo e($kpi['overdue_projects']); ?></p>
  </article>
</section>

<section class="panel-card">
  <?php if($canManageProjects && $filters['project_action'] === 'create'): ?>
  <article class="panel-card create-project-card">
    <h2 class="panel-section-title">Create New Project</h2>
    <form method="post" action="<?php echo e(route('admin.projects.store')); ?>" class="panel-stack">
      <?php echo csrf_field(); ?>
      <div class="panel-form-row">
        <select class="panel-select" name="client_id" required>
          <option value="">Select client</option>
          <?php $__currentLoopData = $projectClients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $client): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <option value="<?php echo e($client->id); ?>" <?php if((int) old('client_id') === (int) $client->id): echo 'selected'; endif; ?>>
            <?php echo e($client->name); ?><?php echo e($client->email ? ' (' . $client->email . ')' : ''); ?>

          </option>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <input class="panel-input" type="text" name="title" value="<?php echo e(old('title')); ?>" placeholder="Project title" required>
      </div>
      <div class="panel-form-row">
        <input class="panel-input" type="text" name="service_type" value="<?php echo e(old('service_type')); ?>" placeholder="Service type">
        <input class="panel-input" type="text" name="property_address" value="<?php echo e(old('property_address')); ?>" placeholder="Property address">
      </div>
      <div class="panel-form-row project-dates-row">
        <div class="panel-stack" style="gap:6px;">
          <label class="panel-muted" for="scheduled_at">Start date</label>
          <input id="scheduled_at" class="panel-input" type="datetime-local" name="scheduled_at" value="<?php echo e(old('scheduled_at')); ?>">
        </div>
        <div class="panel-stack" style="gap:6px;">
          <label class="panel-muted" for="due_at">End date / deadline</label>
          <input id="due_at" class="panel-input" type="datetime-local" name="due_at" value="<?php echo e(old('due_at')); ?>">
        </div>
        <div class="panel-stack" style="gap:6px;">
          <label class="panel-muted" for="project_status">Status</label>
          <select id="project_status" class="panel-select" name="status" required>
            <?php $__currentLoopData = $projectStatuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($status); ?>" <?php if(old('status', 'accepted') === $status): echo 'selected'; endif; ?>><?php echo e(ucfirst($status)); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </select>
        </div>
      </div>
      <div class="panel-stack project-create-assignment-block">
        <div>
          <h3 class="panel-section-title" style="margin-bottom: 6px;">Assign Project Team</h3>
          <p class="panel-muted" style="margin: 0;">Optional. Assign one or more internal users like manager, photographer, or editor right away.</p>
        </div>
        <div class="panel-multi">
          <?php $__currentLoopData = $assignableUsers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $assignableUser): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php ($isChecked = collect(old('assigned_user_ids', []))->contains((string) $assignableUser->id) || collect(old('assigned_user_ids', []))->contains($assignableUser->id)); ?>
            <label>
              <input type="checkbox" name="assigned_user_ids[]" value="<?php echo e($assignableUser->id); ?>" <?php if($isChecked): echo 'checked'; endif; ?>>
              <span><?php echo e($assignableUser->name); ?> <?php if($assignableUser->role): ?> - <?php echo e(ucfirst($assignableUser->role)); ?> <?php endif; ?></span>
            </label>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
      </div>
      <textarea class="panel-textarea" name="notes" placeholder="Project notes"><?php echo e(old('notes')); ?></textarea>
      <div class="panel-form-row row-no-margin">
        <button class="panel-btn panel-btn-primary" type="submit">Create Project</button>
        <a class="panel-link" href="<?php echo e(route('admin.projects.index', ['project_scope' => $filters['project_scope'], 'project_view' => $filters['project_view']])); ?>">Cancel</a>
      </div>
    </form>
  </article>
<?php endif; ?>
  <?php if($filters['project_action'] !== 'create'): ?>
  <div class="panel-sticky-filters">
    <form method="get" class="panel-form-row">
      <input type="hidden" name="project_view" value="<?php echo e($filters['project_view']); ?>">
      <input type="hidden" name="project_action" value="<?php echo e($filters['project_action']); ?>">
      <select class="panel-select" name="project_scope">
        <option value="ongoing" <?php if($filters['project_scope'] === 'ongoing'): echo 'selected'; endif; ?>>Ongoing projects</option>
        <option value="past" <?php if($filters['project_scope'] === 'past'): echo 'selected'; endif; ?>>Past / Completed projects</option>
        <option value="all" <?php if($filters['project_scope'] === 'all'): echo 'selected'; endif; ?>>All projects</option>
      </select>
      <select class="panel-select" name="project_status">
        <option value="">All statuses</option>
        <?php $__currentLoopData = $projectStatuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <option value="<?php echo e($status); ?>" <?php if($filters['project_status'] === $status): echo 'selected'; endif; ?>><?php echo e(ucfirst($status)); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </select>
      <input class="panel-input" type="text" name="project_search" value="<?php echo e($filters['project_search']); ?>" placeholder="Search title/service/address/client">
      <button class="panel-btn panel-btn-primary" type="submit">Filter</button>
      <a class="panel-link" href="<?php echo e(route('admin.projects.index')); ?>">Clear</a>
    </form>
    <div class="panel-form-row row-no-margin">
      <a class="panel-btn panel-btn-primary" href="<?php echo e(route('admin.projects.index', array_merge(request()->query(), ['project_view' => 'table']))); ?>">Table View</a>
      <a class="panel-btn" href="<?php echo e(route('admin.media-delivery.index')); ?>">Open Media Delivery</a>
    </div>
  </div>
  <div class="panel-table-wrap">
    <table class="panel-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Project</th>
          <th>Client</th>
          <th>Service</th>
          <th>Schedule</th>
          <th>Due</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $projects; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $project): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <tr class="<?php echo e((in_array($project->status, ['accepted', 'shooting', 'editing'], true) && $project->due_at && $project->due_at->isPast()) ? 'panel-row-overdue' : ''); ?>">
          <td>#<?php echo e($project->id); ?></td>
          <td>
            <?php echo e($project->title); ?><br>
            <span class="panel-muted"><?php echo e($project->property_address ?: '-'); ?></span>
          </td>
          <td>
            <?php echo e($project->client?->name ?: ('Client #' . $project->client_id)); ?><br>
            <span class="panel-muted"><?php echo e($project->client?->email ?: ($project->client?->phone ?: '-')); ?></span>
          </td>
          <td><?php echo e($project->service_type ?: '-'); ?></td>
          <td><?php echo e($project->scheduled_at?->format('Y-m-d H:i') ?: '-'); ?></td>
          <td><?php echo e($project->due_at?->format('Y-m-d H:i') ?: '-'); ?></td>
          <td><span class="panel-badge"><?php echo e($project->status); ?></span></td>
          <td>
            <?php if($canManageProjects): ?>
            <form method="post" action="<?php echo e(route('admin.projects.status', $project)); ?>" class="panel-form-row status-form-tight">
              <?php echo csrf_field(); ?>
              <select class="panel-select" name="status">
                <?php $__currentLoopData = $projectStatuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($status); ?>" <?php if($project->status === $status): echo 'selected'; endif; ?>><?php echo e(ucfirst($status)); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
              </select>
              <button class="panel-btn panel-btn-primary" type="submit">Save</button>
              <?php if($canDeleteProjects): ?>
              <button class="panel-btn panel-btn-icon" type="submit" form="delete-project-<?php echo e($project->id); ?>" title="Delete project" aria-label="Delete project">
                <?php if (isset($component)) { $__componentOriginal669c17867d57615948fae15a035429b3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal669c17867d57615948fae15a035429b3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.panel-icon','data' => ['name' => 'trash','class' => 'panel-delete-icon']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('panel-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'trash','class' => 'panel-delete-icon']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal669c17867d57615948fae15a035429b3)): ?>
<?php $attributes = $__attributesOriginal669c17867d57615948fae15a035429b3; ?>
<?php unset($__attributesOriginal669c17867d57615948fae15a035429b3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal669c17867d57615948fae15a035429b3)): ?>
<?php $component = $__componentOriginal669c17867d57615948fae15a035429b3; ?>
<?php unset($__componentOriginal669c17867d57615948fae15a035429b3); ?>
<?php endif; ?>
              </button>
              <?php endif; ?>
            </form>
            <?php if($canDeleteProjects): ?>
            <form id="delete-project-<?php echo e($project->id); ?>" method="post" action="<?php echo e(route('admin.projects.delete', $project)); ?>" data-confirm="Delete this project? This will remove team assignments, media, and related data. This cannot be undone.">
              <?php echo csrf_field(); ?>
            </form>
            <?php endif; ?>
            <?php else: ?>
            <span class="panel-muted">Read only</span>
            <?php endif; ?>
            <?php if($project->client): ?>
            <a class="panel-link" href="<?php echo e(route('admin.clients.show', ['client' => $project->client, 'project_id' => $project->id])); ?>">Open project</a>
            <a class="panel-link" href="<?php echo e(route('admin.clients.show', $project->client)); ?>">Open client</a>
            <?php endif; ?>
            <?php if($canManageProjects): ?>
            <a class="panel-link" href="<?php echo e(route('admin.invoices.index', ['invoice_project' => $project->id])); ?>">Project Invoice</a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <tr><td colspan="8" class="panel-muted">No projects found for this filter.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if (isset($component)) { $__componentOriginal9f9d3eae18f18ccf28f34f84596c1d92 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9f9d3eae18f18ccf28f34f84596c1d92 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.panel-pagination','data' => ['paginator' => $projects]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('panel-pagination'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['paginator' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($projects)]); ?>
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
<?php endif; ?>

</section>

  
<?php $__env->stopSection(); ?>





























<?php echo $__env->make('layouts.panel', [
  'title' => 'Projects',
  'heading' => 'Project Workspace',
  'subheading' => 'Monitor ongoing work, completed delivery, deadlines, and status flow in one panel.',
], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/asifk/projects/maccento/resources/views/admin/projects-index.blade.php ENDPATH**/ ?>