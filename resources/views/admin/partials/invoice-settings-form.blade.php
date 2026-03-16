@php
  $canManageInvoiceSettings = in_array(strtolower(trim((string) auth()->user()?->role)), ['owner', 'admin'], true);
@endphp
@if($canManageInvoiceSettings)
  <section class="panel-card" style="margin-top: 16px;">
    <div class="panel-form-row row-between-center">
      <strong>Invoice Payment & Tax Settings</strong>
      <span class="panel-muted">Admin-controlled</span>
    </div>
    <form method="post" action="{{ route('admin.invoices.settings.update') }}" class="panel-form-row tax-settings-form">
    @csrf
    <div class="panel-stack" style="gap: 0.75rem; width: 100%;">
      <label class="panel-muted inline-checkbox-label">
        <input type="hidden" name="include_tax_on_pdf" value="0">
        <input type="checkbox" name="include_tax_on_pdf" value="1" @checked((bool) ($invoiceSettings->include_tax_on_pdf ?? false))>
        Include tax on admin invoice PDF
      </label>
      <label class="panel-muted stacked-label">
        Tax %
        <input class="panel-input tax-input" type="number" step="0.01" min="0" max="100" name="tax_rate_percent" value="{{ number_format((float) ($invoiceSettings->tax_rate_percent ?? 0), 2, '.', '') }}">
      </label>
      <div class="panel-form-row" style="gap: 1rem; align-items: center; flex-wrap: wrap;">
        <label class="panel-inline-check">
          <input type="hidden" name="stripe_enabled" value="0">
          <input type="checkbox" name="stripe_enabled" value="1" @checked((bool) ($invoiceSettings->stripe_enabled ?? false))>
          Enable Stripe (card payments)
        </label>
        <label class="panel-inline-check">
          <input type="hidden" name="paypal_enabled" value="0">
          <input type="checkbox" name="paypal_enabled" value="1" @checked((bool) ($invoiceSettings->paypal_enabled ?? false))>
          Enable PayPal
        </label>
        <label class="panel-inline-check">
          <input type="hidden" name="manual_enabled" value="0">
          <input type="checkbox" name="manual_enabled" value="1" @checked((bool) ($invoiceSettings->manual_enabled ?? true))>
          Enable manual/offline payments
        </label>
      </div>
      <label class="panel-muted stacked-label">
        Manual payment instructions
        <textarea class="panel-textarea" name="manual_instructions" placeholder="Manual payment instructions (bank transfer, cash, etc.)">{{ $invoiceSettings->manual_instructions }}</textarea>
      </label>
      <div class="panel-form-row row-between-center" style="margin-top: 10px;">
        <strong>Invoice Reminder Settings</strong>
        <span class="panel-muted">Client email automation</span>
      </div>
      <label class="panel-muted inline-checkbox-label">
        <input type="hidden" name="auto_email_on_invoice_create" value="0">
        <input type="checkbox" name="auto_email_on_invoice_create" value="1" @checked((bool) ($invoiceSettings->auto_email_on_invoice_create ?? true))>
        Send invoice email when status is set to Sent/Partial/Overdue
      </label>
      <label class="panel-muted inline-checkbox-label">
        <input type="hidden" name="reminder_enabled" value="0">
        <input type="checkbox" name="reminder_enabled" value="1" @checked((bool) ($invoiceSettings->reminder_enabled ?? true))>
        Enable due date reminders
      </label>
      <label class="panel-muted stacked-label">
        Reminder lead time (days before due date)
        <input class="panel-input tax-input" type="number" min="1" max="30" name="reminder_days_before" value="{{ (int) ($invoiceSettings->reminder_days_before ?? 3) }}">
      </label>
      <label class="panel-muted inline-checkbox-label">
        <input type="hidden" name="reminder_send_on_due_date" value="0">
        <input type="checkbox" name="reminder_send_on_due_date" value="1" @checked((bool) ($invoiceSettings->reminder_send_on_due_date ?? true))>
        Send reminder on due date
      </label>
      <label class="panel-muted inline-checkbox-label">
        <input type="hidden" name="overdue_reminder_enabled" value="0">
        <input type="checkbox" name="overdue_reminder_enabled" value="1" @checked((bool) ($invoiceSettings->overdue_reminder_enabled ?? true))>
        Enable overdue reminders
      </label>
      <label class="panel-muted stacked-label">
        Overdue reminder interval (days)
        <input class="panel-input tax-input" type="number" min="1" max="30" name="overdue_reminder_every_days" value="{{ (int) ($invoiceSettings->overdue_reminder_every_days ?? 3) }}">
      </label>
      <div class="panel-form-row" style="justify-content: flex-end;">
        <button class="panel-btn panel-btn-primary" type="submit">Save Settings</button>
      </div>
    </div>
    </form>
  </section>
@endif
