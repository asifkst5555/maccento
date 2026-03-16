@extends('layouts.panel', [
  'title' => 'My Projects',
  'heading' => 'Projects',
  'subheading' => 'Monitor schedules, status, and production progress across your account.',
])

@section('content')
<div class="corp-admin-shell panel-stack">
  <section class="panel-grid panel-grid-kpi">
    <article class="panel-card">
      <span class="panel-kpi-label">Active Projects</span>
      <p class="panel-kpi-value">{{ $portalStats['active_projects'] }}</p>
    </article>
    <article class="panel-card">
      <span class="panel-kpi-label">Deliveries Ready</span>
      <p class="panel-kpi-value">{{ $portalStats['deliveries_ready'] }}</p>
    </article>
    <article class="panel-card">
      <span class="panel-kpi-label">Portal Messages</span>
      <p class="panel-kpi-value">{{ $portalStats['message_count'] }}</p>
    </article>
  </section>

  <section class="panel-card panel-stack">
    <div class="panel-form-row" style="justify-content: space-between; align-items: center;">
      <h2 class="panel-section-title" style="margin: 0;">Project Portfolio</h2>
      <div class="panel-form-row" style="margin-bottom: 0;">
        <a class="panel-btn" href="{{ route('user.deliveries.index') }}">Open Deliveries</a>
        <button class="panel-btn panel-btn-primary" type="button" data-projects-request-open>Request</button>
      </div>
    </div>

    <div class="panel-table-wrap">
      <table class="panel-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Project</th>
            <th>Service</th>
            <th>Schedule</th>
            <th>Due</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse($projects as $project)
            <tr>
              <td data-label="ID">#{{ $project->id }}</td>
              <td data-label="Project">
                {{ $project->title }}<br>
                <span class="panel-muted">{{ $project->property_address ?: '-' }}</span>
              </td>
              <td data-label="Service">{{ $project->service_type ?: '-' }}</td>
              <td data-label="Schedule">{{ $project->scheduled_at?->format('Y-m-d H:i') ?: '-' }}</td>
              <td data-label="Due">{{ $project->due_at?->format('Y-m-d H:i') ?: '-' }}</td>
              <td data-label="Status"><span class="panel-badge">{{ $project->status }}</span></td>
              <td data-label="Action">
                <div class="panel-form-row" style="margin-bottom: 0;">
                  <a class="panel-btn panel-btn-primary" href="{{ route('user.projects.show', $project) }}">Open project</a>
                  <a class="panel-btn" href="{{ route('user.deliveries.index') }}#project-{{ $project->id }}">Delivery</a>
                  @if($project->quoteBuild)
                    <a class="panel-btn" href="{{ route('user.quotes.show', $project->quoteBuild) }}">Linked Quote</a>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="panel-muted">No projects are currently linked to your client account.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <x-panel-pagination :paginator="$projects" />
  </section>
</div>

<div class="panel-modal" data-projects-request-modal hidden>
  <div class="panel-modal-backdrop" data-projects-request-close></div>
  <div class="panel-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="projects-request-title">
    <div class="panel-modal-head">
      <h3 id="projects-request-title" class="panel-modal-title">Request</h3>
      <button class="panel-modal-close" type="button" data-projects-request-close aria-label="Close request form">Ã—</button>
    </div>
    <div class="panel-modal-body">
      <p class="panel-muted">Choose a request type and share the details.</p>
      <form method="post" action="{{ route('user.requests.store') }}" class="panel-stack" data-unified-request-form>
        @csrf
        <select class="panel-select" name="request_type" data-request-type required>
          <option value="service">Service request</option>
          <option value="booking">Booking request</option>
        </select>
        <div data-request-project-fields>
          <select class="panel-select" name="client_project_id" data-request-project required>
            <option value="">Select a project</option>
            @foreach($projects as $project)
              <option value="{{ $project->id }}">{{ $project->title }}</option>
            @endforeach
          </select>
        </div>
        <input class="panel-input" type="text" name="requested_service" data-requested-service placeholder="Requested service" required>

        <div data-request-service-fields>
          <input class="panel-input" type="text" name="subject" placeholder="Subject (optional)">
          <input class="panel-input" type="date" name="preferred_date">
          <textarea class="panel-textarea" name="details" placeholder="Add details for the team"></textarea>
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

        <div class="panel-form-row" style="justify-content: flex-end;">
          <button class="panel-btn" type="button" data-projects-request-close>Cancel</button>
          <button class="panel-btn panel-btn-primary" type="submit">Submit</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  (function () {
    var modal = document.querySelector('[data-projects-request-modal]');
    var openBtn = document.querySelector('[data-projects-request-open]');
    if (!modal || !openBtn) return;

    var closeButtons = modal.querySelectorAll('[data-projects-request-close]');
    var typeSelect = modal.querySelector('[data-request-type]');
    var serviceFields = modal.querySelector('[data-request-service-fields]');
    var bookingFields = modal.querySelector('[data-request-booking-fields]');
    var projectFields = modal.querySelector('[data-request-project-fields]');
    var projectSelect = modal.querySelector('[data-request-project]');
    var requestedService = modal.querySelector('[data-requested-service]');
    var closeModal = function () {
      modal.hidden = true;
      modal.classList.remove('is-open');
      document.body.classList.remove('panel-modal-open');
    };
    var syncFields = function () {
      var isBooking = typeSelect && typeSelect.value === 'booking';
      if (serviceFields) serviceFields.hidden = isBooking;
      if (bookingFields) bookingFields.hidden = !isBooking;
      if (projectFields) projectFields.hidden = isBooking;
      if (projectSelect) {
        projectSelect.required = !isBooking;
        projectSelect.disabled = isBooking;
        if (isBooking) {
          projectSelect.value = '';
        }
      }
      if (requestedService) {
        requestedService.placeholder = isBooking ? 'New project request / service' : 'Requested service';
      }
    };

    openBtn.addEventListener('click', function () {
      modal.hidden = false;
      modal.classList.add('is-open');
      document.body.classList.add('panel-modal-open');
      syncFields();
    });

    closeButtons.forEach(function (btn) {
      btn.addEventListener('click', closeModal);
    });

    modal.addEventListener('click', function (event) {
      if (event.target === modal) closeModal();
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && !modal.hidden) closeModal();
    });

    if (typeSelect) {
      typeSelect.addEventListener('change', syncFields);
    }
  })();
</script>
@endsection


