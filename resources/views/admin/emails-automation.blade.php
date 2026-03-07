@extends('layouts.panel', [
  'title' => 'Email Automation',
  'heading' => 'Email Automation',
  'subheading' => 'Configure source-specific AI welcome email behavior for captured leads.',
])

@section('content')
@php
  $sourceCount = count($sourceSettings);
  $enabledCount = collect($sourceSettings)->filter(static fn ($item): bool => (bool) ($item['enabled'] ?? false))->count();
  $disabledCount = max(0, $sourceCount - $enabledCount);
@endphp

<section class="automation-page panel-stack">
  <article class="panel-card automation-hero">
    <div class="automation-hero__copy">
      <p class="automation-eyebrow">Lead Automation Console</p>
      <h2 class="automation-title">Design, control, and audit every welcome email flow</h2>
      <p class="automation-sub">Each source has its own tone, subject strategy, and AI instruction. Leads are persisted only when a valid email exists.</p>
    </div>
    <div class="automation-hero__stats" aria-label="Automation summary">
      <article class="automation-stat">
        <span class="automation-stat__label">Sources</span>
        <strong class="automation-stat__value">{{ $sourceCount }}</strong>
      </article>
      <article class="automation-stat">
        <span class="automation-stat__label">Enabled</span>
        <strong class="automation-stat__value">{{ $enabledCount }}</strong>
      </article>
      <article class="automation-stat">
        <span class="automation-stat__label">Disabled</span>
        <strong class="automation-stat__value">{{ $disabledCount }}</strong>
      </article>
    </div>
  </article>

  <div class="automation-layout">
    <form method="post" action="{{ route('admin.emails.automation.update') }}" class="automation-main panel-stack">
      @csrf

      @foreach($sourceSettings as $setting)
      <section class="panel-card automation-source-card">
        <div class="automation-source-card__head">
          <div>
            <h3 class="automation-source-card__title">{{ $setting['label'] }}</h3>
            <p class="automation-source-card__desc">{{ $setting['description'] }}</p>
          </div>

          <div class="automation-source-card__controls">
            <span class="panel-badge">{{ str_replace('_', ' ', (string) $setting['source']) }}</span>
            <label class="automation-toggle" for="enabled_{{ $setting['source'] }}">
              <input type="hidden" name="enabled[{{ $setting['source'] }}]" value="0">
              <input
                id="enabled_{{ $setting['source'] }}"
                type="checkbox"
                name="enabled[{{ $setting['source'] }}]"
                value="1"
                @if((bool) ($setting['enabled'] ?? false)) checked @endif
              >
              <span class="automation-toggle__track" aria-hidden="true"></span>
              <span class="automation-toggle__text">Automation enabled</span>
            </label>
          </div>
        </div>

        <div class="automation-field-grid">
          <label class="panel-field">
            <span class="automation-label">Tone Profile</span>
            <input
              class="panel-input"
              type="text"
              name="tone[{{ $setting['source'] }}]"
              maxlength="40"
              value="{{ old('tone.' . $setting['source'], $setting['tone']) }}"
              placeholder="professional, friendly, consultative"
              list="automation-tone-presets"
            >
          </label>

          <label class="panel-field">
            <span class="automation-label">Subject Prefix</span>
            <input
              class="panel-input"
              type="text"
              name="subject_prefix[{{ $setting['source'] }}]"
              maxlength="120"
              value="{{ old('subject_prefix.' . $setting['source'], $setting['subject_prefix']) }}"
              placeholder="Maccento Team:"
            >
          </label>
        </div>

        <label class="panel-field">
          <span class="automation-label">AI Template Instruction</span>
          <textarea
            class="panel-textarea"
            name="template_prompt[{{ $setting['source'] }}]"
            rows="5"
            maxlength="5000"
            placeholder="Guide the AI writing style and structure for this source."
          >{{ old('template_prompt.' . $setting['source'], $setting['template_prompt']) }}</textarea>
        </label>
      </section>
      @endforeach

      <div class="automation-main__actions">
        <button class="panel-btn panel-btn-primary" type="submit">Save Automation Settings</button>
      </div>
    </form>

    <aside class="automation-side panel-stack">
      <section class="panel-card automation-ops-card">
        <p class="automation-eyebrow">Operations</p>
        <h3 class="automation-ops-card__title">One-time Historical Backfill</h3>
        <p class="automation-ops-card__desc">Process historical leads with email that missed the welcome workflow. Start with dry run to inspect impact before live send.</p>

        <div class="automation-ops-card__actions">
          <form method="post" action="{{ route('admin.emails.automation.backfill') }}">
            @csrf
            <input type="hidden" name="mode" value="dry-run">
            <button class="panel-btn" type="submit">Run Dry Run</button>
          </form>
          <form method="post" action="{{ route('admin.emails.automation.backfill') }}" onsubmit="return confirm('Run live backfill now? This can send welcome emails to eligible historical leads.');">
            @csrf
            <input type="hidden" name="mode" value="live">
            <button class="panel-btn panel-btn-danger" type="submit">Run Live</button>
          </form>
        </div>

        @if($errors->has('automation_backfill'))
        <p class="automation-feedback is-error">{{ $errors->first('automation_backfill') }}</p>
        @endif

        @if(session('automation_backfill_output'))
        <div class="automation-output">
          <p class="automation-output__label">Last output ({{ session('automation_backfill_mode', 'dry-run') }}):</p>
          <pre>{{ session('automation_backfill_output') }}</pre>
        </div>
        @endif
      </section>
    </aside>
  </div>

  <datalist id="automation-tone-presets">
    <option value="professional"></option>
    <option value="friendly"></option>
    <option value="consultative"></option>
    <option value="confident"></option>
    <option value="concise"></option>
  </datalist>
</section>
@endsection
