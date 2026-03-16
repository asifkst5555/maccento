@extends('layouts.panel', [
  'title' => 'Account',
  'heading' => 'Account',
  'subheading' => 'Review your client profile, primary contact details, and portal access information.',
])

@section('content')
<div class="client-portal-shell">
  <section class="panel-card client-portal-hero">
    <div class="client-portal-hero-head">
      <div>
        <span class="client-portal-eyebrow">Client Account</span>
        <h2 class="panel-section-title" style="margin-top: 12px;">{{ $client?->name ?: auth()->user()->name }}</h2>
        <p class="client-portal-summary">Your account details below are used for project, invoice, and communication matching inside the CRM.</p>
      </div>
      <div class="client-portal-actions">
        <a class="panel-btn" href="{{ route('user.messages.index') }}">Contact Team</a>
        <a class="panel-btn panel-btn-primary" href="{{ route('user.invoices.index') }}">Open Invoices</a>
      </div>
    </div>
  </section>

  <section class="client-portal-account-grid">
    <article class="panel-card client-portal-stack">
      <h2 class="panel-section-title">Portal Identity</h2>
      <div class="client-portal-detail-grid">
        <div class="client-portal-detail">
          <span class="client-portal-detail-label">Name</span>
          <p class="client-portal-detail-value">{{ auth()->user()->name }}</p>
        </div>
        <div class="client-portal-detail">
          <span class="client-portal-detail-label">Login Email</span>
          <p class="client-portal-detail-value">{{ auth()->user()->email }}</p>
        </div>
        <div class="client-portal-detail">
          <span class="client-portal-detail-label">Role</span>
          <p class="client-portal-detail-value">{{ auth()->user()->role }}</p>
        </div>
      </div>
    </article>

    <article class="panel-card client-portal-stack">
      <h2 class="panel-section-title">Client Record</h2>
      <div class="client-portal-detail-grid">
        <div class="client-portal-detail">
          <span class="client-portal-detail-label">Client Name</span>
          <p class="client-portal-detail-value">{{ $client?->name ?: 'Not linked yet' }}</p>
        </div>
        <div class="client-portal-detail">
          <span class="client-portal-detail-label">Primary Email</span>
          <p class="client-portal-detail-value">{{ $client?->email ?: auth()->user()->email }}</p>
        </div>
        <div class="client-portal-detail">
          <span class="client-portal-detail-label">Phone</span>
          <p class="client-portal-detail-value">{{ $client?->phone ?: (auth()->user()->phone ?: '-') }}</p>
        </div>
        <div class="client-portal-detail">
          <span class="client-portal-detail-label">Company</span>
          <p class="client-portal-detail-value">{{ $client?->company ?: '-' }}</p>
        </div>
        <div class="client-portal-detail">
          <span class="client-portal-detail-label">Status</span>
          <p class="client-portal-detail-value">{{ $client?->status ?: 'active' }}</p>
        </div>
        <div class="client-portal-detail">
          <span class="client-portal-detail-label">Client Record ID</span>
          <p class="client-portal-detail-value">{{ $client?->id ? '#' . $client->id : '-' }}</p>
        </div>
      </div>
    </article>
  </section>

  <section class="panel-card client-portal-stack">
    <h2 class="panel-section-title">Portal Snapshot</h2>
    <div class="panel-grid panel-grid-kpi-compact">
      <article class="client-portal-kpi">
        <span class="panel-kpi-label">Active Projects</span>
        <p class="client-portal-kpi-value">{{ $portalStats['active_projects'] }}</p>
      </article>
      <article class="client-portal-kpi">
        <span class="panel-kpi-label">Unpaid Invoices</span>
        <p class="client-portal-kpi-value">{{ $portalStats['unpaid_invoices'] }}</p>
      </article>
      <article class="client-portal-kpi">
        <span class="panel-kpi-label">Pending Quotes</span>
        <p class="client-portal-kpi-value">{{ $portalStats['pending_quotes'] }}</p>
      </article>
    </div>
  </section>

  <section class="panel-card client-portal-stack">
    <h2 class="panel-section-title">Notification Preferences</h2>
    <form method="post" action="{{ route('user.account.update') }}" class="panel-stack">
      @csrf
      <label class="panel-inline-check">
        <input type="hidden" name="notify_portal" value="0">
        <input type="checkbox" name="notify_portal" value="1" @checked((bool) ($client?->notify_portal ?? true))>
        Receive portal notifications
      </label>
      <label class="panel-inline-check">
        <input type="hidden" name="notify_invoice_email" value="0">
        <input type="checkbox" name="notify_invoice_email" value="1" @checked((bool) ($client?->notify_invoice_email ?? true))>
        Receive invoice emails and reminders
      </label>
      <button class="panel-btn panel-btn-primary" type="submit">Save Preferences</button>
    </form>
  </section>
</div>
@endsection
