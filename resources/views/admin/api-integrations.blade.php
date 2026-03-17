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

  @php
    $maskValue = function (?string $value): string {
        $value = (string) $value;
        if ($value === '') {
            return '-';
        }
        $visible = min(4, strlen($value));
        return str_repeat('•', max(0, strlen($value) - $visible)) . substr($value, -$visible);
    };
    $sourceLabel = function (?string $value): string {
        return trim((string) $value) !== '' ? 'DB' : '.env';
    };
    $boolSourceLabel = function ($value): string {
        return $value !== null ? 'DB' : '.env';
    };
  @endphp

  <section class="panel-card panel-stack">
    <h3 class="panel-section-title" style="margin: 0;">Active Mail Settings</h3>
    <p class="panel-muted">These are the values the CRM is using right now.</p>
    <div class="panel-table-wrap">
      <table class="panel-table">
        <thead>
          <tr>
            <th>Setting</th>
            <th>Effective</th>
            <th>Source</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>SMTP Host</td>
            <td>{{ $settings->mail_host ?: '-' }}</td>
            <td>{{ $sourceLabel($rawSettings->mail_host ?? null) }}</td>
          </tr>
          <tr>
            <td>SMTP Port</td>
            <td>{{ $settings->mail_port ?: '-' }}</td>
            <td>{{ $rawSettings->mail_port ? 'DB' : '.env' }}</td>
          </tr>
          <tr>
            <td>SMTP Username</td>
            <td>{{ $settings->mail_username ?: '-' }}</td>
            <td>{{ $sourceLabel($rawSettings->mail_username ?? null) }}</td>
          </tr>
          <tr>
            <td>SMTP Password</td>
            <td>{{ $maskValue($settings->mail_password) }}</td>
            <td>{{ $sourceLabel($rawSettings->mail_password ?? null) }}</td>
          </tr>
          <tr>
            <td>SMTP Encryption</td>
            <td>{{ $settings->mail_encryption ?: '-' }}</td>
            <td>{{ $sourceLabel($rawSettings->mail_encryption ?? null) }}</td>
          </tr>
          <tr>
            <td>From Address</td>
            <td>{{ $settings->mail_from_address ?: '-' }}</td>
            <td>{{ $sourceLabel($rawSettings->mail_from_address ?? null) }}</td>
          </tr>
          <tr>
            <td>Inbound Enabled</td>
            <td>{{ $settings->inbound_mail_enabled ? 'Yes' : 'No' }}</td>
            <td>{{ $boolSourceLabel($rawSettings->inbound_mail_enabled ?? null) }}</td>
          </tr>
          <tr>
            <td>Inbound Provider</td>
            <td>{{ $settings->inbound_mail_provider ?: '-' }}</td>
            <td>{{ $sourceLabel($rawSettings->inbound_mail_provider ?? null) }}</td>
          </tr>
          <tr>
            <td>Inbound Host</td>
            <td>{{ $settings->inbound_mail_host ?: '-' }}</td>
            <td>{{ $sourceLabel($rawSettings->inbound_mail_host ?? null) }}</td>
          </tr>
          <tr>
            <td>Inbound Port</td>
            <td>{{ $settings->inbound_mail_port ?: '-' }}</td>
            <td>{{ $rawSettings->inbound_mail_port ? 'DB' : '.env' }}</td>
          </tr>
          <tr>
            <td>Inbound Username</td>
            <td>{{ $settings->inbound_mail_username ?: '-' }}</td>
            <td>{{ $sourceLabel($rawSettings->inbound_mail_username ?? null) }}</td>
          </tr>
          <tr>
            <td>Inbound Password</td>
            <td>{{ $maskValue($settings->inbound_mail_password) }}</td>
            <td>{{ $sourceLabel($rawSettings->inbound_mail_password ?? null) }}</td>
          </tr>
        </tbody>
      </table>
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
