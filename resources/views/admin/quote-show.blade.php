@extends('layouts.panel', [
  'title' => 'Quote ' . $quote->quote_id,
  'heading' => 'Quote ' . $quote->quote_id,
  'subheading' => 'Submitted ' . ($quote->submitted_at?->format('Y-m-d H:i') ?: '-'),
])

@section('content')
@php
  $packageCode = strtolower((string) data_get($quote->options, 'package_code', 'custom'));
  $packageTitle = (string) data_get($quote->options, 'package_title', ucfirst($packageCode ?: 'custom'));
  $displayTotal = (string) data_get($quote->options, 'display_total', '');
  $isFixedPackage = in_array($packageCode, ['essential', 'signature', 'prestige'], true);
  $canManagePipeline = in_array(strtolower((string) auth()->user()?->role), ['owner', 'admin', 'manager'], true);

  $humanizeValue = static function ($value): string {
    if (is_array($value)) {
      $items = array_values(array_filter(array_map(static function ($item): string {
        if (is_scalar($item) || $item === null) {
          return trim((string) $item);
        }

        return json_encode($item, JSON_UNESCAPED_SLASHES) ?: '';
      }, $value), static fn ($item): bool => $item !== ''));

      return count($items) > 0 ? implode(', ', $items) : '-';
    }

    if (is_bool($value)) {
      return $value ? 'yes' : 'no';
    }

    if (is_scalar($value) || $value === null) {
      $text = trim((string) $value);
      return $text !== '' ? $text : '-';
    }

    return json_encode($value, JSON_UNESCAPED_SLASHES) ?: '-';
  };

  $humanizePayload = static function ($payload) use ($humanizeValue): array {
    if (!is_array($payload) || count($payload) === 0) {
      return [];
    }

    $lines = [];
    foreach ($payload as $key => $value) {
      $label = \Illuminate\Support\Str::headline((string) $key);
      $lines[] = $label . ': ' . $humanizeValue($value);
    }

    return $lines;
  };
@endphp
<section class="panel-grid">
  <article class="panel-card panel-stack">
    <h2 class="panel-section-title">Quote Details</h2>
    <p><strong>Package:</strong> {{ $packageTitle }}</p>
    <p><strong>Status:</strong> <span class="panel-badge">{{ $quote->status }}</span></p>
    <p><strong>Listing:</strong> {{ $quote->listing_type ?: '-' }}</p>
    <p><strong>Services:</strong> {{ is_array($quote->services) ? implode(', ', $quote->services) : '-' }}</p>
    <p><strong>Total:</strong>
      @if($displayTotal !== '')
        {{ $displayTotal }} {{ $quote->currency }}
      @else
        {{ number_format((int) $quote->estimated_total) }} {{ $quote->currency }}
      @endif
    </p>
    <p><strong>Internal note:</strong> {{ $quote->notes ?: '-' }}</p>
  </article>

  <article class="panel-card">
    <h2 class="panel-section-title">Line Items</h2>
    <div class="panel-table-wrap">
      <table class="panel-table">
        <thead><tr><th>Label</th><th>Amount</th></tr></thead>
        <tbody>
          @forelse(($quote->line_items ?? []) as $item)
          <tr>
            <td>{{ $item['label'] ?? '-' }}</td>
            <td>{{ number_format((int) ($item['amount'] ?? 0)) }} {{ $quote->currency }}</td>
          </tr>
          @empty
          <tr><td colspan="2" class="panel-muted">No line items.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </article>

  <article class="panel-card panel-stack">
    <h2 class="panel-section-title">Client Details</h2>
    <p><strong>Name:</strong> {{ data_get($quote->options, 'contact_name', $quote->leadProfile?->name ?: '-') }}</p>
    <p><strong>Email:</strong> {{ data_get($quote->options, 'contact_email', $quote->leadProfile?->email ?: '-') }}</p>
    <p><strong>Phone:</strong> {{ data_get($quote->options, 'contact_phone', $quote->leadProfile?->phone ?: '-') }}</p>
  </article>

  <article class="panel-card">
    <h2 class="panel-section-title">Quote Actions</h2>
    @if($canManagePipeline)
    <form method="post" action="{{ route('admin.quotes.status', $quote) }}" class="panel-stack">
      @csrf
      <select class="panel-select" name="status" required>
        @foreach(['new','reviewed','contacted','booked','lost'] as $status)
        <option value="{{ $status }}" @selected($quote->status === $status)>{{ ucfirst($status) }}</option>
        @endforeach
      </select>
      <textarea class="panel-textarea" name="note" placeholder="Optional note"></textarea>
      <button class="panel-btn panel-btn-primary" type="submit">Save status</button>
    </form>
    <hr class="panel-hr">
    <form method="post" action="{{ route('admin.quotes.resend-email', $quote) }}">
      @csrf
      <button class="panel-btn" type="submit">Resend email</button>
    </form>
    <hr class="panel-hr">
    <form method="post" action="{{ route('admin.quotes.delete', $quote) }}" onsubmit="return confirm('Delete this quote? This action cannot be undone.');">
      @csrf
      <button class="panel-btn panel-btn-danger panel-btn-icon" type="submit" title="Delete quote" aria-label="Delete quote"><span class="panel-icon-trash" aria-hidden="true"><svg viewBox="0 0 20 20"><path d="M5 6h10M8 6V4h4v2m-6 0l.5 9h7L14 6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span></button>
    </form>
    @else
    <p class="panel-muted">Read only access. Quote update actions are available for owner/admin/manager roles.</p>
    @endif
  </article>

  <article class="panel-card panel-stack">
    <h2 class="panel-section-title">Manual Quote Editor</h2>
    @if(!$canManagePipeline)
    <p class="panel-muted">Read only access. Manual editing is disabled for this role.</p>
    @elseif($isFixedPackage)
    <p class="panel-muted">This is a fixed preset package ({{ ucfirst($packageCode) }}). Line items are locked to keep exact package pricing/features.</p>
    @endif
    @if($canManagePipeline)
    <form method="post" action="{{ route('admin.quotes.line-items', $quote) }}" class="panel-stack" data-line-item-editor>
      @csrf
      <div class="panel-form-row">
        <input class="panel-input" type="text" name="currency" maxlength="8" value="{{ old('currency', $quote->currency) }}" placeholder="Currency (USD)" @disabled($isFixedPackage)>
        <textarea class="panel-textarea" name="notes" placeholder="Internal note" @disabled($isFixedPackage)>{{ old('notes', $quote->notes) }}</textarea>
      </div>

      <div class="panel-table-wrap">
        <table class="panel-table">
          <thead><tr><th>Label</th><th>Amount</th><th>Action</th></tr></thead>
          <tbody data-line-item-body>
            @php($oldItems = old('line_items', $quote->line_items ?? []))
            @foreach($oldItems as $index => $item)
            <tr>
              <td><input class="panel-input" type="text" name="line_items[{{ $index }}][label]" value="{{ $item['label'] ?? '' }}" maxlength="150" required @disabled($isFixedPackage)></td>
              <td><input class="panel-input" type="number" name="line_items[{{ $index }}][amount]" value="{{ (int) ($item['amount'] ?? 0) }}" min="0" required @disabled($isFixedPackage)></td>
              <td><button class="panel-btn" type="button" data-remove-line-item @disabled($isFixedPackage)>Remove</button></td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <div class="panel-form-row">
        <button class="panel-btn" type="button" data-add-line-item @disabled($isFixedPackage)>Add line item</button>
        <button class="panel-btn panel-btn-primary" type="submit" @disabled($isFixedPackage)>Save line items</button>
      </div>
    </form>
    @endif
  </article>

  <article class="panel-card">
    <h2 class="panel-section-title">Timeline</h2>
    <div class="panel-table-wrap">
      <table class="panel-table">
        <thead><tr><th>Time</th><th>Event</th><th>By</th><th>Payload</th></tr></thead>
        <tbody>
          @forelse($quote->events as $event)
          <tr>
            <td>{{ $event->created_at?->format('Y-m-d H:i') }}</td>
            <td>{{ $event->event_type }}</td>
            <td>{{ $event->creator?->email ?: 'system' }}</td>
            <td>
              @php($payloadLines = $humanizePayload($event->payload))
              @if(count($payloadLines) > 0)
                @foreach($payloadLines as $line)
                <div class="panel-muted" style="margin:0 0 4px;">{{ $line }}</div>
                @endforeach
              @else
              -
              @endif
            </td>
          </tr>
          @empty
          <tr><td colspan="4" class="panel-muted">No events.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </article>
</section>

<script>
  (function () {
    const form = document.querySelector('[data-line-item-editor]');
    if (!form) return;

    const body = form.querySelector('[data-line-item-body]');
    const addBtn = form.querySelector('[data-add-line-item]');
    if (!body || !addBtn) return;

    const nextIndex = () => body.querySelectorAll('tr').length;

    const makeRow = (index) => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td><input class="panel-input" type="text" name="line_items[${index}][label]" maxlength="150" required></td>
        <td><input class="panel-input" type="number" name="line_items[${index}][amount]" value="0" min="0" required></td>
        <td><button class="panel-btn" type="button" data-remove-line-item>Remove</button></td>
      `;
      return tr;
    };

    addBtn.addEventListener('click', function () {
      body.appendChild(makeRow(nextIndex()));
    });

    body.addEventListener('click', function (event) {
      const button = event.target.closest('[data-remove-line-item]');
      if (!button) return;
      const row = button.closest('tr');
      if (!row) return;
      if (body.querySelectorAll('tr').length <= 1) return;
      row.remove();
    });

    if (body.querySelectorAll('tr').length === 0) {
      body.appendChild(makeRow(0));
    }
  })();
</script>
@endsection
