@extends('layouts.panel', [
  'title' => 'Admin CRM Dashboard',
  'heading' => 'Admin CRM Dashboard',
  'subheading' => 'Lead, quote, follow-up, and submission operations in one panel.',
])

@section('content')
@php
  $managerMode = (bool) ($widgetVisibility['is_manager'] ?? false);
@endphp

@if($managerMode)
<section class="panel-card panel-alert-strip">
  <span class="panel-badge">Manager Mode</span>
  Operations-focused compact dashboard active. Financial/export analytics are limited.
</section>
@endif

@if(!empty($dashboardError))
<section class="panel-card panel-alert-strip">
  <span class="panel-badge panel-badge-danger">System Notice</span>
  {{ $dashboardError }}
</section>
@endif

@if($stats['overdue_followups'] > 0)
<section class="panel-card panel-alert-strip">
  <span class="panel-badge panel-badge-danger">Overdue Alert</span>
  <strong>{{ $stats['overdue_followups'] }}</strong> follow-up{{ $stats['overdue_followups'] > 1 ? 's are' : ' is' }} overdue.
</section>
@endif

@if($managerMode)
<section class="panel-grid panel-grid-kpi panel-grid-kpi-compact">
  <article class="panel-card"><span class="panel-kpi-label">Total leads</span><p class="panel-kpi-value">{{ $stats['total_leads'] }}</p></article>
  <article class="panel-card"><span class="panel-kpi-label">Qualified leads</span><p class="panel-kpi-value">{{ $stats['qualified_leads'] }}</p></article>
  <article class="panel-card"><span class="panel-kpi-label">Pending follow-ups</span><p class="panel-kpi-value">{{ $stats['pending_followups'] }}</p></article>
  <article class="panel-card"><span class="panel-kpi-label">Overdue follow-ups</span><p class="panel-kpi-value">{{ $stats['overdue_followups'] }}</p></article>
  <article class="panel-card"><span class="panel-kpi-label">New leads</span><p class="panel-kpi-value">{{ (int) ($leadStatusSummary['new'] ?? 0) }}</p></article>
  <article class="panel-card"><span class="panel-kpi-label">Quotes booked</span><p class="panel-kpi-value">{{ $quoteKpi['booked'] }}</p></article>
  <article class="panel-card"><span class="panel-kpi-label">AI requests today</span><p class="panel-kpi-value">{{ $aiKpi['requests_today'] }}</p></article>
  <article class="panel-card"><span class="panel-kpi-label">AI tokens today</span><p class="panel-kpi-value">{{ number_format($aiKpi['tokens_today']) }}</p></article>
</section>
@else
<section class="panel-grid panel-grid-kpi">
  <article class="panel-card"><span class="panel-kpi-label">Total users</span><p class="panel-kpi-value">{{ $stats['total_users'] }}</p></article>
  <article class="panel-card"><span class="panel-kpi-label">Total leads</span><p class="panel-kpi-value">{{ $stats['total_leads'] }}</p></article>
  <article class="panel-card"><span class="panel-kpi-label">Qualified leads</span><p class="panel-kpi-value">{{ $stats['qualified_leads'] }}</p></article>
  <article class="panel-card"><span class="panel-kpi-label">Pending follow-ups</span><p class="panel-kpi-value">{{ $stats['pending_followups'] }}</p></article>
  <article class="panel-card"><span class="panel-kpi-label">Quotes total</span><p class="panel-kpi-value">{{ $quoteKpi['total'] }}</p></article>
  <article class="panel-card"><span class="panel-kpi-label">Quotes booked</span><p class="panel-kpi-value">{{ $quoteKpi['booked'] }}</p></article>
  <article class="panel-card"><span class="panel-kpi-label">Quote conversion</span><p class="panel-kpi-value">{{ $quoteKpi['conversion_rate'] }}%</p></article>
  @if($widgetVisibility['can_view_financial_widgets'])
  <article class="panel-card"><span class="panel-kpi-label">Avg quote value</span><p class="panel-kpi-value">{{ number_format($quoteKpi['avg_total']) }}</p></article>
  @endif
  <article class="panel-card"><span class="panel-kpi-label">AI requests today</span><p class="panel-kpi-value">{{ $aiKpi['requests_today'] }}</p></article>
  <article class="panel-card"><span class="panel-kpi-label">AI tokens today</span><p class="panel-kpi-value">{{ number_format($aiKpi['tokens_today']) }}</p></article>
  @if($widgetVisibility['can_view_cost_widgets'])
  <article class="panel-card"><span class="panel-kpi-label">AI cost today</span><p class="panel-kpi-value">${{ number_format($aiKpi['cost_today'], 4) }}</p></article>
  @endif
</section>

<section class="panel-card panel-stack">
  <h2 class="panel-section-title">Quote Conversion Funnel</h2>
  <form method="get" class="panel-form-row">
    <input class="panel-input" type="date" name="conversion_from_date" value="{{ $filters['conversion_from_date'] }}">
    <input class="panel-input" type="date" name="conversion_to_date" value="{{ $filters['conversion_to_date'] }}">
    <button class="panel-btn panel-btn-primary" type="submit">Apply Range</button>
    <a class="panel-link" href="{{ route('admin.dashboard') }}">Reset</a>
  </form>
  <div class="panel-grid panel-grid-kpi panel-grid-kpi-compact">
    <article class="panel-card"><span class="panel-kpi-label">Quotes in range</span><p class="panel-kpi-value">{{ $conversionAnalytics['total'] }}</p></article>
    <article class="panel-card"><span class="panel-kpi-label">Reviewed</span><p class="panel-kpi-value">{{ $conversionAnalytics['reviewed'] }}</p></article>
    <article class="panel-card"><span class="panel-kpi-label">Contacted</span><p class="panel-kpi-value">{{ $conversionAnalytics['contacted'] }}</p></article>
    <article class="panel-card"><span class="panel-kpi-label">Booked</span><p class="panel-kpi-value">{{ $conversionAnalytics['booked'] }}</p></article>
    <article class="panel-card"><span class="panel-kpi-label">Lost</span><p class="panel-kpi-value">{{ $conversionAnalytics['lost'] }}</p></article>
    <article class="panel-card"><span class="panel-kpi-label">Booking rate</span><p class="panel-kpi-value">{{ $conversionAnalytics['booking_rate'] }}%</p></article>
    <article class="panel-card"><span class="panel-kpi-label">Contact rate</span><p class="panel-kpi-value">{{ $conversionAnalytics['contact_rate'] }}%</p></article>
    <article class="panel-card"><span class="panel-kpi-label">Avg value</span><p class="panel-kpi-value">{{ number_format((int) $conversionAnalytics['avg_total']) }}</p></article>
  </div>
</section>

@php
  $funnelChart = $dashboardCharts['funnel'] ?? [];
  $funnelMax = max(1, (int) ($dashboardCharts['funnel_max'] ?? 1));
  $leadStatusChart = $dashboardCharts['lead_status'] ?? [];
  $leadStatusMax = max(1, (int) ($dashboardCharts['lead_status_max'] ?? 1));
  $quoteStatusChart = $dashboardCharts['quote_status'] ?? [];
  $quoteStatusMax = max(1, (int) ($dashboardCharts['quote_status_max'] ?? 1));
  $trendLabels = $dashboardCharts['trend']['labels'] ?? [];
  $leadTrendPoints = (string) ($dashboardCharts['trend']['lead_points'] ?? '');
  $quoteTrendPoints = (string) ($dashboardCharts['trend']['quote_points'] ?? '');
@endphp

<section class="panel-grid panel-chart-grid">
  <article class="panel-card panel-stack">
    <h2 class="panel-section-title">Funnel Progress Chart</h2>
    <div class="panel-chart-list">
      @foreach($funnelChart as $item)
      @php($width = $funnelMax > 0 ? round(($item['value'] / $funnelMax) * 100, 1) : 0)
      <div class="panel-chart-row">
        <div class="panel-chart-label">{{ $item['label'] }}</div>
        <div class="panel-chart-track">
          <span class="panel-chart-fill" style="width:{{ $width }}%"></span>
        </div>
        <div class="panel-chart-value">{{ $item['value'] }}</div>
      </div>
      @endforeach
    </div>
  </article>

  <article class="panel-card panel-stack">
    <h2 class="panel-section-title">Status Distribution</h2>
    <div class="panel-chart-split">
      <div>
        <p class="panel-chart-subtitle">Leads</p>
        <div class="panel-chart-list">
          @forelse($leadStatusChart as $item)
          @php($width = $leadStatusMax > 0 ? round(($item['count'] / $leadStatusMax) * 100, 1) : 0)
          <div class="panel-chart-row">
            <div class="panel-chart-label">{{ $item['status'] }}</div>
            <div class="panel-chart-track">
              <span class="panel-chart-fill panel-chart-fill-leads" style="width:{{ $width }}%"></span>
            </div>
            <div class="panel-chart-value">{{ $item['count'] }}</div>
          </div>
          @empty
          <p class="panel-muted">No lead data yet.</p>
          @endforelse
        </div>
      </div>
      <div>
        <p class="panel-chart-subtitle">Quotes</p>
        <div class="panel-chart-list">
          @forelse($quoteStatusChart as $item)
          @php($width = $quoteStatusMax > 0 ? round(($item['count'] / $quoteStatusMax) * 100, 1) : 0)
          <div class="panel-chart-row">
            <div class="panel-chart-label">{{ $item['status'] }}</div>
            <div class="panel-chart-track">
              <span class="panel-chart-fill panel-chart-fill-quotes" style="width:{{ $width }}%"></span>
            </div>
            <div class="panel-chart-value">{{ $item['count'] }}</div>
          </div>
          @empty
          <p class="panel-muted">No quote data yet.</p>
          @endforelse
        </div>
      </div>
    </div>
  </article>

  <article class="panel-card panel-stack">
    <h2 class="panel-section-title">7-Day Lead vs Quote Trend</h2>
    <div class="panel-line-chart">
      <svg viewBox="0 0 360 120" role="img" aria-label="7 day trend chart">
        <line x1="8" y1="112" x2="352" y2="112" class="panel-line-chart-axis" />
        <polyline points="{{ $leadTrendPoints }}" class="panel-line-chart-line panel-line-chart-line-leads" />
        <polyline points="{{ $quoteTrendPoints }}" class="panel-line-chart-line panel-line-chart-line-quotes" />
      </svg>
      <div class="panel-line-chart-legend">
        <span><i class="panel-line-chart-dot panel-line-chart-dot-leads"></i> Leads</span>
        <span><i class="panel-line-chart-dot panel-line-chart-dot-quotes"></i> Quotes</span>
      </div>
      <div class="panel-line-chart-labels">
        @foreach($trendLabels as $label)
        <span>{{ $label }}</span>
        @endforeach
      </div>
    </div>
  </article>
</section>

<section class="panel-grid">
  <article class="panel-card">
    <h2 class="panel-section-title">Lead Status Breakdown</h2>
    <div class="panel-table-wrap">
      <table class="panel-table">
        <thead><tr><th>Status</th><th>Count</th></tr></thead>
        <tbody>
          @forelse($leadStatusSummary as $status => $count)
          <tr>
            <td><span class="panel-badge">{{ $status }}</span></td>
            <td>{{ $count }}</td>
          </tr>
          @empty
          <tr><td colspan="2" class="panel-muted">No lead data yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </article>

  <article class="panel-card">
    <h2 class="panel-section-title">Quote Status Breakdown</h2>
    <div class="panel-table-wrap">
      <table class="panel-table">
        <thead><tr><th>Status</th><th>Count</th></tr></thead>
        <tbody>
          @forelse($quoteStatusSummary as $status => $count)
          <tr>
            <td><span class="panel-badge">{{ $status }}</span></td>
            <td>{{ $count }}</td>
          </tr>
          @empty
          <tr><td colspan="2" class="panel-muted">No quote data yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </article>
</section>
@endif

<section id="pending-followups" class="panel-card">
  <h2 class="panel-section-title">Pending Follow-ups</h2>
  <div class="panel-sticky-filters">
    <div class="panel-form-row">
      @if($widgetVisibility['can_export_data'])
      <form method="get" action="{{ route('admin.exports.followups') }}" class="panel-form-row">
        <input type="hidden" name="status" value="pending">
        <input class="panel-input" type="date" name="from_date" value="{{ $filters['followups_from_date'] }}">
        <input class="panel-input" type="date" name="to_date" value="{{ $filters['followups_to_date'] }}">
        <button class="panel-btn" type="submit">Export CSV</button>
      </form>
      @else
      <span class="panel-badge">Manager: export disabled</span>
      @endif
    </div>
  </div>
  <div class="panel-table-wrap">
    <table class="panel-table">
      <thead><tr><th>Due</th><th>Lead</th><th>Method</th><th>Owner</th><th>Status</th><th>Action</th></tr></thead>
      <tbody>
        @forelse($pendingFollowUps as $followUp)
        <tr class="{{ $followUp->due_at && $followUp->due_at->isPast() ? 'panel-row-overdue' : '' }}">
          <td>{{ $followUp->due_at?->format('Y-m-d H:i') ?: '-' }}</td>
          <td>
            <a class="panel-link" href="{{ route('admin.leads.show', $followUp->leadProfile) }}">
              {{ $followUp->leadProfile?->name ?: ('Lead #' . $followUp->lead_profile_id) }}
            </a>
          </td>
          <td>{{ strtoupper($followUp->method) }}</td>
          <td>{{ $followUp->owner?->name ?: '-' }}</td>
          <td>
            <span class="panel-badge">{{ $followUp->status }}</span>
            @if($followUp->due_at && $followUp->due_at->isPast())
            <span class="panel-badge panel-badge-danger">Overdue</span>
            @endif
          </td>
          <td>
            @if($widgetVisibility['can_manage_pipeline'])
              <form method="post" action="{{ route('admin.follow-ups.status', $followUp) }}">
                @csrf
                <input type="hidden" name="status" value="completed">
                <button class="panel-btn" type="submit">Mark completed</button>
              </form>
            @else
              <span class="panel-badge">Read only</span>
            @endif
          </td>
        </tr>
        @empty
        <tr><td colspan="6" class="panel-muted">No pending follow-ups.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</section>

@endsection
