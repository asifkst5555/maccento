@extends('layouts.panel', [
  'title' => 'Quote ' . $quote->quote_id,
  'heading' => 'Quote ' . $quote->quote_id,
  'subheading' => 'Status: ' . $quote->status,
])

@section('content')
<section class="panel-card panel-stack">
  <p><strong>Submitted:</strong> {{ $quote->submitted_at?->format('Y-m-d H:i') ?: '-' }}</p>
  <p><strong>Listing:</strong> {{ $quote->listing_type ?: '-' }}</p>
  <p><strong>Services:</strong> {{ is_array($quote->services) ? implode(', ', $quote->services) : '-' }}</p>
  <p><strong>Estimated total:</strong> {{ number_format((int) $quote->estimated_total) }} {{ $quote->currency }}</p>
</section>

<section class="panel-card">
  <h2 class="panel-section-title">Line Items</h2>
  <ul class="panel-list">
    @forelse(($quote->line_items ?? []) as $item)
    <li>{{ $item['label'] ?? '-' }} - {{ number_format((int) ($item['amount'] ?? 0)) }} {{ $quote->currency }}</li>
    @empty
    <li class="panel-muted">No line items.</li>
    @endforelse
  </ul>
</section>

<section class="panel-card">
  <h2 class="panel-section-title">Timeline</h2>
  <div class="panel-table-wrap">
    <table class="panel-table">
      <thead><tr><th>Time</th><th>Event</th><th>Details</th></tr></thead>
      <tbody>
        @forelse($quote->events as $event)
        <tr>
          <td>{{ $event->created_at?->format('Y-m-d H:i') }}</td>
          <td>{{ $event->event_type }}</td>
          <td>{{ $event->payload ? json_encode($event->payload) : '-' }}</td>
        </tr>
        @empty
        <tr><td colspan="3" class="panel-muted">No timeline events yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</section>

<section class="panel-card panel-stack">
  <h2 class="panel-section-title">Request Quote Revision</h2>
  <p class="panel-muted">Need changes in scope, deliverables, or pricing assumptions? Send a revision request directly to admin.</p>
  <form method="post" action="{{ route('user.quotes.revision-request', $quote) }}" class="panel-stack">
    @csrf
    <textarea class="panel-textarea" name="revision_note" maxlength="1000" required placeholder="Example: Please update this quote to include drone video and 31-45 photos.">{{ old('revision_note') }}</textarea>
    <select class="panel-select" name="preferred_contact">
      <option value="">Preferred contact method (optional)</option>
      <option value="email" @selected(old('preferred_contact') === 'email')>Email</option>
      <option value="phone" @selected(old('preferred_contact') === 'phone')>Phone</option>
      <option value="call" @selected(old('preferred_contact') === 'call')>Call</option>
    </select>
    <button class="panel-btn panel-btn-primary" type="submit">Send Revision Request</button>
  </form>
</section>
@endsection
