<form method="post" action="{{ route('admin.api-integrations.update') }}" class="panel-card panel-stack">
  @csrf
  <div class="panel-stack" style="gap: 1.5rem;">
    <div class="panel-stack">
      <h3 class="panel-section-title" style="margin: 0;">Stripe</h3>
      <div class="panel-form-row">
        <input class="panel-input" type="text" name="stripe_publishable_key" value="{{ $settings->stripe_publishable_key }}" placeholder="Stripe publishable key">
        <input class="panel-input" type="text" name="stripe_secret_key" value="{{ $settings->stripe_secret_key }}" placeholder="Stripe secret key">
      </div>
    </div>

    <div class="panel-stack">
      <h3 class="panel-section-title" style="margin: 0;">PayPal</h3>
      <div class="panel-form-row">
        <input class="panel-input" type="text" name="paypal_client_id" value="{{ $settings->paypal_client_id }}" placeholder="PayPal client ID">
        <input class="panel-input" type="text" name="paypal_secret" value="{{ $settings->paypal_secret }}" placeholder="PayPal secret">
      </div>
      <label class="panel-inline-check" style="margin-top: 0.5rem;">
        <input type="hidden" name="paypal_sandbox" value="0">
        <input type="checkbox" name="paypal_sandbox" value="1" @checked((bool) $settings->paypal_sandbox)>
        Use PayPal sandbox
      </label>
    </div>

    <div class="panel-stack">
      <h3 class="panel-section-title" style="margin: 0;">Email (SMTP)</h3>
      <div class="panel-form-row">
        <input class="panel-input" type="text" name="mail_mailer" value="{{ $settings->mail_mailer }}" placeholder="Mailer (smtp, ses, mailgun)">
        <input class="panel-input" type="text" name="mail_host" value="{{ $settings->mail_host }}" placeholder="SMTP host">
        <input class="panel-input" type="number" name="mail_port" value="{{ $settings->mail_port }}" placeholder="Port">
      </div>
      <div class="panel-form-row">
        <input class="panel-input" type="text" name="mail_username" value="{{ $settings->mail_username }}" placeholder="SMTP username">
        <input class="panel-input" type="password" name="mail_password" value="{{ $settings->mail_password }}" placeholder="SMTP password">
        <input class="panel-input" type="text" name="mail_encryption" value="{{ $settings->mail_encryption }}" placeholder="Encryption (tls/ssl)">
      </div>
      <div class="panel-form-row">
        <input class="panel-input" type="email" name="mail_from_address" value="{{ $settings->mail_from_address }}" placeholder="From address">
        <input class="panel-input" type="text" name="mail_from_name" value="{{ $settings->mail_from_name }}" placeholder="From name">
      </div>
    </div>

    <div class="panel-stack">
      <h3 class="panel-section-title" style="margin: 0;">Storage (S3)</h3>
      <div class="panel-form-row">
        <input class="panel-input" type="text" name="media_disk" value="{{ $settings->media_disk }}" placeholder="Media disk (public or s3)">
        <input class="panel-input" type="text" name="s3_bucket" value="{{ $settings->s3_bucket }}" placeholder="S3 bucket">
        <input class="panel-input" type="text" name="s3_region" value="{{ $settings->s3_region }}" placeholder="S3 region">
      </div>
      <div class="panel-form-row">
        <input class="panel-input" type="text" name="s3_key" value="{{ $settings->s3_key }}" placeholder="S3 access key">
        <input class="panel-input" type="password" name="s3_secret" value="{{ $settings->s3_secret }}" placeholder="S3 secret">
        <input class="panel-input" type="text" name="s3_endpoint" value="{{ $settings->s3_endpoint }}" placeholder="S3 endpoint (optional)">
      </div>
      <label class="panel-inline-check" style="margin-top: 0.5rem;">
        <input type="hidden" name="s3_path_style" value="0">
        <input type="checkbox" name="s3_path_style" value="1" @checked((bool) $settings->s3_path_style)>
        Use path style endpoint
      </label>
    </div>

    <div class="panel-stack">
      <h3 class="panel-section-title" style="margin: 0;">Outbound Webhook</h3>
      <div class="panel-form-row">
        <input class="panel-input" type="url" name="outbound_webhook_url" value="{{ $settings->outbound_webhook_url }}" placeholder="Webhook URL">
        <input class="panel-input" type="text" name="outbound_webhook_secret" value="{{ $settings->outbound_webhook_secret }}" placeholder="Webhook secret (HMAC)">
      </div>
      <label class="panel-inline-check" style="margin-top: 0.5rem;">
        <input type="hidden" name="outbound_webhook_enabled" value="0">
        <input type="checkbox" name="outbound_webhook_enabled" value="1" @checked((bool) $settings->outbound_webhook_enabled)>
        Enable outbound webhook delivery
      </label>
    </div>

    <div class="panel-stack">
      <h3 class="panel-section-title" style="margin: 0;">Chat API</h3>
      <div class="panel-form-row">
        <input class="panel-input" type="text" name="chat_provider" value="{{ $settings->chat_provider }}" placeholder="Provider (Twilio, Intercom, etc.)">
        <input class="panel-input" type="text" name="chat_api_key" value="{{ $settings->chat_api_key }}" placeholder="Chat API key">
        <input class="panel-input" type="url" name="chat_webhook_url" value="{{ $settings->chat_webhook_url }}" placeholder="Webhook URL">
      </div>
    </div>

    <div class="panel-stack">
      <h3 class="panel-section-title" style="margin: 0;">AI Providers</h3>
      <div class="panel-form-row">
        <input class="panel-input" type="text" name="ai_provider" value="{{ $settings->ai_provider }}" placeholder="Active provider (openrouter, openai, gemini)">
        <input class="panel-input" type="text" name="ai_model" value="{{ $settings->ai_model }}" placeholder="Default model (AI_MODEL)">
      </div>
      <div class="panel-form-row">
        <input class="panel-input" type="text" name="openrouter_api_key" value="{{ $settings->openrouter_api_key }}" placeholder="OpenRouter API key">
        <input class="panel-input" type="text" name="openrouter_base_url" value="{{ $settings->openrouter_base_url }}" placeholder="OpenRouter base URL">
        <input class="panel-input" type="text" name="openrouter_model" value="{{ $settings->openrouter_model }}" placeholder="OpenRouter model">
      </div>
      <div class="panel-form-row">
        <input class="panel-input" type="text" name="openai_api_key" value="{{ $settings->openai_api_key }}" placeholder="OpenAI (ChatGPT) API key">
        <input class="panel-input" type="text" name="openai_base_url" value="{{ $settings->openai_base_url }}" placeholder="OpenAI base URL">
      </div>
      <div class="panel-form-row">
        <input class="panel-input" type="text" name="gemini_api_key" value="{{ $settings->gemini_api_key }}" placeholder="Gemini API key">
        <input class="panel-input" type="text" name="gemini_base_url" value="{{ $settings->gemini_base_url }}" placeholder="Gemini base URL">
        <input class="panel-input" type="text" name="gemini_model" value="{{ $settings->gemini_model }}" placeholder="Gemini model">
      </div>
    </div>

    <div class="panel-form-row" style="justify-content: flex-end;">
      <button class="panel-btn panel-btn-primary" type="submit">{{ $submitLabel ?? 'Save API Settings' }}</button>
    </div>
  </div>
</form>
