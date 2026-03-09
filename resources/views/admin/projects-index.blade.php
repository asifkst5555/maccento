@extends('layouts.panel', [
  'title' => 'Projects',
  'heading' => 'Project Workspace',
  'subheading' => 'Monitor ongoing work, completed delivery, deadlines, and status flow in one panel.',
])

@section('content')
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

  .corp-admin-shell .panel-btn {
    border-radius: 10px;
    border: 1px solid #bfcfe0;
    font-weight: 600;
  }

  .corp-admin-shell .panel-btn-primary {
    background: linear-gradient(90deg, #b71d34 0%, #cc2741 100%);
    border-color: #a5172d;
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
    <p class="panel-kpi-value">{{ $kpi['total_projects'] }}</p>
  </article>
  <article class="panel-card">
    <span class="panel-kpi-label">Ongoing</span>
    <p class="panel-kpi-value">{{ $kpi['ongoing_projects'] }}</p>
  </article>
  <article class="panel-card">
    <span class="panel-kpi-label">Completed</span>
    <p class="panel-kpi-value">{{ $kpi['completed_projects'] }}</p>
  </article>
  <article class="panel-card">
    <span class="panel-kpi-label">Due in 7 days</span>
    <p class="panel-kpi-value">{{ $kpi['due_this_week'] }}</p>
  </article>
  <article class="panel-card">
    <span class="panel-kpi-label">Overdue</span>
    <p class="panel-kpi-value">{{ $kpi['overdue_projects'] }}</p>
  </article>
</section>

<section class="panel-card">
  @if($canManageProjects && $filters['project_action'] === 'create')
  <article class="panel-card create-project-card">
    <h2 class="panel-section-title">Create New Project</h2>
    <form method="post" action="{{ route('admin.projects.store') }}" class="panel-stack">
      @csrf
      <div class="panel-form-row">
        <select class="panel-select" name="client_id" required>
          <option value="">Select client</option>
          @foreach($projectClients as $client)
          <option value="{{ $client->id }}" @selected((int) old('client_id') === (int) $client->id)>
            {{ $client->name }}{{ $client->email ? ' (' . $client->email . ')' : '' }}
          </option>
          @endforeach
        </select>
        <input class="panel-input" type="text" name="title" value="{{ old('title') }}" placeholder="Project title" required>
      </div>
      <div class="panel-form-row">
        <input class="panel-input" type="text" name="service_type" value="{{ old('service_type') }}" placeholder="Service type">
        <input class="panel-input" type="text" name="property_address" value="{{ old('property_address') }}" placeholder="Property address">
      </div>
      <div class="panel-form-row">
        <input class="panel-input" type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at') }}">
        <input class="panel-input" type="datetime-local" name="due_at" value="{{ old('due_at') }}">
        <select class="panel-select" name="status" required>
          @foreach($projectStatuses as $status)
          <option value="{{ $status }}" @selected(old('status', 'accepted') === $status)>{{ ucfirst($status) }}</option>
          @endforeach
        </select>
      </div>
      <div class="panel-stack project-create-assignment-block">
        <div>
          <h3 class="panel-section-title" style="margin-bottom: 6px;">Assign Project Team</h3>
          <p class="panel-muted" style="margin: 0;">Optional. Assign one or more internal users like manager, photographer, or editor right away.</p>
        </div>
        <select class="panel-select project-team-select" name="assigned_user_ids[]" multiple size="6">
          @foreach($assignableUsers as $assignableUser)
          <option value="{{ $assignableUser->id }}" @selected(collect(old('assigned_user_ids', []))->contains((string) $assignableUser->id) || collect(old('assigned_user_ids', []))->contains($assignableUser->id))>
            {{ $assignableUser->name }} @if($assignableUser->role) - {{ ucfirst($assignableUser->role) }} @endif
          </option>
          @endforeach
        </select>
      </div>
      <textarea class="panel-textarea" name="notes" placeholder="Project notes">{{ old('notes') }}</textarea>
      <div class="panel-form-row row-no-margin">
        <button class="panel-btn panel-btn-primary" type="submit">Create Project</button>
        <a class="panel-link" href="{{ route('admin.projects.index', ['project_scope' => $filters['project_scope'], 'project_view' => $filters['project_view']]) }}">Cancel</a>
      </div>
    </form>
  </article>
  @endif

  @if($filters['project_action'] !== 'create')
  <div class="panel-sticky-filters">
    <form method="get" class="panel-form-row">
      <input type="hidden" name="project_view" value="{{ $filters['project_view'] }}">
      <input type="hidden" name="project_action" value="{{ $filters['project_action'] }}">
      <select class="panel-select" name="project_scope">
        <option value="ongoing" @selected($filters['project_scope'] === 'ongoing')>Ongoing projects</option>
        <option value="past" @selected($filters['project_scope'] === 'past')>Past / Completed projects</option>
        <option value="all" @selected($filters['project_scope'] === 'all')>All projects</option>
      </select>
      <select class="panel-select" name="project_status">
        <option value="">All statuses</option>
        @foreach($projectStatuses as $status)
        <option value="{{ $status }}" @selected($filters['project_status'] === $status)>{{ ucfirst($status) }}</option>
        @endforeach
      </select>
      <input class="panel-input" type="text" name="project_search" value="{{ $filters['project_search'] }}" placeholder="Search title/service/address/client">
      <button class="panel-btn panel-btn-primary" type="submit">Filter</button>
      <a class="panel-link" href="{{ route('admin.projects.index') }}">Clear</a>
    </form>
    <div class="panel-form-row row-no-margin">
      <a class="panel-btn panel-btn-primary" href="{{ route('admin.projects.index', array_merge(request()->query(), ['project_view' => 'table'])) }}">Table View</a>
      <a class="panel-btn" href="{{ route('admin.media-delivery.index') }}">Open Media Delivery</a>
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
        @forelse($projects as $project)
        @php
          $isOverdue = in_array($project->status, ['accepted', 'shooting', 'editing'], true) && $project->due_at && $project->due_at->isPast();
        @endphp
        <tr class="{{ $isOverdue ? 'panel-row-overdue' : '' }}">
          <td>#{{ $project->id }}</td>
          <td>
            {{ $project->title }}<br>
            <span class="panel-muted">{{ $project->property_address ?: '-' }}</span>
          </td>
          <td>
            {{ $project->client?->name ?: ('Client #' . $project->client_id) }}<br>
            <span class="panel-muted">{{ $project->client?->email ?: ($project->client?->phone ?: '-') }}</span>
          </td>
          <td>{{ $project->service_type ?: '-' }}</td>
          <td>{{ $project->scheduled_at?->format('Y-m-d H:i') ?: '-' }}</td>
          <td>{{ $project->due_at?->format('Y-m-d H:i') ?: '-' }}</td>
          <td><span class="panel-badge">{{ $project->status }}</span></td>
          <td>
            @if($canManageProjects)
            <form method="post" action="{{ route('admin.projects.status', $project) }}" class="panel-form-row status-form-tight">
              @csrf
              <select class="panel-select" name="status">
                @foreach($projectStatuses as $status)
                <option value="{{ $status }}" @selected($project->status === $status)>{{ ucfirst($status) }}</option>
                @endforeach
              </select>
              <button class="panel-btn panel-btn-primary" type="submit">Save</button>
            </form>
            @else
            <span class="panel-muted">Read only</span>
            @endif
            @if($project->client)
            <a class="panel-link" href="{{ route('admin.clients.show', ['client' => $project->client, 'project_id' => $project->id]) }}">Open project</a>
            <a class="panel-link" href="{{ route('admin.clients.show', $project->client) }}">Open client</a>
            @endif
            @if($canManageProjects)
            <a class="panel-link" href="{{ route('admin.invoices.index', ['invoice_project' => $project->id]) }}">Project Invoice</a>
            @endif
          </td>
        </tr>
        @empty
        <tr><td colspan="8" class="panel-muted">No projects found for this filter.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <x-panel-pagination :paginator="$projects" />
  @endif
</section>
</div>
@endsection
