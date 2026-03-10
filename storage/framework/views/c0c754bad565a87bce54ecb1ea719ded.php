<?php $__env->startSection('content'); ?>
<?php
  $managerMode = (bool) ($widgetVisibility['is_manager'] ?? false);
?>

<section class="dashboard-v2 panel-stack dashboard-density-compact">

<div class="dash-density-toggle" role="group" aria-label="Dashboard view density">
  <span class="dash-density-label">View</span>
  <button type="button" class="dash-density-btn" data-density="premium">Premium</button>
  <button type="button" class="dash-density-btn is-active" data-density="compact">Compact</button>
</div>

<?php if($managerMode): ?>
<section class="panel-card panel-alert-strip">
  <span class="panel-badge">Manager Mode</span>
  Operations-focused compact dashboard active. Financial/export analytics are limited.
</section>
<?php endif; ?>

<?php if(!empty($dashboardError)): ?>
<section class="panel-card panel-alert-strip">
  <span class="panel-badge panel-badge-danger">System Notice</span>
  <?php echo e($dashboardError); ?>

</section>
<?php endif; ?>

<?php if($stats['overdue_followups'] > 0): ?>
<section class="panel-card panel-alert-strip">
  <span class="panel-badge panel-badge-danger">Overdue Alert</span>
  <strong><?php echo e($stats['overdue_followups']); ?></strong> follow-up<?php echo e($stats['overdue_followups'] > 1 ? 's are' : ' is'); ?> overdue.
</section>
<?php endif; ?>

<?php if($managerMode): ?>
<section class="panel-grid panel-grid-kpi panel-grid-kpi-compact">
  <article class="panel-card"><span class="panel-kpi-label">Total leads</span><p class="panel-kpi-value"><?php echo e($stats['total_leads']); ?></p></article>
  <article class="panel-card"><span class="panel-kpi-label">Qualified leads</span><p class="panel-kpi-value"><?php echo e($stats['qualified_leads']); ?></p></article>
  <article class="panel-card"><span class="panel-kpi-label">Pending follow-ups</span><p class="panel-kpi-value"><?php echo e($stats['pending_followups']); ?></p></article>
  <article class="panel-card"><span class="panel-kpi-label">Overdue follow-ups</span><p class="panel-kpi-value"><?php echo e($stats['overdue_followups']); ?></p></article>
  <article class="panel-card"><span class="panel-kpi-label">New leads</span><p class="panel-kpi-value"><?php echo e((int) ($leadStatusSummary['new'] ?? 0)); ?></p></article>
  <article class="panel-card"><span class="panel-kpi-label">Quotes booked</span><p class="panel-kpi-value"><?php echo e($quoteKpi['booked']); ?></p></article>
  <article class="panel-card"><span class="panel-kpi-label">AI requests today</span><p class="panel-kpi-value"><?php echo e($aiKpi['requests_today']); ?></p></article>
  <article class="panel-card"><span class="panel-kpi-label">AI tokens today</span><p class="panel-kpi-value"><?php echo e(number_format($aiKpi['tokens_today'])); ?></p></article>
</section>
<?php else: ?>
<section class="panel-grid panel-grid-kpi">
  <article class="panel-card"><span class="panel-kpi-label">Total users</span><p class="panel-kpi-value"><?php echo e($stats['total_users']); ?></p></article>
  <article class="panel-card"><span class="panel-kpi-label">Total leads</span><p class="panel-kpi-value"><?php echo e($stats['total_leads']); ?></p></article>
  <article class="panel-card"><span class="panel-kpi-label">Qualified leads</span><p class="panel-kpi-value"><?php echo e($stats['qualified_leads']); ?></p></article>
  <article class="panel-card"><span class="panel-kpi-label">Pending follow-ups</span><p class="panel-kpi-value"><?php echo e($stats['pending_followups']); ?></p></article>
  <article class="panel-card"><span class="panel-kpi-label">Quotes total</span><p class="panel-kpi-value"><?php echo e($quoteKpi['total']); ?></p></article>
  <article class="panel-card"><span class="panel-kpi-label">Quotes booked</span><p class="panel-kpi-value"><?php echo e($quoteKpi['booked']); ?></p></article>
  <article class="panel-card"><span class="panel-kpi-label">Quote conversion</span><p class="panel-kpi-value"><?php echo e($quoteKpi['conversion_rate']); ?>%</p></article>
  <?php if($widgetVisibility['can_view_financial_widgets']): ?>
  <article class="panel-card"><span class="panel-kpi-label">Avg quote value</span><p class="panel-kpi-value"><?php echo e(number_format($quoteKpi['avg_total'])); ?></p></article>
  <?php endif; ?>
  <article class="panel-card"><span class="panel-kpi-label">AI requests today</span><p class="panel-kpi-value"><?php echo e($aiKpi['requests_today']); ?></p></article>
  <article class="panel-card"><span class="panel-kpi-label">AI tokens today</span><p class="panel-kpi-value"><?php echo e(number_format($aiKpi['tokens_today'])); ?></p></article>
  <?php if($widgetVisibility['can_view_cost_widgets']): ?>
  <article class="panel-card"><span class="panel-kpi-label">AI cost today</span><p class="panel-kpi-value">$<?php echo e(number_format($aiKpi['cost_today'], 4)); ?></p></article>
  <?php endif; ?>
</section>

<section class="panel-card panel-stack">
  <h2 class="panel-section-title">Quote Conversion Funnel</h2>
  <form method="get" class="panel-form-row">
    <input class="panel-input" type="date" name="conversion_from_date" value="<?php echo e($filters['conversion_from_date']); ?>">
    <input class="panel-input" type="date" name="conversion_to_date" value="<?php echo e($filters['conversion_to_date']); ?>">
    <button class="panel-btn panel-btn-primary" type="submit">Apply Range</button>
    <a class="panel-link" href="<?php echo e(route('admin.dashboard')); ?>">Reset</a>
  </form>
  <div class="panel-grid panel-grid-kpi panel-grid-kpi-compact">
    <article class="panel-card"><span class="panel-kpi-label">Quotes in range</span><p class="panel-kpi-value"><?php echo e($conversionAnalytics['total']); ?></p></article>
    <article class="panel-card"><span class="panel-kpi-label">Reviewed</span><p class="panel-kpi-value"><?php echo e($conversionAnalytics['reviewed']); ?></p></article>
    <article class="panel-card"><span class="panel-kpi-label">Contacted</span><p class="panel-kpi-value"><?php echo e($conversionAnalytics['contacted']); ?></p></article>
    <article class="panel-card"><span class="panel-kpi-label">Booked</span><p class="panel-kpi-value"><?php echo e($conversionAnalytics['booked']); ?></p></article>
    <article class="panel-card"><span class="panel-kpi-label">Lost</span><p class="panel-kpi-value"><?php echo e($conversionAnalytics['lost']); ?></p></article>
    <article class="panel-card"><span class="panel-kpi-label">Booking rate</span><p class="panel-kpi-value"><?php echo e($conversionAnalytics['booking_rate']); ?>%</p></article>
    <article class="panel-card"><span class="panel-kpi-label">Contact rate</span><p class="panel-kpi-value"><?php echo e($conversionAnalytics['contact_rate']); ?>%</p></article>
    <article class="panel-card"><span class="panel-kpi-label">Avg value</span><p class="panel-kpi-value"><?php echo e(number_format((int) $conversionAnalytics['avg_total'])); ?></p></article>
  </div>
</section>

<?php
  $funnelChart = $dashboardCharts['funnel'] ?? [];
  $funnelMax = max(1, (int) ($dashboardCharts['funnel_max'] ?? 1));
  $leadStatusChart = $dashboardCharts['lead_status'] ?? [];
  $leadStatusMax = max(1, (int) ($dashboardCharts['lead_status_max'] ?? 1));
  $quoteStatusChart = $dashboardCharts['quote_status'] ?? [];
  $quoteStatusMax = max(1, (int) ($dashboardCharts['quote_status_max'] ?? 1));
  $trendLabels = $dashboardCharts['trend']['labels'] ?? [];
  $leadTrendPoints = (string) ($dashboardCharts['trend']['lead_points'] ?? '');
  $quoteTrendPoints = (string) ($dashboardCharts['trend']['quote_points'] ?? '');
?>

<section class="panel-grid panel-chart-grid">
  <article class="panel-card panel-stack">
    <h2 class="panel-section-title">Funnel Progress Chart</h2>
    <div class="panel-chart-list">
      <?php $__currentLoopData = $funnelChart; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <?php ($width = $funnelMax > 0 ? round(($item['value'] / $funnelMax) * 100, 1) : 0); ?>
      <div class="panel-chart-row">
        <div class="panel-chart-label"><?php echo e($item['label']); ?></div>
        <div class="panel-chart-track">
          <span class="panel-chart-fill" style="width:<?php echo e($width); ?>%"></span>
        </div>
        <div class="panel-chart-value"><?php echo e($item['value']); ?></div>
      </div>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
  </article>

  <article class="panel-card panel-stack">
    <h2 class="panel-section-title">Status Distribution</h2>
    <div class="panel-chart-split">
      <div>
        <p class="panel-chart-subtitle">Leads</p>
        <div class="panel-chart-list">
          <?php $__empty_1 = true; $__currentLoopData = $leadStatusChart; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
          <?php ($width = $leadStatusMax > 0 ? round(($item['count'] / $leadStatusMax) * 100, 1) : 0); ?>
          <div class="panel-chart-row">
            <div class="panel-chart-label"><?php echo e($item['status']); ?></div>
            <div class="panel-chart-track">
              <span class="panel-chart-fill panel-chart-fill-leads" style="width:<?php echo e($width); ?>%"></span>
            </div>
            <div class="panel-chart-value"><?php echo e($item['count']); ?></div>
          </div>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
          <p class="panel-muted">No lead data yet.</p>
          <?php endif; ?>
        </div>
      </div>
      <div>
        <p class="panel-chart-subtitle">Quotes</p>
        <div class="panel-chart-list">
          <?php $__empty_1 = true; $__currentLoopData = $quoteStatusChart; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
          <?php ($width = $quoteStatusMax > 0 ? round(($item['count'] / $quoteStatusMax) * 100, 1) : 0); ?>
          <div class="panel-chart-row">
            <div class="panel-chart-label"><?php echo e($item['status']); ?></div>
            <div class="panel-chart-track">
              <span class="panel-chart-fill panel-chart-fill-quotes" style="width:<?php echo e($width); ?>%"></span>
            </div>
            <div class="panel-chart-value"><?php echo e($item['count']); ?></div>
          </div>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
          <p class="panel-muted">No quote data yet.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </article>

  <article class="panel-card panel-stack">
    <h2 class="panel-section-title">7-Day Lead vs Quote Trend</h2>
    <div class="panel-line-chart">
      <svg viewBox="0 0 360 120" role="img" aria-label="7 day trend chart">
        <line x1="8" y1="112" x2="352" y2="112" class="panel-line-chart-axis" />
        <polyline points="<?php echo e($leadTrendPoints); ?>" class="panel-line-chart-line panel-line-chart-line-leads" />
        <polyline points="<?php echo e($quoteTrendPoints); ?>" class="panel-line-chart-line panel-line-chart-line-quotes" />
      </svg>
      <div class="panel-line-chart-legend">
        <span><i class="panel-line-chart-dot panel-line-chart-dot-leads"></i> Leads</span>
        <span><i class="panel-line-chart-dot panel-line-chart-dot-quotes"></i> Quotes</span>
      </div>
      <div class="panel-line-chart-labels">
        <?php $__currentLoopData = $trendLabels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <span><?php echo e($label); ?></span>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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
          <?php $__empty_1 = true; $__currentLoopData = $leadStatusSummary; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status => $count): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
          <tr>
            <td><span class="panel-badge"><?php echo e($status); ?></span></td>
            <td><?php echo e($count); ?></td>
          </tr>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
          <tr><td colspan="2" class="panel-muted">No lead data yet.</td></tr>
          <?php endif; ?>
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
          <?php $__empty_1 = true; $__currentLoopData = $quoteStatusSummary; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status => $count): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
          <tr>
            <td><span class="panel-badge"><?php echo e($status); ?></span></td>
            <td><?php echo e($count); ?></td>
          </tr>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
          <tr><td colspan="2" class="panel-muted">No quote data yet.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </article>
</section>
<?php endif; ?>

<section id="pending-followups" class="panel-card">
  <h2 class="panel-section-title">Pending Follow-ups</h2>
  <div class="panel-sticky-filters">
    <div class="panel-form-row">
      <?php if($widgetVisibility['can_export_data']): ?>
      <form method="get" action="<?php echo e(route('admin.exports.followups')); ?>" class="panel-form-row">
        <input type="hidden" name="status" value="pending">
        <input class="panel-input" type="date" name="from_date" value="<?php echo e($filters['followups_from_date']); ?>">
        <input class="panel-input" type="date" name="to_date" value="<?php echo e($filters['followups_to_date']); ?>">
        <button class="panel-btn" type="submit">Export CSV</button>
      </form>
      <?php else: ?>
      <span class="panel-badge">Manager: export disabled</span>
      <?php endif; ?>
    </div>
  </div>
  <div class="panel-table-wrap">
    <table class="panel-table">
      <thead><tr><th>Due</th><th>Lead</th><th>Method</th><th>Owner</th><th>Status</th><th>Action</th></tr></thead>
      <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $pendingFollowUps; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $followUp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <tr class="<?php echo e($followUp->due_at && $followUp->due_at->isPast() ? 'panel-row-overdue' : ''); ?>">
          <td><?php echo e($followUp->due_at?->format('Y-m-d H:i') ?: '-'); ?></td>
          <td>
            <a class="panel-link" href="<?php echo e(route('admin.leads.show', $followUp->leadProfile)); ?>">
              <?php echo e($followUp->leadProfile?->name ?: ('Lead #' . $followUp->lead_profile_id)); ?>

            </a>
          </td>
          <td><?php echo e(strtoupper($followUp->method)); ?></td>
          <td><?php echo e($followUp->owner?->name ?: '-'); ?></td>
          <td>
            <span class="panel-badge"><?php echo e($followUp->status); ?></span>
            <?php if($followUp->due_at && $followUp->due_at->isPast()): ?>
            <span class="panel-badge panel-badge-danger">Overdue</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if($widgetVisibility['can_manage_pipeline']): ?>
              <form method="post" action="<?php echo e(route('admin.follow-ups.status', $followUp)); ?>">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="status" value="completed">
                <button class="panel-btn" type="submit">Mark completed</button>
              </form>
            <?php else: ?>
              <span class="panel-badge">Read only</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <tr><td colspan="6" class="panel-muted">No pending follow-ups.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

</section>

<style>
.dashboard-v2 {
  --dash-line: #d6e1f0;
  --dash-soft: #f5f9ff;
  --dash-soft-2: #fbfdff;
  --dash-ink: #102845;
  --dash-muted: #607797;
  gap: 16px;
}

.dashboard-v2.dashboard-density-premium {
  gap: 18px;
}

.dashboard-v2.dashboard-density-compact {
  gap: 12px;
}

.dashboard-v2 .dash-density-toggle {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px;
  border: 1px solid #d7e3f2;
  border-radius: 999px;
  background: #ffffff;
  box-shadow: 0 4px 14px rgba(16, 34, 62, .08);
}

.dashboard-v2 .dash-density-label {
  font-size: 11px;
  font-weight: 700;
  letter-spacing: .04em;
  text-transform: uppercase;
  color: var(--dash-muted);
  padding: 0 8px;
}

.dashboard-v2 .dash-density-btn {
  border: 0;
  background: transparent;
  border-radius: 999px;
  padding: 6px 11px;
  font-size: 12px;
  font-weight: 700;
  color: #355074;
  cursor: pointer;
  transition: background-color .15s ease, color .15s ease;
}

.dashboard-v2 .dash-density-btn.is-active {
  background: #eaf2ff;
  color: #102845;
}

.dashboard-v2 .panel-grid {
  gap: 14px;
}

.dashboard-v2.dashboard-density-premium .panel-grid {
  gap: 16px;
}

.dashboard-v2.dashboard-density-compact .panel-grid {
  gap: 10px;
}

.dashboard-v2 .panel-card {
  border-color: var(--dash-line);
  border-radius: 14px;
  box-shadow: 0 12px 26px rgba(16, 34, 62, .06);
  background: linear-gradient(180deg, #ffffff 0%, var(--dash-soft-2) 100%);
}

.dashboard-v2.dashboard-density-premium .panel-card {
  border-radius: 15px;
}

.dashboard-v2.dashboard-density-compact .panel-card {
  border-radius: 11px;
  box-shadow: 0 8px 18px rgba(16, 34, 62, .05);
}

.dashboard-v2 .panel-grid-kpi {
  align-items: stretch;
  grid-template-columns: repeat(auto-fit, minmax(165px, 1fr));
}

.dashboard-v2 .panel-grid-kpi > .panel-card {
  min-height: 112px;
  padding: 11px 13px;
  display: grid;
  align-content: space-between;
  gap: 9px;
  border: 1px solid #d7e3f2;
  background: linear-gradient(180deg, #ffffff 0%, #f6faff 100%);
}

.dashboard-v2.dashboard-density-premium .panel-grid-kpi > .panel-card {
  min-height: 118px;
  padding: 12px 14px;
}

.dashboard-v2.dashboard-density-compact .panel-grid-kpi > .panel-card {
  min-height: 96px;
  padding: 8px 10px;
  gap: 6px;
}

.dashboard-v2 .panel-kpi-label {
  margin: 0;
  font-size: 11.5px;
  font-weight: 700;
  letter-spacing: .015em;
  line-height: 1.25;
  color: var(--dash-muted);
}

.dashboard-v2.dashboard-density-compact .panel-kpi-label {
  font-size: 10.5px;
  line-height: 1.2;
}

.dashboard-v2 .panel-kpi-value {
  margin: 0;
  font-size: 32px;
  line-height: 1.05;
  letter-spacing: -.01em;
  color: var(--dash-ink);
}

.dashboard-v2.dashboard-density-premium .panel-kpi-value {
  font-size: 34px;
}

.dashboard-v2.dashboard-density-compact .panel-kpi-value {
  font-size: 27px;
  line-height: 1;
}

.dashboard-v2 .panel-section-title {
  margin-bottom: 10px;
  font-size: 30px;
  line-height: 1.15;
  letter-spacing: -.015em;
  color: var(--dash-ink);
}

.dashboard-v2.dashboard-density-premium .panel-section-title {
  margin-bottom: 12px;
}

.dashboard-v2.dashboard-density-compact .panel-section-title {
  font-size: 24px;
  line-height: 1.1;
  margin-bottom: 7px;
}

.dashboard-v2 .panel-alert-strip {
  border-style: solid;
  border-color: #d7e3f2;
  background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
}

.dashboard-v2 .panel-form-row {
  margin-bottom: 10px;
}

.dashboard-v2 .panel-sticky-filters {
  margin-bottom: 8px;
}

.dashboard-v2 .panel-table-wrap {
  border: 1px solid #dbe5f2;
  border-radius: 12px;
  background: #fff;
}

.dashboard-v2 .panel-table th,
.dashboard-v2 .panel-table td {
  padding-top: 9px;
  padding-bottom: 9px;
}

.dashboard-v2.dashboard-density-compact .panel-table th,
.dashboard-v2.dashboard-density-compact .panel-table td {
  padding-top: 6px;
  padding-bottom: 6px;
}

.dashboard-v2 .panel-table th {
  font-size: 11px;
  letter-spacing: .055em;
}

.dashboard-v2 .panel-table td {
  font-size: 13px;
  line-height: 1.35;
}

.dashboard-v2.dashboard-density-compact .panel-table td {
  font-size: 12px;
  line-height: 1.25;
}

.dashboard-v2 .panel-chart-grid {
  gap: 12px;
}

.dashboard-v2 .panel-chart-grid > .panel-card {
  min-height: 100%;
}

.dashboard-v2 .panel-line-chart {
  border: 1px solid #dbe5f2;
  border-radius: 12px;
  padding: 10px;
  background: #fff;
}

.dashboard-v2.dashboard-density-premium .panel-line-chart {
  padding: 12px;
}

.dashboard-v2.dashboard-density-compact .panel-line-chart {
  padding: 8px;
}

.dashboard-v2 .panel-chart-row {
  grid-template-columns: 76px 1fr 46px;
  gap: 9px;
}

.dashboard-v2.dashboard-density-compact .panel-chart-row {
  grid-template-columns: 66px 1fr 38px;
  gap: 6px;
}

.dashboard-v2 .panel-chart-label {
  font-size: 11px;
  line-height: 1.2;
}

.dashboard-v2.dashboard-density-compact .panel-chart-label {
  font-size: 10px;
}

.dashboard-v2 .panel-chart-value {
  font-weight: 700;
}

@media (max-width: 1200px) {
  .dashboard-v2 .panel-chart-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 860px) {
  .dashboard-v2 .panel-chart-grid {
    grid-template-columns: 1fr;
  }

  .dashboard-v2 .panel-grid-kpi {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .dashboard-v2 .panel-grid-kpi > .panel-card {
    min-height: 98px;
  }

  .dashboard-v2 .panel-kpi-value {
    font-size: 27px;
  }
}

@media (max-width: 700px) {
  .dashboard-v2 .dash-density-toggle {
    width: 100%;
    justify-content: space-between;
  }

  .dashboard-v2 .dash-density-label {
    padding-left: 6px;
  }
}

@media (max-width: 560px) {
  .dashboard-v2 .panel-grid-kpi {
    grid-template-columns: 1fr;
  }
}
</style>

<script>
(() => {
  const root = document.querySelector('.dashboard-v2');
  if (!root) {
    return;
  }

  const buttons = root.querySelectorAll('.dash-density-btn[data-density]');
  if (!buttons.length) {
    return;
  }

  const applyDensity = (mode) => {
    const resolved = mode === 'compact' ? 'compact' : 'premium';
    root.classList.remove('dashboard-density-compact', 'dashboard-density-premium');
    root.classList.add(`dashboard-density-${resolved}`);

    buttons.forEach((btn) => {
      const active = btn.dataset.density === resolved;
      btn.classList.toggle('is-active', active);
      btn.setAttribute('aria-pressed', active ? 'true' : 'false');
    });
  };

  applyDensity('compact');

  buttons.forEach((btn) => {
    btn.addEventListener('click', () => {
      const next = btn.dataset.density === 'compact' ? 'compact' : 'premium';
      applyDensity(next);
    });
  });
})();
</script>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.panel', [
  'title' => 'Admin CRM Dashboard',
  'heading' => 'Admin CRM Dashboard',
  'subheading' => 'Lead, quote, follow-up, and submission operations in one panel.',
], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/asifk/projects/maccento/resources/views/admin/dashboard.blade.php ENDPATH**/ ?>