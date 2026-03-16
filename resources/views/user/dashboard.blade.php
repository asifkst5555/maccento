@extends('layouts.panel', [
  'title' => 'Client Workspace',
  'heading' => 'Client Workspace',
  'subheading' => 'Track projects, invoices, quotes, and deliveries in one client portal.',
])

@section('content')
<div class="corp-admin-shell panel-stack">
  <section class="panel-card panel-stack">
    <div class="panel-form-row" style="justify-content: space-between; align-items: center;">
      <div>
        <span class="panel-badge">Client CRM Portal</span>
        <h2 class="panel-section-title" style="margin-top: 12px;">Welcome{{ $client?->name ? ', ' . $client->name : '' }}</h2>
        <p class="panel-muted">Keep track of your active jobs, open invoices, quotations, and delivery progress from one professional workspace.</p>
      </div>
      <div class="panel-form-row" style="margin-bottom: 0;">
        <a class="panel-btn panel-btn-primary" href="{{ route('user.projects.index') }}">View Projects</a>
        <a class="panel-btn" href="{{ route('user.invoices.index') }}">Billing Center</a>
        <a class="panel-btn" href="{{ route('user.messages.index') }}">Messages</a>
      </div>
    </div>
  </section>

  <section class="panel-grid panel-grid-kpi">
    <article class="panel-card"><span class="panel-kpi-label">Active Projects</span><p class="panel-kpi-value">{{ $portalStats['active_projects'] }}</p></article>
    <article class="panel-card"><span class="panel-kpi-label">Unpaid Invoices</span><p class="panel-kpi-value">{{ $portalStats['unpaid_invoices'] }}</p></article>
    <article class="panel-card"><span class="panel-kpi-label">Pending Quotes</span><p class="panel-kpi-value">{{ $portalStats['pending_quotes'] }}</p></article>
    <article class="panel-card"><span class="panel-kpi-label">Deliveries Ready</span><p class="panel-kpi-value">{{ $portalStats['deliveries_ready'] }}</p></article>
  </section>

  <section class="panel-grid" style="grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 1rem;">
    <article class="panel-card panel-stack">
      <div class="panel-form-row" style="justify-content: space-between; align-items: center;">
        <h2 class="panel-section-title" style="margin: 0;">Active Projects</h2>
        <a class="panel-link" href="{{ route('user.projects.index') }}">Open all</a>
      </div>
      <div class="panel-table-wrap">
        <table class="panel-table">
          <thead>
            <tr>
              <th>Project</th>
              <th>Schedule</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            @forelse($recentProjects as $project)
              <tr>
                <td data-label="Project">
                  {{ $project->title }}
                  <div class="panel-muted">{{ $project->service_type ?: 'Service pending' }}</div>
                  @if(!blank($project->property_address))
                    <div class="panel-muted">{{ $project->property_address }}</div>
                  @endif
                </td>
                <td data-label="Schedule">{{ $project->scheduled_at?->format('Y-m-d H:i') ?: 'To be confirmed' }}</td>
                <td data-label="Status"><span class="panel-badge">{{ $project->status }}</span></td>
                <td data-label="Action">
                  <a class="panel-btn panel-btn-primary" href="{{ route('user.projects.show', $project) }}">Open</a>
                </td>
              </tr>
            @empty
              <tr><td colspan="4" class="panel-muted">No projects are linked to your account yet.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </article>

    <article class="panel-card panel-stack">
      <div class="panel-form-row" style="justify-content: space-between; align-items: center;">
        <h2 class="panel-section-title" style="margin: 0;">Open Invoices</h2>
        <a class="panel-link" href="{{ route('user.invoices.index') }}">Open billing</a>
      </div>
      <div class="panel-table-wrap">
        <table class="panel-table">
          <thead>
            <tr>
              <th>Invoice</th>
              <th>Due</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            @forelse($recentInvoices as $invoice)
              <tr>
                <td data-label="Invoice">
                  {{ $invoice->invoice_number }}
                  <div class="panel-muted">{{ $invoice->project?->title ?: 'General invoice' }}</div>
                </td>
                <td data-label="Due">{{ $invoice->due_date?->format('Y-m-d') ?: 'Not set' }}</td>
                <td data-label="Status"><span class="panel-badge">{{ $invoice->status }}</span></td>
                <td data-label="Action">
                  <a class="panel-btn panel-btn-primary" href="{{ route('user.invoices.download', $invoice) }}">Download</a>
                </td>
              </tr>
            @empty
              <tr><td colspan="4" class="panel-muted">No invoices are available right now.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </article>
  </section>

  <section class="panel-grid" style="grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 1rem;">
    <article class="panel-card panel-stack">
      <div class="panel-form-row" style="justify-content: space-between; align-items: center;">
        <h2 class="panel-section-title" style="margin: 0;">Recent Quotations</h2>
        <a class="panel-link" href="{{ route('user.quotes.index') }}">Open quotes</a>
      </div>
      <div class="panel-table-wrap">
        <table class="panel-table">
          <thead>
            <tr>
              <th>Quote</th>
              <th>Submitted</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            @forelse($quotes as $quote)
              <tr>
                <td data-label="Quote">
                  {{ $quote->quote_id }}
                  <div class="panel-muted">{{ is_array($quote->services) ? implode(', ', $quote->services) : 'Services pending' }}</div>
                </td>
                <td data-label="Submitted">{{ $quote->submitted_at?->format('Y-m-d H:i') ?: '-' }}</td>
                <td data-label="Status"><span class="panel-badge">{{ $quote->status }}</span></td>
                <td data-label="Action">
                  <a class="panel-btn" href="{{ route('user.quotes.show', $quote) }}">Open</a>
                </td>
              </tr>
            @empty
              <tr><td colspan="4" class="panel-muted">No quotations have been created for your account yet.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </article>

    <article class="panel-card panel-stack">
      <div class="panel-form-row" style="justify-content: space-between; align-items: center;">
        <h2 class="panel-section-title" style="margin: 0;">Recent Messages</h2>
        <a class="panel-link" href="{{ route('user.messages.index') }}">Open messages</a>
      </div>
      <div class="panel-table-wrap">
        <table class="panel-table">
          <thead>
            <tr>
              <th>Thread</th>
              <th>Message</th>
              <th>Date</th>
              <th>Role</th>
            </tr>
          </thead>
          <tbody>
            @forelse($recentMessages as $message)
              <tr>
                <td data-label="Thread">{{ $message->project?->title ?: 'General account message' }}</td>
                <td data-label="Message">{{ \Illuminate\Support\Str::limit($message->message, 90) }}</td>
                <td data-label="Date">{{ $message->sent_at?->format('Y-m-d H:i') ?: $message->created_at?->format('Y-m-d H:i') }}</td>
                <td data-label="Role"><span class="panel-badge">{{ strtoupper($message->sender_role) }}</span></td>
              </tr>
            @empty
              <tr><td colspan="4" class="panel-muted">No client communication has been logged yet.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </article>
  </section>

  <section class="panel-grid" style="grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 1rem;">
    <article class="panel-card panel-stack" id="client-request-form">
      <h2 class="panel-section-title">Request</h2>
      <p class="panel-muted">Choose a request type and share the details.</p>
      @php
        $hasProjects = $recentProjects->isNotEmpty();
      @endphp
      <form method="post" action="{{ route('user.requests.store') }}" class="panel-stack" data-unified-request-form data-has-projects="{{ $hasProjects ? '1' : '0' }}">
        @csrf
        <select class="panel-select" name="request_type" data-request-type required>
          <option value="service" @disabled(!$hasProjects)>Service request</option>
          <option value="booking">Booking request</option>
        </select>
        @if(!$hasProjects)
          <p class="panel-muted">No active projects yet. Use booking request to start a new project.</p>
        @endif
        <div data-request-project-fields @if(!$hasProjects) hidden @endif>
          <select class="panel-select" name="client_project_id" data-request-project required>
            <option value="">Select a project</option>
            @foreach($recentProjects as $project)
              <option value="{{ $project->id }}">{{ $project->title }}</option>
            @endforeach
          </select>
        </div>
        <input class="panel-input" type="text" name="requested_service" data-requested-service placeholder="Requested service" required>

        <div data-request-service-fields>
          <input class="panel-input" type="text" name="subject" placeholder="Short subject (optional)">
          <input class="panel-input" type="date" name="preferred_date">
          <textarea class="panel-textarea" name="details" placeholder="Tell us about your request"></textarea>
        </div>

        <div data-request-booking-fields hidden>
          <input class="panel-input" type="date" name="preferred_date">
          <select class="panel-select" name="preferred_time_window">
            <option value="">Preferred time window (optional)</option>
            <option value="Morning (8-12)">Morning (8-12)</option>
            <option value="Midday (12-3)">Midday (12-3)</option>
            <option value="Afternoon (3-6)">Afternoon (3-6)</option>
            <option value="Evening (6-8)">Evening (6-8)</option>
            <option value="Any time">Any time</option>
          </select>
          <textarea class="panel-textarea" name="notes" placeholder="Notes about access, timing, or priorities"></textarea>
        </div>

        <button class="panel-btn panel-btn-primary" type="submit">Submit</button>
      </form>
    </article>

    <article class="panel-card panel-stack">
      <h2 class="panel-section-title">Recent Lead Activity</h2>
      <div class="panel-table-wrap">
        <table class="panel-table">
          <thead>
            <tr>
              <th>Lead</th>
              <th>Service</th>
              <th>Location</th>
              <th>Status</th>
              <th>Score</th>
            </tr>
          </thead>
          <tbody>
            @forelse($leads as $lead)
              <tr>
                <td data-label="Lead">Lead #{{ $lead->id }}</td>
                <td data-label="Service">{{ $lead->service_type ?: 'Service not specified' }}</td>
                <td data-label="Location">{{ $lead->location ?: 'Location pending' }}</td>
                <td data-label="Status"><span class="panel-badge">{{ $lead->status }}</span></td>
                <td data-label="Score">{{ $lead->score }}</td>
              </tr>
            @empty
              <tr><td colspan="5" class="panel-muted">No website lead activity is linked to your account yet.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </article>
  </section>
</div>
<script>
  (function () {
    var form = document.querySelector('[data-unified-request-form]');
    if (!form) return;
    var typeSelect = form.querySelector('[data-request-type]');
    var serviceFields = form.querySelector('[data-request-service-fields]');
    var bookingFields = form.querySelector('[data-request-booking-fields]');
    var projectFields = form.querySelector('[data-request-project-fields]');
    var projectSelect = form.querySelector('[data-request-project]');
    var requestedService = form.querySelector('[data-requested-service]');
    var hasProjects = form.getAttribute('data-has-projects') === '1';
    if (!typeSelect) return;

    var syncFields = function () {
      var isBooking = typeSelect.value === 'booking';
      if (serviceFields) serviceFields.hidden = isBooking;
      if (bookingFields) bookingFields.hidden = !isBooking;
      if (projectFields) projectFields.hidden = isBooking || !hasProjects;
      if (projectSelect) {
        projectSelect.required = !isBooking && hasProjects;
        projectSelect.disabled = isBooking || !hasProjects;
        if (isBooking || !hasProjects) {
          projectSelect.value = '';
        }
      }
      if (requestedService) {
        requestedService.placeholder = isBooking ? 'New project request / service' : 'Requested service';
      }
    };

    if (!hasProjects) {
      typeSelect.value = 'booking';
    }

    typeSelect.addEventListener('change', syncFields);

    var anchor = window.location.hash.replace('#', '');
    if (anchor === 'service-request' || anchor === 'booking-request') {
      if (anchor === 'booking-request' || !hasProjects) {
        typeSelect.value = 'booking';
      } else {
        typeSelect.value = 'service';
      }
      var requestCard = document.getElementById('client-request-form');
      if (requestCard) {
        window.setTimeout(function () {
          requestCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 100);
      }
    }

    syncFields();
  })();
</script>
@endsection




