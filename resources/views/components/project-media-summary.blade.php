@props([
    'project',
    'galleryCount' => 0,
    'rawCount' => null,
    'editedCount' => null,
    'zipCount' => 0,
    'isPaid' => false,
    'showClient' => false,
])

<div>
  <h3 class="panel-section-title" style="margin-bottom: 4px;">{{ $project->title }}</h3>

  @if($showClient)
  <p class="panel-muted" style="margin: 0;">
    {{ $project->client?->name ?: ('Client #' . $project->client_id) }}
    • {{ $project->service_type ?: 'Service n/a' }}
    • {{ $project->status }}
  </p>
  @else
  <p class="panel-muted" style="margin: 0;">
    Status: {{ $project->status }}
    @if(!blank($project->service_type))
      • {{ $project->service_type }}
    @endif
  </p>
  @endif

  <p class="panel-muted" style="margin: 6px 0 0;">
    @if($rawCount !== null || $editedCount !== null)
      Raw: {{ $rawCount ?? 0 }} | Edited/Final: {{ $editedCount ?? 0 }} | Final ZIP: {{ $zipCount }} |
    @else
      Gallery: {{ $galleryCount }} | Final ZIP: {{ $zipCount }} |
    @endif
    Payment: <strong>{{ $isPaid ? 'Paid' : 'Unpaid' }}</strong>
  </p>
</div>
