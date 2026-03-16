@extends('layouts.panel', [
  'title' => 'Invoices',
  'heading' => 'Invoices',
  'subheading' => 'Track all client invoices with paid and unpaid visibility.',
])

@section('content')
<style>
  .corp-admin-shell {
    --corp-ink: #10233a;
    --corp-ink-soft: #586b83;
    --corp-line: #d6e0ec;
    --corp-surface: #ffffff;
    --corp-soft: #f3f7fc;
    --corp-accent: #c11f37;
    --corp-shadow: 0 14px 30px rgba(16, 35, 58, 0.08);
  }

  .corp-admin-shell .panel-card {
    border: 1px solid var(--corp-line);
    border-radius: 14px;
    background: var(--corp-surface);
    box-shadow: var(--corp-shadow);
  }

  .corp-admin-shell .panel-kpi-label {
    color: var(--corp-ink-soft);
    font-weight: 700;
    letter-spacing: 0.03em;
    text-transform: uppercase;
    font-size: 0.72rem;
  }

  .corp-admin-shell .panel-kpi-value {
    color: var(--corp-ink);
  }

  .corp-admin-shell .panel-muted {
    color: var(--corp-ink-soft);
  }

  .corp-admin-shell .panel-link {
    color: #143557;
    font-weight: 600;
    text-decoration: none;
  }

  .corp-admin-shell .panel-link:hover {
    color: var(--corp-accent);
  }

  .corp-admin-shell .panel-input,
  .corp-admin-shell .panel-select,
  .corp-admin-shell .panel-textarea {
    border-radius: 10px;
    border: 1px solid #c9d6e5;
    background-color: #fff;
  }

  .corp-admin-shell .panel-btn {
    border-radius: 10px;
    border: 1px solid #bfcfe0;
    font-weight: 600;
  }

  .corp-admin-shell .panel-btn-primary {
    background: linear-gradient(90deg, #b71d34 0%, #cc2741 100%);
    border-color: #a5172d;
  }

  .corp-admin-shell .panel-sticky-filters {
    background: linear-gradient(180deg, #f8fbff 0%, #eef4fb 100%);
    border: 1px solid #d9e3ef;
    border-radius: 12px;
    padding: 0.7rem;
  }

  .corp-admin-shell .panel-table-wrap {
    border: 1px solid var(--corp-line);
    border-radius: 12px;
    background: #fff;
    overflow-x: auto;
    overflow-y: visible;
  }
  .corp-admin-shell .panel-table-wrap::-webkit-scrollbar {
    height: 8px;
  }
  .corp-admin-shell .panel-table-wrap::-webkit-scrollbar-track {
    background: transparent;
  }
  .corp-admin-shell .panel-table-wrap::-webkit-scrollbar-thumb {
    background: rgba(193, 31, 55, 0.2);
    border-radius: 999px;
  }
  .corp-admin-shell .panel-table-wrap {
    scrollbar-width: thin;
    scrollbar-color: rgba(193, 31, 55, 0.25) transparent;
  }

  .corp-admin-shell .panel-table thead th {
    background: var(--corp-soft);
    color: #324963;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    font-size: 0.74rem;
  }

  .corp-admin-shell .panel-table tbody tr:nth-child(even) {
    background: #fbfdff;
  }

  .corp-admin-shell .panel-badge {
    border-radius: 999px;
    border: 1px solid #c5d3e3;
    background: #eff5fc;
    color: #203b59;
    font-weight: 700;
    font-size: 0.7rem;
    letter-spacing: 0.02em;
  }

  .corp-admin-shell .panel-row-overdue {
    background: #fff7f8;
  }

  .corp-admin-shell .row-between-center {
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
  }

  .corp-admin-shell .status-form-tight {
    margin-bottom: 0;
  }

  .corp-admin-shell .inline-delete-form {
    display: inline-block;
    margin-left: 8px;
  }

  .corp-admin-shell .invoice-table {
    border-collapse: separate;
    border-spacing: 0 12px;
  }

  .corp-admin-shell .invoice-row {
    background: #ffffff;
    box-shadow: 0 12px 24px rgba(16, 35, 58, 0.08);
  }

  .corp-admin-shell .invoice-row td {
    background: #ffffff;
    border-top: 1px solid #e3ecf5;
    border-bottom: 1px solid #e3ecf5;
  }

  .corp-admin-shell .invoice-row td:first-child {
    border-left: 1px solid #e3ecf5;
    border-top-left-radius: 12px;
    border-bottom-left-radius: 12px;
  }

  .corp-admin-shell .invoice-row td:last-child {
    border-right: 1px solid #e3ecf5;
    border-top-right-radius: 12px;
    border-bottom-right-radius: 12px;
  }

  .corp-admin-shell .invoice-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
  }

  .corp-admin-shell .invoice-action-form {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0;
  }

  .corp-admin-shell .invoice-action-save {
    background: linear-gradient(90deg, #b71d34 0%, #cc2741 100%);
    border-color: #a5172d;
    color: #ffffff;
  }

  .corp-admin-shell .invoice-action-save:hover {
    background: #a3172b;
  }

  .corp-admin-shell .invoice-action-group {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
  }

  .corp-admin-shell .invoice-action-label {
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.02em;
    text-transform: uppercase;
    color: var(--corp-ink-soft);
    margin-right: 4px;
  }

  .corp-admin-shell .invoice-action-details {
    border: 1px solid #d6e0ec;
    border-radius: 12px;
    padding: 8px;
    background: #f7faff;
  }

  .corp-admin-shell .invoice-action-details summary {
    list-style: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }

  .corp-admin-shell .invoice-action-details summary::-webkit-details-marker {
    display: none;
  }

  .corp-admin-shell .invoice-action-details[open] summary {
    margin-bottom: 8px;
  }

  .corp-admin-shell .invoice-quick-links {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
  }

  .corp-admin-shell .inline-delete-form {
    margin-left: 0;
    display: flex;
    align-items: center;
  }

  .corp-admin-shell .panel-btn-icon {
    width: 35px;
    height: 35px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }

  .corp-admin-shell .panel-btn-icon svg {
    width: 18px !important;
    height: 18px !important;
  }

  .corp-admin-shell .panel-btn-danger.panel-btn-icon {
    background: #b71d34;
    border-color: #9f162b;
    color: #ffffff;
  }

  .corp-admin-shell .panel-btn-danger.panel-btn-icon:hover {
    transform: scale(1.08);
  }

  .corp-admin-shell .status-badge {
    border-radius: 999px;
    font-weight: 700;
    text-transform: capitalize;
  }

  .corp-admin-shell .status-badge.status-paid {
    background: #e5f6ee;
    color: #1a7f4f;
    border-color: #cfeedd;
  }

  .corp-admin-shell .status-badge.status-overdue {
    background: #ffe8ec;
    color: #b5243b;
    border-color: #ffd1d9;
  }

  .corp-admin-shell .status-badge.status-sent,
  .corp-admin-shell .status-badge.status-partial {
    background: #e9f1ff;
    color: #2f5aa8;
    border-color: #d3e1fb;
  }

  .corp-admin-shell .status-badge.status-draft {
    background: #f0f3f7;
    color: #5a6c80;
    border-color: #e2e7ef;
  }

  @media (max-width: 1024px) {
    .corp-admin-shell .panel-sticky-filters {
      position: static;
    }
  }
</style>
<div class="corp-admin-shell">
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
  @if(!empty($filters['invoice_project']))
  <div class="panel-form-row row-between-center">
    <span class="panel-badge">Project Filter: {{ $filters['invoice_project_title'] ?: ('Project #' . $filters['invoice_project']) }}</span>
    <a class="panel-link" href="{{ route('admin.invoices.index') }}">Clear Project Filter</a>
  </div>
  @endif

  <div class="panel-sticky-filters">
    <form method="get" class="panel-form-row">
      @if(!empty($filters['invoice_project']))
      <input type="hidden" name="invoice_project" value="{{ $filters['invoice_project'] }}">
      @endif
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
    <table class="panel-table invoice-table">
      <thead>
        <tr>
          <th>Invoice</th>
          <th>Client</th>
          <th>Project</th>
          <th>Amount</th>
          <th>Paid</th>
          <th>Due Amount</th>
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
        <tr class="invoice-row {{ $isOverdue ? 'panel-row-overdue' : '' }}">
          <td>{{ $invoice->invoice_number }}</td>
          <td>
            {{ $invoice->client?->name ?: '-' }}<br>
            <span class="panel-muted">{{ $invoice->client?->email ?: ($invoice->client?->phone ?: '-') }}</span>
          </td>
          <td>{{ $invoice->project?->title ?: '-' }}</td>
          <td>{{ number_format((float) $invoice->amount, 2) }} {{ strtoupper((string) $invoice->currency) }}</td>
          <td>{{ number_format((float) ($invoice->amount_paid ?? 0), 2) }}</td>
          <td>{{ number_format((float) ($invoice->balance_due ?? $invoice->amount), 2) }}</td>
          <td><span class="panel-badge status-badge status-{{ $invoice->status }}">{{ $invoice->status }}</span></td>
          <td>{{ $invoice->issued_at?->format('Y-m-d') ?: '-' }}</td>
          <td>{{ $invoice->due_date?->format('Y-m-d') ?: '-' }}</td>
          <td>{{ $invoice->paid_at?->format('Y-m-d') ?: '-' }}</td>
          <td>
            <div class="invoice-actions">
              <div class="invoice-action-group">
                <span class="invoice-action-label">Status</span>
                <form method="post" action="{{ route('admin.invoices.status', $invoice) }}" class="invoice-action-form status-form-tight">
                  @csrf
                  <select class="panel-select" name="status">
                    @foreach(['draft','sent','partial','paid','overdue'] as $status)
                    <option value="{{ $status }}" @selected($invoice->status === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                  </select>
                  <button class="panel-btn invoice-action-save" type="submit">Update</button>
                </form>
              </div>

              <details class="invoice-action-details">
                <summary class="panel-btn">Record Payment</summary>
                <form method="post" action="{{ route('admin.invoices.payments.store', $invoice) }}" class="invoice-action-form status-form-tight">
                  @csrf
                  <input class="panel-input" style="max-width: 110px;" type="number" name="amount" min="0.01" step="0.01" placeholder="Amount" required>
                  <select class="panel-select" name="method">
                    <option value="manual">Manual</option>
                    <option value="bank_transfer">Bank</option>
                    <option value="cash">Cash</option>
                    <option value="cheque">Cheque</option>
                    <option value="stripe">Stripe</option>
                    <option value="paypal">PayPal</option>
                    <option value="other">Other</option>
                  </select>
                  <input class="panel-input" style="max-width: 120px;" type="text" name="reference" placeholder="Ref">
                  <button class="panel-btn invoice-action-save" type="submit">Add Payment</button>
                </form>
              </details>

              <div class="invoice-action-group">
                <span class="invoice-action-label">Quick links</span>
                <div class="invoice-quick-links">
                  @if($invoice->client)
                  <a class="panel-btn" href="{{ route('admin.clients.show', $invoice->client) }}">Client</a>
                  @endif
                  <a class="panel-btn" href="{{ route('admin.invoices.download', $invoice) }}">PDF</a>
                  <form method="post" action="{{ route('admin.invoices.delete', $invoice) }}" data-confirm="Delete invoice {{ $invoice->invoice_number }}?" class="inline-delete-form">
                    @csrf
                    <button class="panel-btn panel-btn-danger panel-btn-icon" type="submit" title="Delete invoice" aria-label="Delete invoice"><span class="panel-icon-trash" aria-hidden="true"><x-panel-icon name="trash" /></span></button>
                  </form>
                </div>
              </div>
            </div>
          </td>
        </tr>
        @empty
        <tr><td colspan="11" class="panel-muted">No invoices found.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <x-panel-pagination :paginator="$invoices" />
</section>

</div>
@endsection

