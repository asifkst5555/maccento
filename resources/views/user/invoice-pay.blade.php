@extends('layouts.panel', [
  'title' => 'Pay Invoice',
  'heading' => 'Pay Invoice',
  'subheading' => 'Complete your payment securely.',
])

@section('content')
<div class="corp-admin-shell panel-stack">
  <section class="panel-card panel-stack">
    <div class="panel-form-row" style="justify-content: space-between; align-items: center;">
      <div>
        <span class="panel-badge">Invoice {{ $invoice->invoice_number }}</span>
        <h2 class="panel-section-title" style="margin-top: 12px;">Payment for {{ $invoice->project?->title ?: 'Invoice' }}</h2>
        <p class="panel-muted">Amount due: <strong>{{ number_format((float) ($invoice->balance_due ?? $invoice->amount), 2) }} {{ strtoupper((string) $invoice->currency) }}</strong></p>
      </div>
      <a class="panel-btn" href="{{ route('user.invoices.index') }}">Back to invoices</a>
    </div>
  </section>

  @if($demoMode)
    <section class="panel-card">
      <span class="panel-badge panel-badge-warning">Demo mode</span>
      <p class="panel-muted" style="margin-top: 10px;">Demo mode is enabled. Payments are recorded without contacting Stripe or PayPal.</p>
    </section>
  @endif

  <section class="panel-card panel-stack">
    <h3 class="panel-section-title" style="margin-top: 0;">Pay online</h3>
    <p class="panel-muted" style="margin-top: 0;">You can pay the full balance or enter a partial payment amount below.</p>
    <div class="panel-form-row" style="flex-wrap: wrap; gap: 1rem;">
      @if($stripeEnabled)
        <form method="post" action="{{ route('user.invoices.stripe.checkout', $invoice) }}">
          @csrf
          <input class="panel-input" style="max-width: 160px; margin-right: 8px;" type="number" name="amount" min="0.01" step="0.01" value="{{ number_format((float) ($invoice->balance_due ?? $invoice->amount), 2, '.', '') }}" required>
          <button class="panel-btn panel-btn-primary" type="submit">Pay with Card (Stripe)</button>
        </form>
      @endif
      @if($paypalEnabled)
        <form method="post" action="{{ route('user.invoices.paypal.create', $invoice) }}">
          @csrf
          <input class="panel-input" style="max-width: 160px; margin-right: 8px;" type="number" name="amount" min="0.01" step="0.01" value="{{ number_format((float) ($invoice->balance_due ?? $invoice->amount), 2, '.', '') }}" required>
          <button class="panel-btn panel-btn-primary" type="submit">Pay with PayPal</button>
        </form>
      @endif
      @if(!$stripeEnabled && !$paypalEnabled)
        <p class="panel-muted">Online payments are currently disabled.</p>
      @endif
    </div>
  </section>

  @if($manualEnabled)
  <section class="panel-card panel-stack">
    <h3 class="panel-section-title" style="margin-top: 0;">Manual payment</h3>
    <p class="panel-muted">{{ $manualInstructions ?: 'Contact the admin to arrange manual payment.' }}</p>
    <form method="post" action="{{ route('user.invoices.manual.notify', $invoice) }}">
      @csrf
      <input class="panel-input" style="max-width: 160px; margin-right: 8px;" type="number" name="amount" min="0.01" step="0.01" value="{{ number_format((float) ($invoice->balance_due ?? $invoice->amount), 2, '.', '') }}">
      <button class="panel-btn" type="submit">Notify admin of manual payment</button>
    </form>
  </section>
  @endif
</div>
@endsection
