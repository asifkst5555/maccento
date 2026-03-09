@extends('layouts.panel', [
  'title' => 'Invoices',
  'heading' => 'Invoices',
  'subheading' => 'Review billing, payment status, and downloadable invoice PDFs.',
])

@section('content')
<div class="client-portal-shell">
  @php
    $invoiceStatusClass = static function (?string $status): string {
      return 'client-status-chip status-' . \Illuminate\Support\Str::slug((string) $status);
    };
  @endphp
  <section class="panel-grid panel-grid-kpi-compact">
    <article class="client-portal-kpi">
      <span class="panel-kpi-label">Unpaid Invoices</span>
      <p class="client-portal-kpi-value">{{ $portalStats['unpaid_invoices'] }}</p>
    </article>
    <article class="client-portal-kpi">
      <span class="panel-kpi-label">Active Projects</span>
      <p class="client-portal-kpi-value">{{ $portalStats['active_projects'] }}</p>
    </article>
    <article class="client-portal-kpi">
      <span class="panel-kpi-label">Pending Quotes</span>
      <p class="client-portal-kpi-value">{{ $portalStats['pending_quotes'] }}</p>
    </article>
  </section>

  <section class="panel-card client-portal-table client-portal-card-accent">
    <div class="client-portal-section-head">
      <div class="client-portal-section-copy">
        <h2 class="panel-section-title" style="margin: 0;">Billing Records</h2>
        <p class="client-portal-subtle" style="margin: 8px 0 0;">Download invoice PDFs, review payment status, and keep your billing trail organized.</p>
      </div>
      <a class="panel-btn" href="{{ route('user.account.index') }}">Account Details</a>
    </div>
    <div class="panel-table-wrap">
      <table class="panel-table">
        <thead>
          <tr>
            <th>Invoice</th>
            <th>Project</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Due Date</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse($invoices as $invoice)
            <tr>
              <td>{{ $invoice->invoice_number }}</td>
              <td>{{ $invoice->project?->title ?: 'General invoice' }}</td>
              <td><span class="client-portal-money">{{ number_format((float) $invoice->amount, 2) }} {{ $invoice->currency }}</span></td>
              <td><span class="{{ $invoiceStatusClass($invoice->status) }}">{{ $invoice->status }}</span></td>
              <td>{{ $invoice->due_date?->format('Y-m-d') ?: '-' }}</td>
              <td><a class="panel-btn panel-btn-primary" href="{{ route('user.invoices.download', $invoice) }}">Download PDF</a></td>
            </tr>
          @empty
            <tr>
              <td colspan="6">
                <div class="client-portal-empty"><strong>No invoices yet</strong>Billing records will appear here once your projects move into invoicing.</div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <x-panel-pagination :paginator="$invoices" />
  </section>
</div>
@endsection
