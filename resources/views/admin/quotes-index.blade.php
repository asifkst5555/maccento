@extends('layouts.panel', [
  'title' => 'Quote Pipeline',
  'heading' => 'Quote Pipeline',
  'subheading' => 'Dedicated quote management workspace with corporate separation.',
])

@section('content')
<section class="panel-card">
  <h2 class="panel-section-title">Create Quote Manually</h2>
  <form method="post" action="{{ route('admin.quotes.manual-store') }}" class="panel-form-row">
    @csrf
    <input class="panel-input" type="text" name="contact_name" placeholder="Client name" required>
    <input class="panel-input" type="email" name="contact_email" placeholder="Client email">
    <input class="panel-input" type="text" name="contact_phone" placeholder="Client phone">
    <input class="panel-input" type="text" name="services" placeholder="Services (comma separated)" required>
    <select class="panel-select" name="listing_type">
      <option value="home">Home</option>
      <option value="condo">Condo</option>
      <option value="rental">Rental</option>
      <option value="chalet">Chalet</option>
      <option value="other" selected>Other</option>
    </select>
    <input class="panel-input" type="number" name="estimated_total" min="0" placeholder="Estimated total" required>
    <input class="panel-input" type="text" name="currency" value="USD" placeholder="Currency">
    <button class="panel-btn panel-btn-primary" type="submit">Create Quote</button>
    <textarea class="panel-textarea" name="notes" placeholder="Optional notes"></textarea>
  </form>
</section>

<section class="panel-card">
  <div class="panel-sticky-filters">
    <form method="get" class="panel-form-row">
      <input class="panel-input" type="text" name="quote_search" placeholder="Search quote/contact" value="{{ $filters['quote_search'] }}">
      <select class="panel-select" name="quote_status">
        <option value="">All statuses</option>
        @foreach(['new','reviewed','contacted','booked','lost'] as $status)
        <option value="{{ $status }}" @selected($filters['quote_status'] === $status)>{{ ucfirst($status) }}</option>
        @endforeach
      </select>
      <input class="panel-input" type="number" name="min_total" placeholder="Min total" value="{{ $filters['min_total'] }}">
      <input class="panel-input" type="number" name="max_total" placeholder="Max total" value="{{ $filters['max_total'] }}">
      <button class="panel-btn panel-btn-primary" type="submit">Filter</button>
      <a class="panel-link" href="{{ route('admin.quotes.index') }}">Clear</a>
    </form>
    <div class="panel-form-row">
      @if($widgetVisibility['can_export_data'])
      <form method="get" action="{{ route('admin.exports.quotes') }}" class="panel-form-row">
        <input type="hidden" name="quote_search" value="{{ $filters['quote_search'] }}">
        <input type="hidden" name="quote_status" value="{{ $filters['quote_status'] }}">
        <input type="hidden" name="min_total" value="{{ $filters['min_total'] }}">
        <input type="hidden" name="max_total" value="{{ $filters['max_total'] }}">
        <input class="panel-input" type="date" name="from_date" value="{{ $filters['quotes_from_date'] }}">
        <input class="panel-input" type="date" name="to_date" value="{{ $filters['quotes_to_date'] }}">
        <button class="panel-btn" type="submit">Export CSV</button>
      </form>
      @else
      <span class="panel-badge">Manager: export disabled</span>
      @endif
    </div>
  </div>

  <div class="panel-table-wrap">
    <table class="panel-table">
      <thead><tr><th>Quote ID</th><th>Package</th><th>Status</th><th>Total</th><th>Submitted</th><th>Action</th></tr></thead>
      <tbody>
        @forelse($quotes as $quote)
        <tr>
          <td>{{ $quote->quote_id }}</td>
          <td>{{ data_get($quote->options, 'package_title', ucfirst((string) data_get($quote->options, 'package_code', 'custom'))) }}</td>
          <td><span class="panel-badge">{{ $quote->status }}</span></td>
          <td>
            @if(data_get($quote->options, 'display_total'))
              {{ data_get($quote->options, 'display_total') }} {{ $quote->currency }}
            @else
              {{ number_format((int) $quote->estimated_total) }} {{ $quote->currency }}
            @endif
          </td>
          <td>{{ $quote->submitted_at?->format('Y-m-d H:i') ?: '-' }}</td>
          <td>
            <a class="panel-link" href="{{ route('admin.quotes.show', $quote) }}">Open</a>
            <form method="post" action="{{ route('admin.quotes.delete', $quote) }}" style="display:inline-block; margin-left:8px;" onsubmit="return confirm('Delete this quote?');">
              @csrf
              <button class="panel-btn panel-btn-danger" type="submit">Delete</button>
            </form>
          </td>
        </tr>
        @empty
        <tr><td colspan="6" class="panel-muted">No quotes yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <x-panel-pagination :paginator="$quotes" />
</section>
@endsection
