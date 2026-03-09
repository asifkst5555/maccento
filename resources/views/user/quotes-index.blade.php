@extends('layouts.panel', [
  'title' => 'Quotations',
  'heading' => 'Quotations',
  'subheading' => 'Track estimate history, status changes, and open detailed quote pages.',
])

@section('content')
<div class="client-portal-shell">
  @php
    $quoteStatusClass = static function (?string $status): string {
      return 'client-status-chip status-' . \Illuminate\Support\Str::slug((string) $status);
    };
  @endphp
  <section class="panel-grid panel-grid-kpi-compact">
    <article class="client-portal-kpi">
      <span class="panel-kpi-label">Pending Quotes</span>
      <p class="client-portal-kpi-value">{{ $portalStats['pending_quotes'] }}</p>
    </article>
    <article class="client-portal-kpi">
      <span class="panel-kpi-label">Lead Records</span>
      <p class="client-portal-kpi-value">{{ $portalStats['lead_count'] }}</p>
    </article>
    <article class="client-portal-kpi">
      <span class="panel-kpi-label">Portal Messages</span>
      <p class="client-portal-kpi-value">{{ $portalStats['message_count'] }}</p>
    </article>
  </section>

  <section class="panel-card client-portal-table client-portal-card-accent">
    <div class="client-portal-section-head">
      <div class="client-portal-section-copy">
        <h2 class="panel-section-title" style="margin: 0;">Quote History</h2>
        <p class="client-portal-subtle" style="margin: 8px 0 0;">Review estimate progress, compare submitted scopes, and open detailed quote records.</p>
      </div>
      <a class="panel-btn" href="{{ route('user.messages.index') }}">Request Changes</a>
    </div>
    <div class="panel-table-wrap">
      <table class="panel-table">
        <thead>
          <tr>
            <th>Quote ID</th>
            <th>Listing</th>
            <th>Services</th>
            <th>Status</th>
            <th>Total</th>
            <th>Submitted</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse($quotes as $quote)
            <tr>
              <td>{{ $quote->quote_id }}</td>
              <td>{{ $quote->listing_type ?: '-' }}</td>
              <td>{{ is_array($quote->services) ? implode(', ', $quote->services) : '-' }}</td>
              <td><span class="{{ $quoteStatusClass($quote->status) }}">{{ $quote->status }}</span></td>
              <td><span class="client-portal-money">{{ number_format((int) $quote->estimated_total) }} {{ $quote->currency }}</span></td>
              <td>{{ $quote->submitted_at?->format('Y-m-d H:i') ?: '-' }}</td>
              <td><a class="panel-btn panel-btn-primary" href="{{ route('user.quotes.show', $quote) }}">Open Quote</a></td>
            </tr>
          @empty
            <tr>
              <td colspan="7">
                <div class="client-portal-empty"><strong>No quotations yet</strong>Your pricing proposals and estimate revisions will appear here once they are created.</div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <x-panel-pagination :paginator="$quotes" />
  </section>
</div>
@endsection
