@extends('layouts.panel', [
  'title' => 'My Projects',
  'heading' => 'Projects',
  'subheading' => 'Monitor schedules, status, and production progress across your account.',
])

@section('content')
<div class="corp-admin-shell panel-stack">
  <section class="panel-grid panel-grid-kpi">
    <article class="panel-card">
      <span class="panel-kpi-label">Active Projects</span>
      <p class="panel-kpi-value">{{ $portalStats['active_projects'] }}</p>
    </article>
    <article class="panel-card">
      <span class="panel-kpi-label">Deliveries Ready</span>
      <p class="panel-kpi-value">{{ $portalStats['deliveries_ready'] }}</p>
    </article>
    <article class="panel-card">
      <span class="panel-kpi-label">Portal Messages</span>
      <p class="panel-kpi-value">{{ $portalStats['message_count'] }}</p>
    </article>
  </section>

  <section class="panel-card panel-stack">
    <div class="panel-form-row" style="justify-content: space-between; align-items: center;">
      <h2 class="panel-section-title" style="margin: 0;">Project Portfolio</h2>
      <div class="panel-form-row" style="margin-bottom: 0;">
        <a class="panel-btn" href="{{ route('user.deliveries.index') }}">Open Deliveries</a>
        <a class="panel-btn panel-btn-primary" href="{{ route('user.messages.index') }}">Request Service</a>
      </div>
    </div>

    <div class="panel-table-wrap">
      <table class="panel-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Project</th>
            <th>Service</th>
            <th>Schedule</th>
            <th>Due</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse($projects as $project)
            <tr>
              <td data-label="ID">#{{ $project->id }}</td>
              <td data-label="Project">
                {{ $project->title }}<br>
                <span class="panel-muted">{{ $project->property_address ?: '-' }}</span>
              </td>
              <td data-label="Service">{{ $project->service_type ?: '-' }}</td>
              <td data-label="Schedule">{{ $project->scheduled_at?->format('Y-m-d H:i') ?: '-' }}</td>
              <td data-label="Due">{{ $project->due_at?->format('Y-m-d H:i') ?: '-' }}</td>
              <td data-label="Status"><span class="panel-badge">{{ $project->status }}</span></td>
              <td data-label="Action">
                <div class="panel-form-row" style="margin-bottom: 0;">
                  <a class="panel-btn panel-btn-primary" href="{{ route('user.projects.show', $project) }}">Open project</a>
                  <a class="panel-btn" href="{{ route('user.deliveries.index') }}#project-{{ $project->id }}">Delivery</a>
                  @if($project->quoteBuild)
                    <a class="panel-btn" href="{{ route('user.quotes.show', $project->quoteBuild) }}">Linked Quote</a>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="panel-muted">No projects are currently linked to your client account.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <x-panel-pagination :paginator="$projects" />
  </section>
</div>
@endsection
