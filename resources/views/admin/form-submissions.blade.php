@extends('layouts.panel', [
  'title' => 'Website Form Submissions',
  'heading' => 'Website Form Submissions',
  'subheading' => 'Inbound website leads from contact forms.',
])

@section('content')
<section class="panel-card">
  <form method="get" class="panel-form-row">
    <input class="panel-input" type="text" name="search" placeholder="Search name, email, phone..." value="{{ $filters['search'] }}">
    <select class="panel-select" name="status">
      <option value="">All statuses</option>
      @foreach(['new','reviewed','qualified','won','lost'] as $status)
      <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ ucfirst($status) }}</option>
      @endforeach
    </select>
    <button class="panel-btn panel-btn-primary" type="submit">Filter</button>
  </form>
  <div class="panel-table-wrap">
    <table class="panel-table">
      <thead><tr><th>ID</th><th>Submitted</th><th>Name</th><th>Contact</th><th>Service</th><th>Region</th><th>Status</th><th>Action</th></tr></thead>
      <tbody>
        @forelse($submissions as $submission)
        <tr>
          <td>#{{ $submission->id }}</td>
          <td>{{ $submission->submitted_at?->format('Y-m-d H:i') ?: '-' }}</td>
          <td>{{ $submission->name ?: '-' }}</td>
          <td>{{ $submission->email ?: ($submission->phone ?: '-') }}</td>
          <td>{{ $submission->service ?: '-' }}</td>
          <td>{{ $submission->region ?: '-' }}</td>
          <td><span class="panel-badge">{{ $submission->status }}</span></td>
          <td><a class="panel-link" href="{{ route('admin.form-submissions.show', $submission) }}">Open</a></td>
        </tr>
        @empty
        <tr><td colspan="8" class="panel-muted">No submissions.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <x-panel-pagination :paginator="$submissions" />
</section>
@endsection
