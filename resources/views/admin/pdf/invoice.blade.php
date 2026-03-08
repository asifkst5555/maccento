<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Invoice {{ $invoice->invoice_number }}</title>
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #12243f; }
    .page { padding: 4px 10px; }
    .header { border-bottom: 2px solid #cc1f2f; padding-bottom: 10px; margin-bottom: 16px; }
    .header-top { width: 100%; border-collapse: collapse; }
    .header-top td { vertical-align: top; }
    .logo-wrap { text-align: right; }
    .logo { max-width: 145px; max-height: 58px; }
    .brand { font-size: 22px; font-weight: 700; color: #0f2748; margin: 0; }
    .brand-sub { margin: 4px 0 0; color: #4a617f; line-height: 1.45; }
    .title-row { width: 100%; margin-top: 12px; border-collapse: collapse; }
    .title-row td { vertical-align: top; }
    .title { font-size: 19px; font-weight: 700; color: #0f2748; margin: 0; }
    .muted { color: #6a7f9f; }
    .section { margin-top: 12px; }
    .grid { width: 100%; border-collapse: collapse; margin-top: 8px; }
    .grid th, .grid td { border: 1px solid #d9e2ef; padding: 8px; text-align: left; }
    .grid th { background: #f4f8fe; color: #27456a; width: 28%; }
    .summary { width: 46%; margin-left: auto; border-collapse: collapse; margin-top: 14px; }
    .summary td { border: 1px solid #d9e2ef; padding: 8px; }
    .summary .label { background: #f4f8fe; color: #27456a; width: 55%; }
    .summary .value { text-align: right; font-weight: 600; }
    .summary .total .label,
    .summary .total .value { font-weight: 700; font-size: 13px; background: #eef4fc; }
    .notes { margin-top: 14px; white-space: pre-wrap; color: #1f3558; }
    .footer { border-top: 1px solid #d9e2ef; margin-top: 24px; padding-top: 10px; color: #6a7f9f; font-size: 11px; }
  </style>
</head>
<body>
  <div class="page">
    <div class="header">
      <table class="header-top">
        <tr>
          <td>
            <p class="brand">{{ $brandName }}</p>
            <p class="brand-sub">{{ $brandEmail }} | {{ $brandPhone }}</p>
          </td>
          <td class="logo-wrap">
            @if(!empty($logoAbsolutePath))
              <img class="logo" src="{{ $logoAbsolutePath }}" alt="{{ $brandName }} logo">
            @endif
          </td>
        </tr>
      </table>

      <table class="title-row">
        <tr>
          <td>
            <p class="title">INVOICE {{ $invoice->invoice_number }}</p>
            <p class="muted">Issued: {{ $invoice->issued_at?->format('Y-m-d') ?: '-' }}</p>
            <p class="muted">Due: {{ $invoice->due_date?->format('Y-m-d') ?: '-' }}</p>
            <p class="muted">Status: {{ strtoupper((string) $invoice->status) }}</p>
          </td>
          <td style="text-align:right;">
            <p class="muted">Generated: {{ now()->format('Y-m-d H:i:s') }}</p>
            <p class="muted">Currency: {{ strtoupper((string) $invoice->currency) }}</p>
          </td>
        </tr>
      </table>
    </div>

    <div class="section">
      <table class="grid">
        <tr>
          <th>Bill To</th>
          <td>
            {{ $client?->name ?: 'Client' }}<br>
            {{ $client?->company ?: '' }}@if(!blank($client?->company))<br>@endif
            {{ $client?->email ?: '-' }}<br>
            {{ $client?->phone ?: '-' }}
          </td>
        </tr>
        <tr>
          <th>Project</th>
          <td>
            {{ $project?->title ?: '-' }}<br>
            {{ $project?->service_type ?: '' }}{{ $project?->property_address ? ' - ' . $project->property_address : '' }}
          </td>
        </tr>
      </table>
    </div>

    <table class="summary">
      <tr>
        <td class="label">Subtotal</td>
        <td class="value">{{ number_format((float) $subtotal, 2) }} {{ strtoupper((string) $invoice->currency) }}</td>
      </tr>
      @if($includeTax)
      <tr>
        <td class="label">Tax ({{ number_format((float) $taxRate, 2) }}%)</td>
        <td class="value">{{ number_format((float) $taxAmount, 2) }} {{ strtoupper((string) $invoice->currency) }}</td>
      </tr>
      @endif
      <tr class="total">
        <td class="label">Total</td>
        <td class="value">{{ number_format((float) $total, 2) }} {{ strtoupper((string) $invoice->currency) }}</td>
      </tr>
    </table>

    @if(!blank($invoice->notes))
      <div class="notes"><strong>Notes:</strong><br>{{ $invoice->notes }}</div>
    @endif

    <div class="footer">
      This invoice was generated from the Maccento Admin Portal.
    </div>
  </div>
</body>
</html>
