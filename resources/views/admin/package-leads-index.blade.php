@extends('layouts.panel', [
  'title' => 'Leads from Packages',
  'heading' => 'Leads from Packages',
  'subheading' => 'Only leads captured via package builder (channel: package_builder).',
])

@section('content')
<section class="panel-card">
  <div class="panel-sticky-filters">
    <form method="get" class="panel-form-row">
      <input class="panel-input" type="text" name="lead_search" placeholder="Search package lead" value="{{ $filters['lead_search'] }}">
      <select class="panel-select" name="lead_status">
        <option value="">All statuses</option>
        @foreach(['new','qualified','contacted','won','lost','nurturing'] as $status)
        <option value="{{ $status }}" @selected($filters['lead_status'] === $status)>{{ ucfirst($status) }}</option>
        @endforeach
      </select>
      <button class="panel-btn panel-btn-primary" type="submit">Filter</button>
      <a class="panel-link" href="{{ route('admin.leads.packages.index') }}">Clear</a>
    </form>
    <div class="panel-form-row">
      @if($widgetVisibility['can_export_data'])
      <form method="get" action="{{ route('admin.exports.leads') }}" class="panel-form-row">
        <input type="hidden" name="lead_search" value="{{ $filters['lead_search'] }}">
        <input type="hidden" name="lead_status" value="{{ $filters['lead_status'] }}">
        <input type="hidden" name="lead_channel" value="package_builder">
        <input class="panel-input" type="date" name="from_date" value="{{ $filters['leads_from_date'] }}">
        <input class="panel-input" type="date" name="to_date" value="{{ $filters['leads_to_date'] }}">
        <button class="panel-btn" type="submit">Export CSV</button>
      </form>
      @else
      <span class="panel-badge">Manager: export disabled</span>
      @endif
    </div>
  </div>

  <div class="panel-table-wrap">
    <table class="panel-table">
      <thead><tr><th>ID</th><th>Name</th><th>Contact</th><th>Service</th><th>Status</th><th>Score</th><th>Source</th><th>Action</th></tr></thead>
      <tbody>
        @forelse($leads as $lead)
        <tr>
          <td>#{{ $lead->id }}</td>
          <td>{{ $lead->name ?: '-' }}</td>
          <td>{{ $lead->email ?: ($lead->phone ?: '-') }}</td>
          <td>{{ $lead->service_type ?: '-' }}</td>
          <td><span class="panel-badge">{{ $lead->status }}</span></td>
          <td>{{ $lead->score }}</td>
          <td><span class="panel-badge">{{ $lead->conversation?->channel ?: 'package_builder' }}</span></td>
          <td><a class="panel-link panel-btn-icon" href="{{ route('admin.leads.show', $lead) }}" title="Open lead" aria-label="Open lead"><span class="panel-icon" aria-hidden="true"><svg viewBox="0 0 20 20"><path d="M4 10h12M10 4l6 6-6 6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span></a></td>
        </tr>
        @empty
        <tr><td colspan="8" class="panel-muted">No package leads yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <x-panel-pagination :paginator="$leads" />
</section>
@endsection
