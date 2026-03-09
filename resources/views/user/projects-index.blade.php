@extends('layouts.panel', [
  'title' => 'My Projects',
  'heading' => 'Projects',
  'subheading' => 'Monitor schedules, status, and production progress across your account.',
])

@section('content')
<div class="client-portal-shell">
  <section class="panel-grid panel-grid-kpi-compact">
    <article class="client-portal-kpi">
      <span class="panel-kpi-label">Active Projects</span>
      <p class="client-portal-kpi-value">{{ $portalStats['active_projects'] }}</p>
    </article>
    <article class="client-portal-kpi">
      <span class="panel-kpi-label">Deliveries Ready</span>
      <p class="client-portal-kpi-value">{{ $portalStats['deliveries_ready'] }}</p>
    </article>
    <article class="client-portal-kpi">
      <span class="panel-kpi-label">Portal Messages</span>
      <p class="client-portal-kpi-value">{{ $portalStats['message_count'] }}</p>
    </article>
  </section>

  <section class="panel-card client-portal-stack">
    <div class="panel-form-row" style="justify-content: space-between; align-items: center;">
      <h2 class="panel-section-title" style="margin: 0;">Project Portfolio</h2>
      <div class="client-portal-actions">
        <a class="panel-btn" href="{{ route('user.deliveries.index') }}">Open Deliveries</a>
        <a class="panel-btn panel-btn-primary" href="{{ route('user.messages.index') }}">Request Service</a>
      </div>
    </div>

    @forelse($projects as $project)
      <article class="client-portal-list-row">
        <div class="client-portal-list-main">
          <h3 class="client-portal-title">{{ $project->title }}</h3>
          <p class="client-portal-meta">
            {{ $project->service_type ?: 'Service pending' }}
            @if(!blank($project->property_address))
              &bull; {{ $project->property_address }}
            @endif
          </p>
          <div class="client-portal-detail-grid" style="margin-top: 12px;">
            <div class="client-portal-detail">
              <span class="client-portal-detail-label">Schedule</span>
              <p class="client-portal-detail-value">{{ $project->scheduled_at?->format('Y-m-d H:i') ?: 'To be confirmed' }}</p>
            </div>
            <div class="client-portal-detail">
              <span class="client-portal-detail-label">Gallery Files</span>
              <p class="client-portal-detail-value">{{ $project->gallery_media_count }}</p>
            </div>
            <div class="client-portal-detail">
              <span class="client-portal-detail-label">Final ZIP</span>
              <p class="client-portal-detail-value">{{ $project->final_zip_count }}</p>
            </div>
          </div>
        </div>
        <div class="client-portal-side">
          <span class="panel-badge">{{ $project->status }}</span>
          <a class="panel-btn panel-btn-primary" href="{{ route('user.projects.show', $project) }}">Open Project</a>
          <a class="panel-btn" href="{{ route('user.deliveries.index') }}#project-{{ $project->id }}">Open Delivery</a>
          @if($project->quoteBuild)
            <a class="panel-btn" href="{{ route('user.quotes.show', $project->quoteBuild) }}">Linked Quote</a>
          @endif
        </div>
      </article>
    @empty
      <div class="client-portal-empty">No projects are currently linked to your client account.</div>
    @endforelse

    <x-panel-pagination :paginator="$projects" />
  </section>
</div>
@endsection
