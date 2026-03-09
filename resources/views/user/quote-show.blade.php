@extends('layouts.panel', [
  'title' => 'Quote ' . $quote->quote_id,
  'heading' => 'Quote ' . $quote->quote_id,
  'subheading' => 'Detailed quotation record with pricing, scope, and revision workflow.',
])

@section('content')
@php
  $quoteStatusClass = 'client-status-chip status-' . \Illuminate\Support\Str::slug((string) $quote->status);
@endphp
<div class="client-portal-shell">
  <section class="panel-card client-portal-hero">
    <div class="client-portal-hero-head">
      <div>
        <span class="client-portal-eyebrow">Quotation Detail</span>
        <h2 class="panel-section-title" style="margin-top: 12px;">{{ $quote->quote_id }}</h2>
        <p class="client-portal-summary">
          {{ $quote->listing_type ?: 'Listing type pending' }}
          &bull; {{ is_array($quote->services) ? implode(', ', $quote->services) : 'Services pending' }}
        </p>
      </div>
      <div class="client-portal-actions">
        <span class="{{ $quoteStatusClass }}">{{ $quote->status }}</span>
        <a class="panel-btn" href="{{ route('user.quotes.index') }}">Back to Quotes</a>
      </div>
    </div>
  </section>

  <section class="panel-grid panel-grid-kpi-compact">
    <article class="client-portal-kpi">
      <span class="panel-kpi-label">Estimated Total</span>
      <p class="client-portal-kpi-value">{{ number_format((int) $quote->estimated_total) }}</p>
      <p class="client-portal-kpi-note">{{ $quote->currency }}</p>
    </article>
    <article class="client-portal-kpi">
      <span class="panel-kpi-label">Submitted</span>
      <p class="client-portal-kpi-value" style="font-size: 20px;">{{ $quote->submitted_at?->format('Y-m-d') ?: '-' }}</p>
      <p class="client-portal-kpi-note">{{ $quote->submitted_at?->format('H:i') ?: '' }}</p>
    </article>
    <article class="client-portal-kpi">
      <span class="panel-kpi-label">Line Items</span>
      <p class="client-portal-kpi-value">{{ count($quote->line_items ?? []) }}</p>
      <p class="client-portal-kpi-note">Pricing components in this quotation.</p>
    </article>
  </section>

  <section class="client-portal-grid-two">
    <article class="panel-card client-portal-stack client-portal-card-accent">
      <div class="client-portal-section-head">
        <div class="client-portal-section-copy">
          <h2 class="panel-section-title" style="margin: 0;">Quote Summary</h2>
          <p class="client-portal-subtle" style="margin: 8px 0 0;">A concise view of your quote scope, estimate, and submission details.</p>
        </div>
      </div>
      <div class="client-portal-detail-grid">
        <div class="client-portal-detail">
          <span class="client-portal-detail-label">Listing Type</span>
          <p class="client-portal-detail-value">{{ $quote->listing_type ?: '-' }}</p>
        </div>
        <div class="client-portal-detail">
          <span class="client-portal-detail-label">Status</span>
          <p class="client-portal-detail-value">{{ $quote->status }}</p>
        </div>
        <div class="client-portal-detail">
          <span class="client-portal-detail-label">Currency</span>
          <p class="client-portal-detail-value">{{ $quote->currency }}</p>
        </div>
      </div>
      <div class="client-portal-empty">
        <strong>Services Included</strong>
        {{ is_array($quote->services) && count($quote->services) > 0 ? implode(', ', $quote->services) : 'No services listed.' }}
      </div>
    </article>

    <article class="panel-card client-portal-stack">
      <div class="client-portal-section-head">
        <div class="client-portal-section-copy">
          <h2 class="panel-section-title" style="margin: 0;">Request Quote Revision</h2>
          <p class="client-portal-subtle" style="margin: 8px 0 0;">Need changes in scope, pricing assumptions, or deliverables? Send the admin team a structured revision request.</p>
        </div>
      </div>
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
    </article>
  </section>

  <section class="client-portal-grid-two">
    <article class="panel-card client-portal-table">
      <h2 class="panel-section-title">Line Items</h2>
      <div class="panel-table-wrap">
        <table class="panel-table">
          <thead>
            <tr>
              <th>Item</th>
              <th>Amount</th>
            </tr>
          </thead>
          <tbody>
            @forelse(($quote->line_items ?? []) as $item)
              <tr>
                <td>{{ $item['label'] ?? '-' }}</td>
                <td><span class="client-portal-money">{{ number_format((int) ($item['amount'] ?? 0)) }} {{ $quote->currency }}</span></td>
              </tr>
            @empty
              <tr>
                <td colspan="2">
                  <div class="client-portal-empty"><strong>No line items</strong>This quote does not have a detailed line-item breakdown yet.</div>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </article>

    <article class="panel-card client-portal-table">
      <h2 class="panel-section-title">Timeline</h2>
      <div class="panel-table-wrap">
        <table class="panel-table">
          <thead>
            <tr>
              <th>Time</th>
              <th>Event</th>
              <th>Details</th>
            </tr>
          </thead>
          <tbody>
            @forelse($quote->events as $event)
              <tr>
                <td>{{ $event->created_at?->format('Y-m-d H:i') ?: '-' }}</td>
                <td>{{ $event->event_type }}</td>
                <td>{{ $event->payload ? json_encode($event->payload) : '-' }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="3">
                  <div class="client-portal-empty"><strong>No quote timeline yet</strong>Status changes and revision activity will appear here.</div>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </article>
  </section>
</div>
@endsection
