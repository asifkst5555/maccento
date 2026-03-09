@extends('layouts.panel', [
  'title' => 'Client Workspace',
  'heading' => 'Client Workspace',
  'subheading' => 'Track projects, invoices, quotes, and deliveries in one client portal.',
])

@section('content')
<div class="client-portal-shell">
  <section class="panel-card client-portal-hero">
    <div class="client-portal-hero-head">
      <div>
        <span class="client-portal-eyebrow">Client CRM Portal</span>
        <h2 class="panel-section-title" style="margin-top: 12px;">Welcome{{ $client?->name ? ', ' . $client->name : '' }}</h2>
        <p class="client-portal-summary">
          Keep track of your active jobs, open invoices, quotations, and delivery progress from one professional workspace.
        </p>
      </div>
      <div class="client-portal-actions">
        <a class="panel-btn panel-btn-primary" href="{{ route('user.projects.index') }}">View Projects</a>
        <a class="panel-btn" href="{{ route('user.invoices.index') }}">Billing Center</a>
        <a class="panel-btn" href="{{ route('user.messages.index') }}">Messages</a>
      </div>
    </div>
  </section>

  <section class="panel-grid panel-grid-kpi">
    <article class="client-portal-kpi">
      <span class="panel-kpi-label">Active Projects</span>
      <p class="client-portal-kpi-value">{{ $portalStats['active_projects'] }}</p>
      <p class="client-portal-kpi-note">Projects currently in production or delivery.</p>
    </article>
    <article class="client-portal-kpi">
      <span class="panel-kpi-label">Unpaid Invoices</span>
      <p class="client-portal-kpi-value">{{ $portalStats['unpaid_invoices'] }}</p>
      <p class="client-portal-kpi-note">Open billing items awaiting payment or review.</p>
    </article>
    <article class="client-portal-kpi">
      <span class="panel-kpi-label">Pending Quotes</span>
      <p class="client-portal-kpi-value">{{ $portalStats['pending_quotes'] }}</p>
      <p class="client-portal-kpi-note">Quotes still under review, follow-up, or revision.</p>
    </article>
    <article class="client-portal-kpi">
      <span class="panel-kpi-label">Deliveries Ready</span>
      <p class="client-portal-kpi-value">{{ $portalStats['deliveries_ready'] }}</p>
      <p class="client-portal-kpi-note">Projects with a final ZIP ready in the portal.</p>
    </article>
  </section>

  <section class="client-portal-grid-two">
    <article class="panel-card client-portal-stack">
      <div class="panel-form-row" style="justify-content: space-between; align-items: center;">
        <h2 class="panel-section-title" style="margin: 0;">Active Projects</h2>
        <a class="panel-link" href="{{ route('user.projects.index') }}">Open all</a>
      </div>
      @forelse($recentProjects as $project)
        <div class="client-portal-list-row">
          <div class="client-portal-list-main">
            <h3 class="client-portal-title">{{ $project->title }}</h3>
            <p class="client-portal-meta">
              {{ $project->service_type ?: 'Service pending' }}
              @if(!blank($project->property_address))
                &bull; {{ $project->property_address }}
              @endif
            </p>
            <p class="client-portal-meta">
              Schedule: {{ $project->scheduled_at?->format('Y-m-d H:i') ?: 'To be confirmed' }}
            </p>
          </div>
          <div class="client-portal-side">
            <span class="panel-badge">{{ $project->status }}</span>
            <a class="panel-btn panel-btn-primary" href="{{ route('user.projects.show', $project) }}">Open Project</a>
            <a class="panel-btn" href="{{ route('user.deliveries.index') }}#project-{{ $project->id }}">Delivery</a>
          </div>
        </div>
      @empty
        <div class="client-portal-empty">No projects are linked to your account yet.</div>
      @endforelse
    </article>

    <article class="panel-card client-portal-stack">
      <div class="panel-form-row" style="justify-content: space-between; align-items: center;">
        <h2 class="panel-section-title" style="margin: 0;">Open Invoices</h2>
        <a class="panel-link" href="{{ route('user.invoices.index') }}">Open billing</a>
      </div>
      @forelse($recentInvoices as $invoice)
        <div class="client-portal-list-row">
          <div class="client-portal-list-main">
            <h3 class="client-portal-title">{{ $invoice->invoice_number }}</h3>
            <p class="client-portal-meta">{{ $invoice->project?->title ?: 'General invoice' }}</p>
            <p class="client-portal-meta">Due: {{ $invoice->due_date?->format('Y-m-d') ?: 'Not set' }}</p>
          </div>
          <div class="client-portal-side">
            <span class="panel-badge">{{ $invoice->status }}</span>
            <a class="panel-btn panel-btn-primary" href="{{ route('user.invoices.download', $invoice) }}">Download</a>
          </div>
        </div>
      @empty
        <div class="client-portal-empty">No invoices are available right now.</div>
      @endforelse
    </article>
  </section>

  <section class="client-portal-grid-two">
    <article class="panel-card client-portal-stack">
      <div class="panel-form-row" style="justify-content: space-between; align-items: center;">
        <h2 class="panel-section-title" style="margin: 0;">Recent Quotations</h2>
        <a class="panel-link" href="{{ route('user.quotes.index') }}">Open quotes</a>
      </div>
      @forelse($quotes as $quote)
        <div class="client-portal-list-row">
          <div class="client-portal-list-main">
            <h3 class="client-portal-title">{{ $quote->quote_id }}</h3>
            <p class="client-portal-meta">{{ is_array($quote->services) ? implode(', ', $quote->services) : 'Services pending' }}</p>
            <p class="client-portal-meta">Submitted: {{ $quote->submitted_at?->format('Y-m-d H:i') ?: '-' }}</p>
          </div>
          <div class="client-portal-side">
            <span class="panel-badge">{{ $quote->status }}</span>
            <a class="panel-btn" href="{{ route('user.quotes.show', $quote) }}">Open Quote</a>
          </div>
        </div>
      @empty
        <div class="client-portal-empty">No quotations have been created for your account yet.</div>
      @endforelse
    </article>

    <article class="panel-card client-portal-stack">
      <div class="panel-form-row" style="justify-content: space-between; align-items: center;">
        <h2 class="panel-section-title" style="margin: 0;">Recent Messages</h2>
        <a class="panel-link" href="{{ route('user.messages.index') }}">Open messages</a>
      </div>
      @forelse($recentMessages as $message)
        <div class="client-portal-list-row">
          <div class="client-portal-list-main">
            <h3 class="client-portal-title">{{ $message->project?->title ?: 'General account message' }}</h3>
            <p class="client-portal-meta">{{ \Illuminate\Support\Str::limit($message->message, 110) }}</p>
            <p class="client-portal-meta">{{ $message->sent_at?->format('Y-m-d H:i') ?: $message->created_at?->format('Y-m-d H:i') }}</p>
          </div>
          <div class="client-portal-side">
            <span class="panel-badge">{{ $message->sender_role }}</span>
          </div>
        </div>
      @empty
        <div class="client-portal-empty">No client communication has been logged yet.</div>
      @endforelse
    </article>
  </section>

  <section class="client-portal-grid-two">
    <article class="panel-card client-portal-stack">
      <h2 class="panel-section-title">Request a New Service</h2>
      <p class="panel-muted">Send a new request directly to the team from your portal.</p>
      <form method="post" action="{{ route('user.service-requests.store') }}" class="panel-stack">
        @csrf
        @if($recentProjects->isNotEmpty())
          <select class="panel-select" name="client_project_id">
            <option value="">General request (not linked to a project)</option>
            @foreach($recentProjects as $project)
              <option value="{{ $project->id }}">{{ $project->title }}</option>
            @endforeach
          </select>
        @endif
        <input class="panel-input" type="text" name="requested_service" placeholder="Service needed" required>
        <input class="panel-input" type="text" name="subject" placeholder="Short subject (optional)">
        <input class="panel-input" type="date" name="preferred_date">
        <textarea class="panel-textarea" name="details" placeholder="Tell us about your request"></textarea>
        <button class="panel-btn panel-btn-primary" type="submit">Submit Request</button>
      </form>
    </article>

    <article class="panel-card client-portal-stack">
      <h2 class="panel-section-title">Recent Lead Activity</h2>
      @forelse($leads as $lead)
        <div class="client-portal-list-row">
          <div class="client-portal-list-main">
            <h3 class="client-portal-title">Lead #{{ $lead->id }}</h3>
            <p class="client-portal-meta">{{ $lead->service_type ?: 'Service not specified' }}</p>
            <p class="client-portal-meta">{{ $lead->location ?: 'Location pending' }}</p>
          </div>
          <div class="client-portal-side">
            <span class="panel-badge">{{ $lead->status }}</span>
            <span class="panel-muted">Score {{ $lead->score }}</span>
          </div>
        </div>
      @empty
        <div class="client-portal-empty">No website lead activity is linked to your account yet.</div>
      @endforelse
    </article>
  </section>
</div>
@endsection
