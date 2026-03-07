@extends('layouts.panel', [
  'title' => 'All Leads',
  'heading' => 'All Leads',
  'subheading' => 'Dedicated lead management workspace with focused filters and actions.',
])

@section('content')
<section class="panel-card">
  <div class="panel-sticky-filters">
    <form method="get" class="panel-form-row">
      <input class="panel-input" type="text" name="lead_search" placeholder="Search lead" value="{{ $filters['lead_search'] }}">
      <select class="panel-select" name="lead_status">
        <option value="">All statuses</option>
        @foreach(['new','qualified','contacted','won','lost','nurturing'] as $status)
        <option value="{{ $status }}" @selected($filters['lead_status'] === $status)>{{ ucfirst($status) }}</option>
        @endforeach
      </select>
      <button class="panel-btn panel-btn-primary" type="submit">Filter</button>
      <a class="panel-link" href="{{ route('admin.leads.index') }}">Clear</a>
    </form>
    <div class="panel-form-row">
      @if($widgetVisibility['can_export_data'])
      <form method="get" action="{{ route('admin.exports.leads') }}" class="panel-form-row">
        <input type="hidden" name="lead_search" value="{{ $filters['lead_search'] }}">
        <input type="hidden" name="lead_status" value="{{ $filters['lead_status'] }}">
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
      <thead><tr><th>ID</th><th>Name</th><th>Contact</th><th>Service</th><th>Status</th><th>Score</th><th>Action</th></tr></thead>
      <tbody>
        @forelse($leads as $lead)
        <tr>
          <td>#{{ $lead->id }}</td>
          <td>{{ $lead->name ?: '-' }}</td>
          <td>{{ $lead->email ?: ($lead->phone ?: '-') }}</td>
          <td>{{ $lead->service_type ?: '-' }}</td>
          <td><span class="panel-badge">{{ $lead->status }}</span></td>
          <td>{{ $lead->score }}</td>
          <td>
            <a class="panel-link" href="{{ route('admin.leads.show', $lead) }}">Open</a>
            @if(!blank($lead->email))
            <a class="panel-link" style="margin-left:8px;" href="{{ route('admin.emails.inbox', ['compose_to' => $lead->email, 'compose_subject' => 'Follow-up from Maccento CRM', 'compose_message' => 'Hi ' . ($lead->name ?: 'there') . ",\n\nThanks for your interest in Maccento. We would love to help you move forward.\n\nBest regards,\n" . (auth()->user()->name ?? 'Maccento Team'), 'lead_id' => $lead->id, 'recipient_name' => $lead->name, 'compose_template' => 'cold_followup', 'compose_goal' => 'Send a short warm follow-up and ask for a 10-minute call this week.']) }}">Email</a>
            <form method="post" action="{{ route('admin.leads.email.send', $lead) }}" style="display:inline-block; margin-left:8px;" onsubmit="return confirm('Send quick follow-up email to this lead?');">
              @csrf
              <button class="panel-btn" type="submit">Quick Send</button>
            </form>
            @endif
            <form method="post" action="{{ route('admin.leads.delete', $lead) }}" style="display:inline-block; margin-left:8px;" onsubmit="return confirm('Delete this lead?');">
              @csrf
              <button class="panel-btn panel-btn-danger" type="submit">Delete</button>
            </form>
          </td>
        </tr>
        @empty
        <tr><td colspan="7" class="panel-muted">No leads yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <x-panel-pagination :paginator="$leads" />
</section>
@endsection
