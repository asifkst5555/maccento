@extends('layouts.panel', [
  'title' => 'Submission #' . $submission->id,
  'heading' => 'Submission #' . $submission->id,
  'subheading' => 'Received ' . ($submission->submitted_at?->format('Y-m-d H:i') ?: '-'),
])

@section('content')
<section class="panel-card panel-stack">
  <form method="post" action="{{ route('admin.form-submissions.status', $submission) }}" class="panel-form-row">
    @csrf
    <select class="panel-select" name="status">
      @foreach(['new','reviewed','qualified','won','lost'] as $status)
      <option value="{{ $status }}" @selected($submission->status === $status)>{{ ucfirst($status) }}</option>
      @endforeach
    </select>
    <button class="panel-btn panel-btn-primary" type="submit">Update status</button>
  </form>
  <hr class="panel-hr">
  <p><strong>Status:</strong> <span class="panel-badge">{{ $submission->status }}</span></p>
  <p><strong>Name:</strong> {{ $submission->name ?: '-' }}</p>
  <p><strong>Company:</strong> {{ $submission->company ?: '-' }}</p>
  <p><strong>Email:</strong> {{ $submission->email ?: '-' }}</p>
  <p><strong>Phone:</strong> {{ $submission->phone ?: '-' }}</p>
  <p><strong>Service:</strong> {{ $submission->service ?: '-' }}</p>
  <p><strong>Region:</strong> {{ $submission->region ?: '-' }}</p>
  <p><strong>Page URL:</strong> {{ $submission->page_url ?: '-' }}</p>
  <p><strong>IP Address:</strong> {{ $submission->ip_address ?: '-' }}</p>
  <p><strong>Source:</strong> {{ $submission->source ?: '-' }}</p>
  <hr class="panel-hr">
  <div>
    <h2 class="panel-section-title">Message</h2>
    <p class="panel-muted">{{ $submission->message ?: '-' }}</p>
  </div>
</section>
@endsection
