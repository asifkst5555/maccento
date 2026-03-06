@extends('layouts.panel', [
  'title' => 'Projects',
  'heading' => 'Project Workspace',
  'subheading' => 'Monitor ongoing work, completed delivery, deadlines, and status flow in one panel.',
])

@section('content')
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
  <div class="panel-sticky-filters">
    <form method="get" class="panel-form-row">
      <input type="hidden" name="project_view" value="{{ $filters['project_view'] }}">
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
    <div class="panel-form-row" style="margin-bottom:0;">
      <a class="panel-btn {{ $filters['project_view'] === 'table' ? 'panel-btn-primary' : '' }}" href="{{ route('admin.projects.index', array_merge(request()->query(), ['project_view' => 'table'])) }}">Table View</a>
      <a class="panel-btn {{ $filters['project_view'] === 'kanban' ? 'panel-btn-primary' : '' }}" href="{{ route('admin.projects.index', array_merge(request()->query(), ['project_view' => 'kanban'])) }}">Kanban View</a>
      <a class="panel-btn" href="{{ route('admin.media-delivery.index') }}">Open Media Delivery</a>
    </div>
  </div>

  @if($filters['project_view'] === 'kanban')
  @php
    $kanbanByStatus = $kanbanProjects->groupBy('status');
  @endphp
  <div class="panel-kanban-board">
    @foreach($projectStatuses as $statusColumn)
    @php
      $columnProjects = $kanbanByStatus->get($statusColumn, collect());
    @endphp
    <div class="panel-kanban-col">
      <div class="panel-kanban-col-head">
        <h3>{{ ucfirst($statusColumn) }}</h3>
        <span class="panel-badge">{{ $columnProjects->count() }}</span>
      </div>
      <div class="panel-kanban-list">
        @forelse($columnProjects as $project)
        @php
          $isOverdue = in_array($project->status, ['accepted', 'shooting', 'editing'], true) && $project->due_at && $project->due_at->isPast();
        @endphp
        <article class="panel-kanban-card {{ $isOverdue ? 'is-overdue' : '' }}">
          <p class="panel-kanban-title">{{ $project->title }}</p>
          <p class="panel-muted">{{ $project->service_type ?: '-' }}</p>
          <p class="panel-muted">{{ $project->client?->name ?: ('Client #' . $project->client_id) }}</p>
          <p class="panel-muted">Due: {{ $project->due_at?->format('Y-m-d H:i') ?: '-' }}</p>
          @if($canManageProjects)
          <form method="post" action="{{ route('admin.projects.status', $project) }}" class="panel-form-row" style="margin-top:8px; margin-bottom:0;">
            @csrf
            <select class="panel-select" name="status">
              @foreach($projectStatuses as $status)
              <option value="{{ $status }}" @selected($project->status === $status)>{{ ucfirst($status) }}</option>
              @endforeach
            </select>
            <button class="panel-btn" type="submit">Save</button>
          </form>
          @endif
          @if($project->client)
          <a class="panel-link" href="{{ route('admin.clients.show', $project->client) }}">Open client</a>
          @endif
        </article>
        @empty
        <p class="panel-muted">No projects in this stage.</p>
        @endforelse
      </div>
    </div>
    @endforeach
  </div>
  @else
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
            <form method="post" action="{{ route('admin.projects.status', $project) }}" class="panel-form-row" style="margin-bottom:6px;">
              @csrf
              <select class="panel-select" name="status">
                @foreach($projectStatuses as $status)
                <option value="{{ $status }}" @selected($project->status === $status)>{{ ucfirst($status) }}</option>
                @endforeach
              </select>
              <button class="panel-btn" type="submit">Save</button>
            </form>
            @else
            <span class="panel-muted">Read only</span>
            @endif
            @if($project->client)
            <a class="panel-link" href="{{ route('admin.clients.show', $project->client) }}">Open client</a>
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
@endsection
