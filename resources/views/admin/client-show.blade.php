@extends('layouts.panel', [
  'title' => 'Client #' . $client->id,
  'heading' => $client->name,
  'subheading' => 'Simple project list for this client.',
])

@section('content')
  <style>
    .corp-client-shell .panel-btn {
      border-radius: 10px;
      border: 1px solid #bfcfe0;
      font-weight: 600;
    }

    .corp-client-shell .panel-btn-primary {
      background: linear-gradient(90deg, #b71d34 0%, #cc2741 100%);
      border-color: #a5172d;
      color: #ffffff;
    }

    .corp-client-shell .panel-btn-icon {
      width: 35px;
      height: 35px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }

    .corp-client-shell .panel-btn-icon svg {
      width: 18px !important;
      height: 18px !important;
    }

    .corp-client-shell .panel-btn-danger.panel-btn-icon {
      background: #b71d34;
      border-color: #9f162b;
      color: #ffffff;
    }

    .corp-client-shell .panel-btn-danger.panel-btn-icon:hover {
      transform: scale(1.08);
      background: #b71d34;
      border-color: #9f162b;
      color: #ffffff;
    }

    .corp-client-shell .client-action-stack {
      display: grid;
      gap: 6px;
      justify-items: start;
    }

    .corp-client-shell .client-action-row {
      display: flex;
      gap: 8px;
      align-items: center;
      flex-wrap: wrap;
    }
  </style>
  <div class="corp-client-shell">
  @php
    $crmRole = strtolower(trim((string) auth()->user()?->role));
    $canManageProjects = in_array($crmRole, ['owner', 'admin', 'manager'], true);
    $canDeleteProjects = in_array($crmRole, ['owner', 'admin'], true);
    $projects = $client->projects;
    $activeProjects = $projects->whereIn('status', ['accepted', 'shooting', 'editing'])->count();
    $completedProjects = $projects->where('status', 'complete')->count();
    $openInvoices = $projects->sum(fn ($project) => $project->invoices->where('status', '!=', 'paid')->count());
  @endphp

  <section class="panel-card" style="display:grid; gap:16px;">
    <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:16px; flex-wrap:wrap;">
      <div>
        <h2 class="panel-section-title" style="margin-bottom:6px;">Client Projects</h2>
        <p class="panel-muted" style="margin:0;">Only the project list is shown here so the client page stays easy to understand.</p>
      </div>
      <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <a class="panel-btn" href="{{ route('admin.clients.index') }}">Back to Clients</a>
        <a class="panel-btn" href="{{ route('admin.clients.export', $client) }}">Export Client Data</a>
        <form method="post" action="{{ route('admin.clients.anonymize', $client) }}" data-confirm="Anonymize this client? This will remove personal data." style="display:inline-block;">
          @csrf
          <button class="panel-btn panel-btn-danger" type="submit">Anonymize</button>
        </form>
        <a class="panel-btn panel-btn-primary" href="{{ route('admin.projects.index') }}">Open Full Projects Page</a>
      </div>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:12px;">
      <article class="panel-card" style="margin:0;">
        <span class="panel-kpi-label">Total Projects</span>
        <p class="panel-kpi-value">{{ $projects->count() }}</p>
      </article>
      <article class="panel-card" style="margin:0;">
        <span class="panel-kpi-label">Active Projects</span>
        <p class="panel-kpi-value">{{ $activeProjects }}</p>
      </article>
      <article class="panel-card" style="margin:0;">
        <span class="panel-kpi-label">Completed</span>
        <p class="panel-kpi-value">{{ $completedProjects }}</p>
      </article>
      <article class="panel-card" style="margin:0;">
        <span class="panel-kpi-label">Open Invoices</span>
        <p class="panel-kpi-value">{{ $openInvoices }}</p>
      </article>
    </div>
  </section>

  <section class="panel-card" style="display:grid; gap:14px;">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
      <div>
        <h2 class="panel-section-title" style="margin-bottom:4px;">Project List</h2>
        <p class="panel-muted" style="margin:0;">Each row gives a quick project summary without showing extra client tabs.</p>
      </div>
      <span class="panel-badge">{{ $client->status }}</span>
    </div>

    <div class="panel-table-wrap">
      <table class="panel-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Project</th>
            <th>Service</th>
            <th>Schedule</th>
            <th>Status</th>
            <th>Files</th>
            <th>Requests</th>
            <th>Team</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse($projects as $project)
            <tr class="{{ (in_array($project->status, ['accepted', 'shooting', 'editing'], true) && $project->due_at && $project->due_at->isPast()) ? 'panel-row-overdue' : '' }}">
              <td>#{{ $project->id }}</td>
              <td>
                <strong>{{ $project->title }}</strong><br>
                <span class="panel-muted">{{ $project->property_address ?: '-' }}</span>
              </td>
              <td>{{ $project->service_type ?: '-' }}</td>
              <td>
                {{ $project->scheduled_at?->format('Y-m-d H:i') ?: '-' }}<br>
                <span class="panel-muted">Due: {{ $project->due_at?->format('Y-m-d H:i') ?: '-' }}</span>
              </td>
              <td><span class="panel-badge">{{ $project->status }}</span></td>
              <td>
                Gallery: {{ $project->gallery_media_count }}<br>
                <span class="panel-muted">Final ZIP: {{ $project->final_zip_count }}</span>
              </td>
              <td>
                Service: {{ $project->service_requests_count }}<br>
                <span class="panel-muted">Booking: {{ $project->booking_requests_count }}</span>
              </td>
              <td>
                @if($project->assignments->isNotEmpty())
                  {{ $project->assignments->pluck('user.name')->filter()->implode(', ') }}
                @else
                  <span class="panel-muted">Unassigned</span>
                @endif
              </td>
              <td>
                <div class="client-action-stack">
                  <div class="client-action-row">
                    <a class="panel-btn" href="{{ route('admin.media-delivery.index', ['media_search' => $project->title]) }}#project-{{ $project->id }}">Open Delivery</a>
                  @if($canManageProjects)
                    <a class="panel-btn" href="{{ route('admin.invoices.index', ['invoice_project' => $project->id]) }}">Open Invoices</a>
                  @endif
                    <a class="panel-btn" href="{{ route('admin.projects.workspace', $project) }}">Open project</a>
                  </div>
                  @if($canManageProjects)
                    <form method="post" action="{{ route('admin.projects.status', $project) }}" class="client-action-row">
                      @csrf
                      <select class="panel-select" name="status" style="min-width:130px;">
                        @foreach($projectStatuses as $status)
                          <option value="{{ $status }}" @selected($project->status === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                      </select>
                      <button class="panel-btn panel-btn-primary" type="submit">Save</button>
                      @if($canDeleteProjects)
                        <button class="panel-btn panel-btn-danger panel-btn-icon" type="submit" form="delete-project-{{ $project->id }}" title="Delete project" aria-label="Delete project">
                          <span class="panel-icon-trash" aria-hidden="true"><x-panel-icon name="trash" /></span>
                        </button>
                      @endif
                    </form>
                  @endif
                  @if($canDeleteProjects)
                    <form id="delete-project-{{ $project->id }}" method="post" action="{{ route('admin.projects.delete', $project) }}" data-confirm="Delete this project? This will remove team assignments, media, and related data. This cannot be undone.">
                      @csrf
                    </form>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="9" class="panel-muted">No projects found for this client yet.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>
  </div>
@endsection
