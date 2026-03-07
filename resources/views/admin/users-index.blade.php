@extends('layouts.panel', [
  'title' => 'User Accounts',
  'heading' => 'User Accounts',
  'subheading' => 'Create internal team and client/agent login accounts by role.',
])

@section('content')
<section class="panel-card">
  <h2 class="panel-section-title">Create Account</h2>
  <form method="post" action="{{ route('admin.users.store') }}" class="panel-form-row">
    @csrf
    <input class="panel-input" type="text" name="name" placeholder="Full name" required>
    <input class="panel-input" type="email" name="email" placeholder="Email (login)" required>
    <input class="panel-input" type="text" name="phone" placeholder="Phone">
    <select class="panel-select" name="role" required>
      <option value="">Select role</option>
      @foreach($roles as $role)
      <option value="{{ $role }}">{{ ucfirst($role) }}</option>
      @endforeach
    </select>
    <input class="panel-input" type="text" name="password" placeholder="Password (optional, auto-generated if empty)">
    <input class="panel-input" type="text" name="company" placeholder="Company (client/agent)">
    <textarea class="panel-textarea" name="notes" placeholder="Notes (optional)"></textarea>
    <button class="panel-btn panel-btn-primary" type="submit">Create Account</button>
  </form>
</section>

<section class="panel-card">
  <h2 class="panel-section-title">All Accounts</h2>
  <form method="get" class="panel-form-row">
    <input class="panel-input" type="text" name="search" value="{{ $filters['search'] }}" placeholder="Search name/email/phone">
    <select class="panel-select" name="role">
      <option value="">All roles</option>
      @foreach($roles as $role)
      <option value="{{ $role }}" @selected($filters['role'] === $role)>{{ ucfirst($role) }}</option>
      @endforeach
    </select>
    <button class="panel-btn panel-btn-primary" type="submit">Filter</button>
    <a class="panel-link" href="{{ route('admin.users.index') }}">Clear</a>
  </form>

  <div class="panel-table-wrap">
    <table class="panel-table">
      <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Created</th><th>Action</th></tr></thead>
      <tbody>
        @forelse($users as $user)
        <tr>
          <td>#{{ $user->id }}</td>
          <td>{{ $user->name }}</td>
          <td>{{ $user->email }}</td>
          <td>{{ $user->phone ?: '-' }}</td>
          <td><span class="panel-badge">{{ $user->role }}</span></td>
          <td>{{ $user->created_at?->format('Y-m-d H:i') ?: '-' }}</td>
          <td>
            @if((int) auth()->id() !== (int) $user->id)
            <form method="post" action="{{ route('admin.users.delete', $user) }}" onsubmit="return confirm('Delete this user account?');">
              @csrf
              <button class="panel-btn panel-btn-danger panel-btn-icon" type="submit" title="Delete user account" aria-label="Delete user account"><span class="panel-icon-trash" aria-hidden="true"><svg viewBox="0 0 20 20"><path d="M5 6h10M8 6V4h4v2m-6 0l.5 9h7L14 6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span></button>
            </form>
            @else
            <span class="panel-badge">Current user</span>
            @endif
          </td>
        </tr>
        @empty
        <tr><td colspan="7" class="panel-muted">No user accounts found.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <x-panel-pagination :paginator="$users" />
</section>
@endsection
