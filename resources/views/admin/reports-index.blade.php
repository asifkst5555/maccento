@extends('layouts.panel', [
  'title' => 'Reports',
  'heading' => 'Reports',
  'subheading' => 'Track revenue, conversions, and operational performance.',
])

@section('content')
<div class="corp-admin-shell panel-stack">
  <section class="panel-card panel-stack">
    <div class="panel-form-row" style="justify-content: space-between; align-items: center;">
      <div>
        <span class="panel-badge">Analytics</span>
        <h2 class="panel-section-title" style="margin-top: 12px;">Performance Overview</h2>
        <p class="panel-muted">Reports are filtered by date range. Paid revenue uses the paid date.</p>
      </div>
      <form method="get" action="{{ route('admin.reports.index') }}" class="panel-form-row" style="margin-bottom: 0;">
        <input class="panel-input" type="date" name="from" value="{{ $fromDate }}">
        <input class="panel-input" type="date" name="to" value="{{ $toDate }}">
        <button class="panel-btn panel-btn-primary" type="submit">Apply</button>
      </form>
    </div>
  </section>

  <section class="panel-card panel-grid" style="grid-template-columns: repeat(4, minmax(0, 1fr));">
    <div class="panel-stat-card">
      <div class="panel-stat-label">Invoices Issued</div>
      <div class="panel-stat-value">{{ $issuedCount }}</div>
      <div class="panel-muted">Range: {{ $fromDate }} to {{ $toDate }}</div>
    </div>
    <div class="panel-stat-card">
      <div class="panel-stat-label">Invoices Paid</div>
      <div class="panel-stat-value">{{ $paidCount }}</div>
      <div class="panel-muted">Paid within range</div>
    </div>
    <div class="panel-stat-card">
      <div class="panel-stat-label">Invoices Unpaid</div>
      <div class="panel-stat-value">{{ $unpaidCount }}</div>
      <div class="panel-muted">Sent / Partial / Overdue</div>
    </div>
    <div class="panel-stat-card">
      <div class="panel-stat-label">Avg Days to Pay</div>
      <div class="panel-stat-value">{{ $avgDaysToPay ?? 'n/a' }}</div>
      <div class="panel-muted">Based on paid invoices</div>
    </div>
  </section>

  <section class="panel-card panel-grid" style="grid-template-columns: repeat(4, minmax(0, 1fr));">
    <div class="panel-stat-card">
      <div class="panel-stat-label">Leads Created</div>
      <div class="panel-stat-value">{{ $leadsCreated }}</div>
      <div class="panel-muted">New leads in range</div>
    </div>
    <div class="panel-stat-card">
      <div class="panel-stat-label">Leads Won</div>
      <div class="panel-stat-value">{{ $leadsWon }}</div>
      <div class="panel-muted">Updated in range</div>
    </div>
    <div class="panel-stat-card">
      <div class="panel-stat-label">Quotes Created</div>
      <div class="panel-stat-value">{{ $quotesCreated }}</div>
      <div class="panel-muted">New quotes in range</div>
    </div>
    <div class="panel-stat-card">
      <div class="panel-stat-label">Quotes Booked</div>
      <div class="panel-stat-value">{{ $quotesBooked }}</div>
      <div class="panel-muted">Updated in range</div>
    </div>
  </section>

  <section class="panel-card panel-grid" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
    <div class="panel-stat-card">
      <div class="panel-stat-label">Projects Completed</div>
      <div class="panel-stat-value">{{ $projectsCompleted }}</div>
      <div class="panel-muted">Updated in range</div>
    </div>
    <div class="panel-stat-card">
      <div class="panel-stat-label">Projects Overdue</div>
      <div class="panel-stat-value">{{ $projectsOverdue }}</div>
      <div class="panel-muted">Past due date</div>
    </div>
  </section>

  <section class="panel-card panel-stack">
    <h3 class="panel-section-title">Revenue by Currency</h3>
    <div class="panel-table-wrap">
      <table class="panel-table">
        <thead>
          <tr>
            <th>Currency</th>
            <th>Paid Revenue</th>
            <th>Outstanding</th>
          </tr>
        </thead>
        <tbody>
          @forelse($revenueByCurrency as $row)
            @php
              $outstandingRow = $outstandingByCurrency->firstWhere('currency', $row->currency);
            @endphp
            <tr>
              <td data-label="Currency">{{ $row->currency ?? '-' }}</td>
              <td data-label="Paid Revenue">{{ number_format((float) ($row->total ?? 0), 2) }}</td>
              <td data-label="Outstanding">{{ number_format((float) ($outstandingRow->total ?? 0), 2) }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="3" class="panel-muted">No revenue data in this range.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>

  <section class="panel-card panel-stack">
    <h3 class="panel-section-title">Overdue Aging</h3>
    <div class="panel-table-wrap">
      <table class="panel-table">
        <thead>
          <tr>
            <th>0-7 Days</th>
            <th>8-14 Days</th>
            <th>15-30 Days</th>
            <th>31+ Days</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td data-label="0-7">{{ $overdueAging['0_7'] }}</td>
            <td data-label="8-14">{{ $overdueAging['8_14'] }}</td>
            <td data-label="15-30">{{ $overdueAging['15_30'] }}</td>
            <td data-label="31+">{{ $overdueAging['31_plus'] }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>

  <section class="panel-card panel-stack">
    <h3 class="panel-section-title">Top Clients by Revenue</h3>
    <div class="panel-table-wrap">
      <table class="panel-table">
        <thead>
          <tr>
            <th>Client</th>
            <th>Email</th>
            <th>Currency</th>
            <th>Paid Revenue</th>
            <th>Invoices</th>
          </tr>
        </thead>
        <tbody>
          @forelse($topClients as $client)
            <tr>
              <td data-label="Client">{{ $client->name ?? ('Client #' . $client->id) }}</td>
              <td data-label="Email">{{ $client->email ?? '-' }}</td>
              <td data-label="Currency">{{ $client->currency ?? '-' }}</td>
              <td data-label="Paid Revenue">{{ number_format((float) ($client->total ?? 0), 2) }}</td>
              <td data-label="Invoices">{{ $client->invoices_count ?? 0 }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="panel-muted">No paid invoices in this range.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>
</div>
@endsection
