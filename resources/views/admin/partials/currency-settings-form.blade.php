@php
  $enabledCurrencies = array_map('strtoupper', (array) ($currencySettings->enabled_currencies ?? []));
  $defaultCurrency = strtoupper((string) ($currencySettings->default_currency ?? 'USD'));
@endphp

<section class="panel-card" id="currency-settings">
  <div class="panel-form-row row-between-center" style="margin-bottom: 12px;">
    <strong>Currency Settings</strong>
    <span class="panel-muted">Defaults for quotes and invoices</span>
  </div>
  <form method="post" action="{{ route('admin.settings.currency.update') }}" class="panel-stack">
    @csrf
    <div class="panel-grid" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 14px; align-items: start;">
      <section class="panel-card" style="margin: 0;">
        <h3 class="panel-section-title">Default Currency</h3>
        <select class="panel-input" name="default_currency" data-select-flags="currency" required>
          @foreach($currencyOptions as $code => $label)
            <option value="{{ $code }}" @selected($defaultCurrency === $code)>{{ $code }} - {{ $label }}</option>
          @endforeach
        </select>
        <p class="panel-muted" style="margin-top: 8px;">Used when creating new invoices, quotes, and projects.</p>
      </section>

      <section class="panel-card" style="margin: 0;">
        <h3 class="panel-section-title">Enabled Currencies</h3>
        <div class="panel-stack" style="gap: 8px;">
          @foreach($currencyOptions as $code => $label)
            <label class="panel-inline-check">
              <input type="checkbox" name="enabled_currencies[]" value="{{ $code }}" @checked(in_array($code, $enabledCurrencies, true))>
              <span class="currency-flag flag-{{ strtolower($code) }}"></span>
              {{ $code }} - {{ $label }}
            </label>
          @endforeach
        </div>
        <p class="panel-muted" style="margin-top: 8px;">At least the default currency is always enabled.</p>
      </section>
    </div>

    <div class="panel-form-row" style="justify-content: flex-end; margin-top: 12px;">
      <button class="panel-btn panel-btn-primary" type="submit">Save Currency Settings</button>
    </div>
  </form>
</section>
