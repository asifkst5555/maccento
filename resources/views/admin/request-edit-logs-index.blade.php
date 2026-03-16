@extends('layouts.panel', [
  'title' => 'Activity Log',
  'heading' => 'Activity Log',
  'subheading' => 'Review critical changes across requests, projects, invoices, and client actions.',
])

@section('content')
<div class="corp-admin-shell panel-stack">
  <section class="panel-card panel-stack">
    <div class="panel-form-row" style="justify-content: space-between; align-items: center;">
      <div>
        <span class="panel-badge">Audit</span>
        <h2 class="panel-section-title" style="margin-top: 12px;">Activity Log</h2>
        <p class="panel-muted">Track who changed records, when it happened, and what was updated.</p>
      </div>
      <form method="get" action="{{ route('admin.request-edit-logs.index') }}" class="panel-form-row" style="margin-bottom: 0;">
        <select class="panel-select" name="type">
          <option value="">All types</option>
          @foreach($typeOptions as $type)
            <option value="{{ $type }}" @selected($typeFilter === $type)>{{ ucfirst($type) }}</option>
          @endforeach
        </select>
        <select class="panel-select" name="action">
          <option value="">All actions</option>
          @foreach($actionOptions as $action)
            <option value="{{ $action }}" @selected($actionFilter === $action)>{{ ucfirst(str_replace('_', ' ', $action)) }}</option>
          @endforeach
        </select>
        <input class="panel-input" type="text" name="search" placeholder="Search client, actor, or request" value="{{ $search }}">
        <button class="panel-btn panel-btn-primary" type="submit">Filter</button>
        @if($typeFilter !== '' || $actionFilter !== '' || $search !== '')
          <a class="panel-btn" href="{{ route('admin.request-edit-logs.index') }}">Clear</a>
        @endif
      </form>
    </div>
  </section>

  <section class="panel-card panel-stack">
    <div class="panel-table-wrap">
      <table class="panel-table">
        <thead>
          <tr>
            <th>When</th>
            <th>Type</th>
            <th>Action</th>
            <th>Entity</th>
            <th>Client</th>
            <th>Actor</th>
            <th>Summary</th>
          </tr>
        </thead>
        <tbody>
          @forelse($logs as $log)
            @php
              $changes = $log->changes ?? [];
              $action = $log->action ?: ($changes['action'] ?? 'update');
              $type = $log->entity_type ?: $log->request_type;
              $entityId = $log->entity_id ?: $log->request_id;
              $summaryService = data_get($changes, 'after.requested_service')
                ?? data_get($changes, 'snapshot.requested_service')
                ?? data_get($changes, 'before.requested_service');
              $summarySubject = data_get($changes, 'after.subject')
                ?? data_get($changes, 'snapshot.subject')
                ?? data_get($changes, 'before.subject');
              $summaryDetails = data_get($changes, 'after.details')
                ?? data_get($changes, 'snapshot.details')
                ?? data_get($changes, 'before.details');
              $summaryDate = data_get($changes, 'after.preferred_date')
                ?? data_get($changes, 'snapshot.preferred_date')
                ?? data_get($changes, 'before.preferred_date');
              $summaryStatus = data_get($changes, 'after.status')
                ?? data_get($changes, 'snapshot.status')
                ?? data_get($changes, 'before.status');
              $summary = $log->summary ?: $summaryService ?: $summarySubject ?: $summaryDetails;
            @endphp
            <tr>
              <td data-label="When">{{ $log->created_at?->format('Y-m-d H:i') ?: '-' }}</td>
              <td data-label="Type"><span class="panel-badge">{{ ucfirst($type ?: 'update') }}</span></td>
              <td data-label="Action"><span class="panel-badge">{{ ucfirst(str_replace('_', ' ', $action)) }}</span></td>
              <td data-label="Entity">#{{ $entityId ?: '-' }}</td>
              <td data-label="Client">
                {{ $log->client?->name ?: 'Client #' . ($log->client_id ?? '-') }}
                <div class="panel-muted">{{ $log->client?->email ?: 'Email unavailable' }}</div>
              </td>
              <td data-label="Actor">
                {{ $log->actor?->name ?: 'System' }}
                <div class="panel-muted">{{ $log->actor?->email ?: ($log->actor_role ?: 'system') }}</div>
              </td>
              <td data-label="Summary">
                <div>{{ $summary ?: 'Record #' . ($entityId ?: $log->request_id) }}</div>
                @if(!blank($summarySubject))
                  <div class="panel-muted">{{ $summarySubject }}</div>
                @endif
                @if(!blank($summaryDetails))
                  <div class="panel-muted">{{ 
                    Illuminate\Support\Str::limit($summaryDetails, 120) 
                  }}</div>
                @endif
                <div class="panel-muted">
                  @if(!blank($summaryDate))
                    Preferred: {{ $summaryDate }}
                  @endif
                  @if(!blank($summaryStatus))
                    <span style="margin-left: 8px;">Status: {{ $summaryStatus }}</span>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="panel-muted">No audit logs found yet.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{ $logs->links() }}
  </section>
</div>
@endsection
