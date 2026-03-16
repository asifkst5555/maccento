@extends('layouts.panel', [
  'title' => 'Project Workspace',
  'heading' => 'Project Workspace',
  'subheading' => 'Manage assignments, invoices, and comments for this project in one place.',
])

@section('content')
<style>
  .corp-admin-shell {
    --corp-ink: #10233a;
    --corp-ink-soft: #586b83;
    --corp-line: #d6e0ec;
    --corp-surface: #ffffff;
    --corp-soft: #f3f7fc;
    --corp-accent: #c11f37;
    --corp-shadow: 0 14px 30px rgba(16, 35, 58, 0.08);
  }

  .corp-admin-shell .panel-card {
    border: 1px solid var(--corp-line);
    border-radius: 14px;
    background: var(--corp-surface);
    box-shadow: var(--corp-shadow);
  }

  .project-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
  }

  .project-title {
    font-size: 1.35rem;
    font-weight: 700;
    color: var(--corp-ink);
    margin: 0;
  }

  .project-subtitle {
    margin: 6px 0 0;
    color: var(--corp-ink-soft);
  }

  .project-workspace-grid {
    display: grid;
    grid-template-columns: minmax(0, 2.2fr) minmax(0, 1fr);
    gap: 16px;
  }

  .project-stack {
    display: grid;
    gap: 14px;
  }

  .panel-section-title {
    margin: 0 0 8px;
    font-size: 1.05rem;
  }

  .project-meta {
    display: grid;
    gap: 6px;
    color: var(--corp-ink-soft);
  }

  .project-meta strong {
    color: var(--corp-ink);
  }

  .status-badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 10px;
    border-radius: 999px;
    border: 1px solid #c5d3e3;
    background: #eff5fc;
    color: #203b59;
    font-weight: 700;
    font-size: 0.72rem;
    text-transform: capitalize;
  }

  .panel-input,
  .panel-select,
  .panel-textarea {
    border-radius: 10px;
    border: 1px solid #c9d6e5;
    background-color: #fff;
  }

  .panel-btn {
    border-radius: 10px;
    border: 1px solid #bfcfe0;
    font-weight: 600;
  }

  .panel-btn-primary {
    background: linear-gradient(90deg, #b71d34 0%, #cc2741 100%);
    border-color: #a5172d;
    color: #ffffff;
  }

  .panel-multi {
    border: 1px solid #c9d6e5;
    border-radius: 10px;
    padding: 0.65rem;
    background: #ffffff;
    display: grid;
    gap: 0.45rem;
    max-height: 220px;
    overflow: auto;
  }

  .panel-multi label {
    display: grid;
    grid-template-columns: 18px minmax(0, 1fr);
    gap: 0.55rem;
    align-items: center;
    padding: 0.4rem 0.55rem;
    border-radius: 8px;
    border: 1px solid transparent;
    background: #f8fbff;
    color: var(--corp-ink);
    font-weight: 600;
    font-size: 0.88rem;
  }

  .panel-multi input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: var(--corp-accent);
  }

  .comment-list {
    display: grid;
    gap: 10px;
  }

  .comment-card {
    border: 1px solid #d7e2ef;
    border-radius: 10px;
    padding: 10px 12px;
    background: #ffffff;
  }

  .comment-meta {
    font-size: 0.82rem;
    color: var(--corp-ink-soft);
    margin-bottom: 6px;
  }

  .project-side-actions {
    display: grid;
    gap: 10px;
  }

  @media (max-width: 1024px) {
    .project-workspace-grid {
      grid-template-columns: minmax(0, 1fr);
    }
  }
</style>

@php
  $assignedIds = $project->assignments?->pluck('user_id')->map(static fn ($id) => (int) $id)->all() ?? [];
@endphp

<div class="corp-admin-shell">
  <section class="panel-card project-header">
    <div>
      <h2 class="project-title">{{ $project->title }}</h2>
      <p class="project-subtitle">
        {{ $project->client?->name ?: ('Client #' . $project->client_id) }}
        @if($project->client?->email)
          · {{ $project->client->email }}
        @endif
      </p>
    </div>
    <span class="status-badge">{{ $project->status }}</span>
  </section>

  <section class="project-workspace-grid" style="margin-top: 16px;">
    <div class="project-stack">
      <article class="panel-card">
        <h3 class="panel-section-title">Assign Team</h3>
        @if($canManageProjects)
        <form method="post" action="{{ route('admin.projects.assignments.update', $project) }}" class="panel-stack">
          @csrf
          <div class="panel-multi">
            @foreach($assignableUsers as $user)
              <label>
                <input type="checkbox" name="assigned_user_ids[]" value="{{ $user->id }}" @checked(in_array((int) $user->id, $assignedIds, true))>
                <span>{{ $user->name }} @if($user->role) - {{ ucfirst($user->role) }} @endif</span>
              </label>
            @endforeach
          </div>
          <button class="panel-btn panel-btn-primary" type="submit">Save Team</button>
        </form>
        @else
        <p class="panel-muted">You have read-only access.</p>
        @endif
      </article>

      <article class="panel-card">
        <h3 class="panel-section-title">Create Invoice</h3>
        @if($canManageProjects && $project->client)
        <form method="post" action="{{ route('admin.clients.invoices.store', $project->client) }}" class="panel-stack">
          @csrf
          <input type="hidden" name="client_project_id" value="{{ $project->id }}">
          <div class="panel-form-row">
            <input class="panel-input" type="number" step="0.01" min="0.01" name="amount" placeholder="Amount" required>
            <select class="panel-input" name="currency" data-select-flags="currency" required>
              @foreach($currencyOptions ?? ['USD' => 'US Dollar'] as $code => $label)
                <option value="{{ $code }}" @selected(($defaultCurrency ?? 'USD') === $code)>{{ $code }} - {{ $label }}</option>
              @endforeach
            </select>
            <select class="panel-select" name="status" required>
              @foreach(['sent','partial','overdue','draft','paid'] as $status)
              <option value="{{ $status }}" @selected($status === 'sent')>{{ ucfirst($status) }}</option>
              @endforeach
            </select>
          </div>
          <div class="panel-form-row">
            <input class="panel-input" type="date" name="issued_at" value="{{ now()->toDateString() }}">
            <input class="panel-input" type="date" name="due_date">
          </div>
          <textarea class="panel-textarea" name="notes" placeholder="Invoice notes (optional)"></textarea>
          <button class="panel-btn panel-btn-primary" type="submit">Create Invoice</button>
        </form>
        @else
        <p class="panel-muted">Invoice creation is not available.</p>
        @endif
      </article>

      <article class="panel-card">
        <h3 class="panel-section-title">Project Tasks</h3>
        @if($canManageProjects)
        <form method="post" action="{{ route('admin.projects.tasks.store', $project) }}" class="panel-stack" style="margin-bottom: 14px;">
          @csrf
          <input class="panel-input" type="text" name="title" placeholder="Task title" required>
          <textarea class="panel-textarea" name="notes" rows="2" placeholder="Notes (optional)"></textarea>
          <div class="panel-form-row">
            <input class="panel-input" type="date" name="due_date">
            <select class="panel-select" name="assigned_to">
              <option value="">Assign to</option>
              @foreach($assignableUsers as $user)
                <option value="{{ $user->id }}">{{ $user->name }} @if($user->role) - {{ ucfirst($user->role) }} @endif</option>
              @endforeach
            </select>
            <button class="panel-btn panel-btn-primary" type="submit">Add Task</button>
          </div>
        </form>
        @endif

        @php
          $tasks = $project->tasks?->sortBy([
            ['status', 'asc'],
            ['due_date', 'asc'],
            ['id', 'desc'],
          ]) ?? collect();
        @endphp
        <div class="comment-list">
          @forelse($tasks as $task)
            <div class="comment-card">
              <div class="comment-meta">
                {{ $task->title }}
                @if($task->due_date)
                  · Due {{ $task->due_date->format('Y-m-d') }}
                @endif
                @if($task->assignee)
                  · {{ $task->assignee->name }}
                @endif
              </div>
              @if($task->notes)
                <div class="panel-muted" style="margin-bottom: 8px;">{{ $task->notes }}</div>
              @endif
              @if($canManageProjects)
              <form method="post" action="{{ route('admin.projects.tasks.update', [$project, $task]) }}" class="panel-form-row" style="flex-wrap: wrap;">
                @csrf
                <select class="panel-select" name="status">
                  @foreach(['open','in_progress','blocked','done'] as $status)
                    <option value="{{ $status }}" @selected($task->status === $status)>{{ ucfirst(str_replace('_',' ', $status)) }}</option>
                  @endforeach
                </select>
                <select class="panel-select" name="assigned_to">
                  <option value="">Unassigned</option>
                  @foreach($assignableUsers as $user)
                    <option value="{{ $user->id }}" @selected((int) $task->assigned_to === (int) $user->id)>{{ $user->name }}</option>
                  @endforeach
                </select>
                <input class="panel-input" type="date" name="due_date" value="{{ $task->due_date?->format('Y-m-d') }}">
                <button class="panel-btn panel-btn-primary" type="submit">Update</button>
              </form>
              <form method="post" action="{{ route('admin.projects.tasks.delete', [$project, $task]) }}" data-confirm="Delete task {{ $task->title }}?" style="margin-top: 8px;">
                @csrf
                <button class="panel-btn panel-btn-danger" type="submit">Delete</button>
              </form>
              @else
                <span class="status-badge">{{ $task->status }}</span>
              @endif
            </div>
          @empty
            <p class="panel-muted">No tasks yet.</p>
          @endforelse
        </div>
      </article>

      <article class="panel-card">
        <h3 class="panel-section-title">Project Comments</h3>
        <div class="comment-list">
          @forelse($project->comments ?? [] as $comment)
            <div class="comment-card">
              <div class="comment-meta">
                {{ $comment->user?->name ?: 'Team member' }} · {{ $comment->created_at?->format('Y-m-d H:i') }}
              </div>
              <div>{{ $comment->body }}</div>
            </div>
          @empty
            <p class="panel-muted">No comments yet.</p>
          @endforelse
        </div>
        @if($canManageProjects)
        <form method="post" action="{{ route('admin.projects.comments.store', $project) }}" class="panel-stack" style="margin-top: 12px;">
          @csrf
          <textarea class="panel-textarea" name="body" rows="3" placeholder="Add a comment..." required></textarea>
          <button class="panel-btn panel-btn-primary" type="submit">Post Comment</button>
        </form>
        @endif
      </article>
    </div>

    <aside class="project-stack">
      <article class="panel-card">
        <h3 class="panel-section-title">Project Overview</h3>
        <div class="project-meta">
          <div><strong>Schedule:</strong> {{ $project->scheduled_at?->format('Y-m-d H:i') ?: '-' }}</div>
          <div><strong>Due:</strong> {{ $project->due_at?->format('Y-m-d H:i') ?: '-' }}</div>
          <div><strong>Status:</strong> {{ ucfirst($project->status) }}</div>
          <div><strong>Invoices:</strong> {{ $project->invoices?->count() ?? 0 }}</div>
          <div><strong>Open Tasks:</strong> {{ $project->tasks?->whereIn('status', ['open','in_progress','blocked'])->count() ?? 0 }}</div>
        </div>
        @if($canManageProjects)
        <form method="post" action="{{ route('admin.projects.status', $project) }}" class="panel-stack" style="margin-top: 12px;">
          @csrf
          <div class="panel-form-row">
            <select class="panel-select" name="status" required>
              @foreach(['accepted','shooting','editing','delivered','cancelled','archived'] as $status)
              <option value="{{ $status }}" @selected($project->status === $status)>{{ ucfirst($status) }}</option>
              @endforeach
            </select>
            <button class="panel-btn panel-btn-primary" type="submit">Update Status</button>
          </div>
        </form>
        @endif
      </article>

      <article class="panel-card">
        <h3 class="panel-section-title">Quick Actions</h3>
        <div class="project-side-actions">
          @if($project->client)
          <a class="panel-btn panel-btn-primary" href="{{ route('admin.clients.show', $project->client) }}">Open Client</a>
          @endif
          <a class="panel-btn" href="{{ route('admin.projects.calendar', $project) }}">Add to Calendar (.ics)</a>
          <a class="panel-btn" href="{{ route('admin.media-delivery.index', ['media_search' => $project->title]) }}#project-{{ $project->id }}">Media Delivery</a>
          <a class="panel-btn" href="{{ route('admin.invoices.index', ['invoice_project' => $project->id]) }}">Project Invoices</a>
          <a class="panel-btn" href="{{ route('user.projects.show', $project) }}">Open Client Portal</a>
        </div>
      </article>
    </aside>
  </section>
</div>
@endsection
