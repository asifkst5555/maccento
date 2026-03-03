@extends('layouts.panel', [
  'title' => 'Client Dashboard',
  'heading' => 'Client Dashboard',
  'subheading' => 'Welcome, ' . auth()->user()->name,
])

@section('content')
<section class="panel-card">
  <h2 class="panel-section-title">Your Leads</h2>
  <div class="panel-table-wrap">
    <table class="panel-table">
      <thead><tr><th>Lead ID</th><th>Service</th><th>Location</th><th>Status</th><th>Score</th></tr></thead>
      <tbody>
        @forelse($leads as $lead)
        <tr>
          <td>#{{ $lead->id }}</td>
          <td>{{ $lead->service_type ?: '-' }}</td>
          <td>{{ $lead->location ?: '-' }}</td>
          <td><span class="panel-badge">{{ $lead->status }}</span></td>
          <td>{{ $lead->score }}</td>
        </tr>
        @empty
        <tr><td colspan="5" class="panel-muted">No leads yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <x-panel-pagination :paginator="$leads" />
</section>

<section class="panel-card">
  <h2 class="panel-section-title">Your Quotes</h2>
  <div class="panel-table-wrap">
    <table class="panel-table">
      <thead><tr><th>Quote ID</th><th>Services</th><th>Status</th><th>Total</th><th>Submitted</th><th>Action</th></tr></thead>
      <tbody>
        @forelse($quotes as $quote)
        <tr>
          <td>{{ $quote->quote_id }}</td>
          <td>{{ is_array($quote->services) ? implode(', ', $quote->services) : '-' }}</td>
          <td><span class="panel-badge">{{ $quote->status }}</span></td>
          <td>{{ number_format((int) $quote->estimated_total) }} {{ $quote->currency }}</td>
          <td>{{ $quote->submitted_at?->format('Y-m-d H:i') ?: '-' }}</td>
          <td><a class="panel-link" href="{{ route('user.quotes.show', $quote) }}">Open</a></td>
        </tr>
        @empty
        <tr><td colspan="6" class="panel-muted">No quotes yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</section>

<section class="panel-card">
  <h2 class="panel-section-title">Request New Service</h2>
  <form method="post" action="{{ route('user.service-requests.store') }}" class="panel-stack">
    @csrf
    <input class="panel-input" type="text" name="requested_service" placeholder="Service needed (photo/video/drone etc.)" required>
    <input class="panel-input" type="text" name="subject" placeholder="Subject (optional)">
    <input class="panel-input" type="date" name="preferred_date">
    <textarea class="panel-textarea" name="details" placeholder="Tell us what you need"></textarea>
    <button class="panel-btn panel-btn-primary" type="submit">Submit Request</button>
  </form>
</section>

@if($client)
<section class="panel-card">
  <h2 class="panel-section-title">Your Projects</h2>
  <div class="panel-table-wrap">
    <table class="panel-table">
      <thead><tr><th>Title</th><th>Service</th><th>Schedule</th><th>Status</th></tr></thead>
      <tbody>
        @forelse($client->projects as $project)
        <tr>
          <td>{{ $project->title }}</td>
          <td>{{ $project->service_type ?: '-' }}</td>
          <td>{{ $project->scheduled_at?->format('Y-m-d H:i') ?: '-' }}</td>
          <td><span class="panel-badge">{{ $project->status }}</span></td>
        </tr>
        @empty
        <tr><td colspan="4" class="panel-muted">No projects yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</section>

<section class="panel-card">
  <h2 class="panel-section-title">Your Invoices</h2>
  <div class="panel-table-wrap">
    <table class="panel-table">
      <thead><tr><th>Invoice #</th><th>Amount</th><th>Status</th><th>Due Date</th></tr></thead>
      <tbody>
        @forelse($client->invoices as $invoice)
        <tr>
          <td>{{ $invoice->invoice_number }}</td>
          <td>{{ number_format((float) $invoice->amount, 2) }} {{ $invoice->currency }}</td>
          <td><span class="panel-badge">{{ $invoice->status }}</span></td>
          <td>{{ $invoice->due_date?->format('Y-m-d') ?: '-' }}</td>
        </tr>
        @empty
        <tr><td colspan="4" class="panel-muted">No invoices yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</section>

<section class="panel-card">
  <h2 class="panel-section-title">Request History</h2>
  <div class="panel-table-wrap">
    <table class="panel-table">
      <thead><tr><th>Service</th><th>Preferred Date</th><th>Status</th></tr></thead>
      <tbody>
        @forelse($client->serviceRequests as $requestItem)
        <tr>
          <td>{{ $requestItem->requested_service }}</td>
          <td>{{ $requestItem->preferred_date?->format('Y-m-d') ?: '-' }}</td>
          <td><span class="panel-badge">{{ $requestItem->status }}</span></td>
        </tr>
        @empty
        <tr><td colspan="3" class="panel-muted">No requests yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</section>

<section class="panel-card">
  <h2 class="panel-section-title">Messages from Team</h2>
  <div class="panel-chat-list">
    @forelse($client->messages as $message)
    <div class="panel-chat-item {{ $message->sender_role === 'client' ? 'is-user' : 'is-assistant' }}">
      <p class="panel-chat-role">{{ strtoupper($message->sender_role) }}</p>
      <p class="panel-chat-text">{{ $message->message }}</p>
      <p class="panel-muted">{{ $message->sent_at?->format('Y-m-d H:i') ?: $message->created_at?->format('Y-m-d H:i') }}</p>
    </div>
    @empty
    <p class="panel-muted">No team messages yet.</p>
    @endforelse
  </div>
</section>
@endif
@endsection
