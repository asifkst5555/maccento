@extends('layouts.panel', [
  'title' => 'API Integrations',
  'heading' => 'API Integrations',
  'subheading' => 'Manage payment gateways, email providers, and chat integrations from one place.',
])

@section('content')
<div class="corp-admin-shell panel-stack">
  <section class="panel-card panel-stack">
    <div class="panel-form-row" style="justify-content: space-between; align-items: center;">
      <div>
        <span class="panel-badge">Integrations</span>
        <h2 class="panel-section-title" style="margin-top: 12px;">API Integrations</h2>
        <p class="panel-muted">Saved here values override <code>.env</code> values automatically.</p>
      </div>
    </div>
  </section>

  @include('admin.partials.api-integrations-form', ['settings' => $settings, 'submitLabel' => 'Save API Settings'])

  @if($settings->outbound_webhook_enabled && $settings->outbound_webhook_url)
  <section class="panel-card panel-stack" style="margin-top: 16px;">
    <h3 class="panel-section-title" style="margin: 0;">Webhook Delivery Log</h3>
    <p class="panel-muted">Latest deliveries are stored in the database.</p>
  </section>
  @endif
</div>
@endsection
