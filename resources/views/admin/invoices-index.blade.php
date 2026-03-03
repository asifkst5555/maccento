@extends('layouts.panel', [
  'title' => 'Invoices',
  'heading' => 'Invoices',
  'subheading' => 'Track all client invoices with paid and unpaid visibility.',
])

@section('content')
<section class="panel-grid panel-grid-kpi">
  <article class="panel-card">
    <span class="panel-kpi-label">Total invoices</span>
    <p class="panel-kpi-value">{{ $kpi['total_invoices'] }}</p>
  </article>
  <article class="panel-card">
    <span class="panel-kpi-label">Paid invoices</span>
    <p class="panel-kpi-value">{{ $kpi['paid_invoices'] }}</p>
  </article>
  <article class="panel-card">
    <span class="panel-kpi-label">Unpaid invoices</span>
    <p class="panel-kpi-value">{{ $kpi['unpaid_invoices'] }}</p>
  </article>
  <article class="panel-card">
    <span class="panel-kpi-label">Overdue invoices</span>
    <p class="panel-kpi-value">{{ $kpi['overdue_invoices'] }}</p>
  </article>
  <article class="panel-card">
    <span class="panel-kpi-label">Total amount</span>
    <p class="panel-kpi-value">{{ number_format($kpi['total_amount'], 2) }}</p>
  </article>
  <article class="panel-card">
    <span class="panel-kpi-label">Paid amount</span>
    <p class="panel-kpi-value">{{ number_format($kpi['paid_amount'], 2) }}</p>
  </article>
  <article class="panel-card">
    <span class="panel-kpi-label">Unpaid amount</span>
    <p class="panel-kpi-value">{{ number_format($kpi['unpaid_amount'], 2) }}</p>
  </article>
</section>

<section class="panel-card">
  <div class="panel-sticky-filters">
    <form method="get" class="panel-form-row">
      <input class="panel-input" type="text" name="invoice_search" placeholder="Search invoice/client/project" value="{{ $filters['invoice_search'] }}">
      <select class="panel-select" name="invoice_status">
        <option value="">All invoices</option>
        <option value="paid" @selected($filters['invoice_status'] === 'paid')>Paid</option>
        <option value="unpaid" @selected($filters['invoice_status'] === 'unpaid')>Unpaid</option>
        <option value="draft" @selected($filters['invoice_status'] === 'draft')>Draft</option>
        <option value="sent" @selected($filters['invoice_status'] === 'sent')>Sent</option>
        <option value="partial" @selected($filters['invoice_status'] === 'partial')>Partial</option>
        <option value="overdue" @selected($filters['invoice_status'] === 'overdue')>Overdue</option>
      </select>
      <button class="panel-btn panel-btn-primary" type="submit">Filter</button>
      <a class="panel-link" href="{{ route('admin.invoices.index') }}">Clear</a>
    </form>
  </div>

  <div class="panel-table-wrap">
    <table class="panel-table">
      <thead>
        <tr>
          <th>Invoice</th>
          <th>Client</th>
          <th>Project</th>
          <th>Amount</th>
          <th>Status</th>
          <th>Issued</th>
          <th>Due</th>
          <th>Paid At</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($invoices as $invoice)
        @php
          $isOverdue = $invoice->status !== 'paid' && $invoice->due_date && $invoice->due_date->isPast();
        @endphp
        <tr class="{{ $isOverdue ? 'panel-row-overdue' : '' }}">
          <td>{{ $invoice->invoice_number }}</td>
          <td>
            {{ $invoice->client?->name ?: '-' }}<br>
            <span class="panel-muted">{{ $invoice->client?->email ?: ($invoice->client?->phone ?: '-') }}</span>
          </td>
          <td>{{ $invoice->project?->title ?: '-' }}</td>
          <td>{{ number_format((float) $invoice->amount, 2) }} {{ strtoupper((string) $invoice->currency) }}</td>
          <td><span class="panel-badge">{{ $invoice->status }}</span></td>
          <td>{{ $invoice->issued_at?->format('Y-m-d') ?: '-' }}</td>
          <td>{{ $invoice->due_date?->format('Y-m-d') ?: '-' }}</td>
          <td>{{ $invoice->paid_at?->format('Y-m-d H:i') ?: '-' }}</td>
          <td>
            <form method="post" action="{{ route('admin.invoices.status', $invoice) }}" class="panel-form-row" style="margin-bottom:6px;">
              @csrf
              <select class="panel-select" name="status">
                @foreach(['draft','sent','partial','paid','overdue'] as $status)
                <option value="{{ $status }}" @selected($invoice->status === $status)>{{ ucfirst($status) }}</option>
                @endforeach
              </select>
              <button class="panel-btn" type="submit">Save</button>
            </form>
            @if($invoice->client)
            <a class="panel-link" href="{{ route('admin.clients.show', $invoice->client) }}">Open client</a>
            @endif
          </td>
        </tr>
        @empty
        <tr><td colspan="9" class="panel-muted">No invoices found.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <x-panel-pagination :paginator="$invoices" />
</section>
@endsection
