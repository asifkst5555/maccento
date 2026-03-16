@extends('layouts.panel', [
  'title' => 'Settings',
  'heading' => 'Settings',
  'subheading' => 'Manage integrations, watermark rules, invoice configuration, and currency options.',
])

@section('content')
<div class="corp-admin-shell panel-stack">
  @if(session('status'))
  <section class="panel-card panel-stack" style="border-left: 4px solid #2d9a62;">
    <span class="panel-badge">Settings Saved</span>
    <p class="panel-muted" style="margin: 0;">{{ session('status') }}</p>
  </section>
  @endif
  @if($errors->has('currency'))
  <section class="panel-card panel-stack" style="border-left: 4px solid #c11f37;">
    <span class="panel-badge panel-badge-danger">Settings Error</span>
    <p class="panel-muted" style="margin: 0;">{{ $errors->first('currency') }}</p>
  </section>
  @endif
  <section class="panel-card panel-stack">
    <div class="panel-form-row" style="justify-content: space-between; align-items: center;">
      <div>
        <span class="panel-badge">Admin Controls</span>
        <h2 class="panel-section-title" style="margin-top: 12px;">System Settings</h2>
        <p class="panel-muted">Configure API integrations, watermarking, invoice rules, and currency defaults.</p>
      </div>
    </div>
  </section>

  <section class="panel-card panel-stack" id="api-integrations">
    <div class="panel-form-row" style="justify-content: space-between; align-items: center;">
      <div>
        <span class="panel-badge">Integrations</span>
        <h3 class="panel-section-title" style="margin-top: 12px;">API Integrations</h3>
        <p class="panel-muted">Saved values override <code>.env</code> settings automatically.</p>
      </div>
      <a class="panel-link" href="{{ route('admin.api-integrations.index') }}">Open full API settings</a>
    </div>
  </section>

  @include('admin.partials.api-integrations-form', [
    'settings' => $apiSettings,
    'submitLabel' => 'Save API Settings',
  ])

  <section class="panel-card panel-stack" id="watermark-settings">
    <div class="panel-form-row" style="justify-content: space-between; align-items: center;">
      <div>
        <span class="panel-badge">Media Delivery</span>
        <h3 class="panel-section-title" style="margin-top: 12px;">Watermark Settings</h3>
        <p class="panel-muted">Configure watermark logo, placement, and opacity for unpaid previews.</p>
      </div>
      <a class="panel-link" href="{{ route('admin.media-delivery.watermark.index') }}">Open full watermark settings</a>
    </div>
  </section>

  @include('admin.partials.watermark-settings', [
    'settings' => $watermarkSettings,
    'unpaidImageTotal' => $unpaidImageTotal,
    'upToDateWatermarks' => $upToDateWatermarks,
    'pendingRebuild' => $pendingRebuild,
    'logoExists' => $logoExists,
  ])

  <section class="panel-card panel-stack" id="invoice-settings">
    <div class="panel-form-row" style="justify-content: space-between; align-items: center;">
      <div>
        <span class="panel-badge">Billing</span>
        <h3 class="panel-section-title" style="margin-top: 12px;">Invoice Settings</h3>
        <p class="panel-muted">Payment gateways, tax rules, and reminder automation.</p>
      </div>
      <a class="panel-link" href="{{ route('admin.invoices.index') }}">Open invoices</a>
    </div>
  </section>

  @include('admin.partials.invoice-settings-form', ['invoiceSettings' => $invoiceSettings])

  @include('admin.partials.currency-settings-form', [
    'currencySettings' => $currencySettings,
    'currencyOptions' => $currencyOptions,
  ])
</div>
@endsection
