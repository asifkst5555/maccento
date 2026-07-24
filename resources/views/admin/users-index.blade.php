@extends('layouts.panel', [
  'title' => 'User Accounts & Authorization',
  'heading' => 'User Accounts & Authorization',
  'subheading' => 'Manage user credentials, statuses, authentication histories, and RBAC permissions.',
])

@section('content')
<style>
  .user-badge-active {
    background-color: #d1fae5;
    color: #065f46;
  }
  .user-badge-suspended {
    background-color: #fee2e2;
    color: #991b1b;
  }
  .user-badge-locked {
    background-color: #ffedd5;
    color: #9a3412;
  }
  .user-badge-archived {
    background-color: #f1f5f9;
    color: #475569;
  }
  .user-badge-pending {
    background-color: #fef9c3;
    color: #854d0e;
  }
  .user-edit-row {
    background-color: #f8fafc;
    border-left: 4px solid #b71d34;
  }
  .user-form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 16px;
    padding: 16px;
  }
  .audit-log-card {
    margin-top: 24px;
  }
  .avatar-circle {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background-color: #e2e8f0;
    color: #475569;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 0.95rem;
    border: 1px solid #cbd5e1;
  }
  .bulk-bar {
    background-color: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 12px 16px;
    margin-bottom: 20px;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 12px;
  }
</style>

<div style="display: flex; gap: 12px; margin-bottom: 20px; justify-content: flex-end;">
  <a href="{{ route('admin.permissions.index') }}" class="panel-btn panel-btn-primary">View Role-Permission Matrix</a>
</div>

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
    <input class="panel-input" type="text" name="company" placeholder="Company (client)">
    <textarea class="panel-textarea" name="notes" placeholder="Notes (optional)"></textarea>
    <button class="panel-btn panel-btn-primary" type="submit">Create Account</button>
  </form>
</section>

<section class="panel-card">
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
    <h2 class="panel-section-title" style="margin:0;">All Accounts</h2>
    <form method="get" class="panel-form-row" style="margin:0;">
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
  </div>

  {{-- Bulk Action Form --}}
  <form method="post" action="{{ route('admin.users.bulk') }}" id="bulk-form">
    @csrf
    <div class="bulk-bar">
      <span style="font-weight:600; color:#334155;">Bulk Action:</span>
      <select class="panel-select" name="action" id="bulk-action-select" required onchange="handleBulkActionChange()">
        <option value="">Select Action</option>
        <option value="activate">Bulk Activate</option>
        <option value="suspend">Bulk Suspend</option>
        <option value="change_role">Bulk Change Role</option>
        <option value="reset_password">Bulk Reset Password</option>
        <option value="delete">Bulk Delete</option>
      </select>

      <div id="bulk-role-container" style="display: none;">
        <select class="panel-select" name="bulk_role">
          <option value="">Select Role</option>
          @foreach($roles as $r)
          <option value="{{ $r }}">{{ ucfirst($r) }}</option>
          @endforeach
        </select>
      </div>

      <div id="bulk-password-container" style="display: none;">
        <input class="panel-input" type="password" name="bulk_password" placeholder="New Password">
      </div>

      <button class="panel-btn panel-btn-danger" type="submit" onclick="return confirm('Apply this bulk action to all selected users?')">Apply Action</button>
    </div>

    <div class="panel-table-wrap">
      <table class="panel-table">
        <thead>
          <tr>
            <th style="width: 40px; text-align: center;">
              <input type="checkbox" id="select-all-checkbox" onclick="toggleSelectAll(this)" aria-label="Select all user rows">
            </th>
            <th>User</th>
            <th>Email</th>
            <th>Role</th>
            <th>Status</th>
            <th>Last Login</th>
            <th>Created</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse($users as $user)
          <tr id="view-row-{{ $user->id }}">
            <td style="text-align: center; vertical-align: middle;">
              <input type="checkbox" class="user-select-checkbox" name="user_ids[]" value="{{ $user->id }}" aria-label="Select user row">
            </td>
            <td>
              <div style="display: flex; align-items: center; gap: 10px;">
                <div class="avatar-circle">
                  {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div>
                  <strong>{{ $user->name }}</strong><br>
                  <span class="panel-muted">{{ $user->phone ?: '-' }}</span>
                </div>
              </div>
            </td>
            <td>{{ $user->email }}</td>
            <td><span class="panel-badge">{{ ucfirst($user->role) }}</span></td>
            <td>
              <span class="panel-badge user-badge-{{ $user->status ?? 'active' }}">
                {{ ucfirst(str_replace('_', ' ', $user->status ?? 'active')) }}
              </span>
            </td>
            <td>{{ $user->last_login_at?->diffForHumans() ?: 'Never' }}</td>
            <td>{{ $user->created_at?->format('Y-m-d H:i') ?: '-' }}</td>
            <td>
              <div style="display: flex; gap: 6px;">
                <button class="panel-btn" type="button" onclick="toggleEditRow({{ $user->id }})">Edit / Role</button>
                <button class="panel-btn" type="button" onclick="togglePasswordRow({{ $user->id }})">Reset Pass</button>
                @if((int) auth()->id() !== (int) $user->id)
                <button class="panel-btn panel-btn-danger panel-btn-icon" type="submit" formaction="{{ route('admin.users.delete', $user) }}" onclick="return confirm('Delete this user account?')">
                  <span class="panel-icon-trash" aria-hidden="true"><x-panel-icon name="trash" /></span>
                </button>
                @else
                <span class="panel-badge">Self</span>
                @endif
              </div>
            </td>
          </tr>

          {{-- Edit Details and Role Row --}}
          <tr id="edit-row-{{ $user->id }}" class="user-edit-row" style="display: none;">
            <td colspan="8">
              <div class="user-form-container" style="padding: 16px;">
                <div class="user-form-grid">
                  <div>
                    <label class="panel-label">Name</label>
                    <input class="panel-input" type="text" name="edit_users[{{ $user->id }}][name]" value="{{ $user->name }}" id="edit-name-{{ $user->id }}" style="width:100%;">
                  </div>
                  <div>
                    <label class="panel-label">Email</label>
                    <input class="panel-input" type="email" name="edit_users[{{ $user->id }}][email]" value="{{ $user->email }}" id="edit-email-{{ $user->id }}" style="width:100%;">
                  </div>
                  <div>
                    <label class="panel-label">Phone</label>
                    <input class="panel-input" type="text" name="edit_users[{{ $user->id }}][phone]" value="{{ $user->phone }}" id="edit-phone-{{ $user->id }}" style="width:100%;">
                  </div>
                  <div>
                    <label class="panel-label">Assigned Role</label>
                    <select class="panel-select" name="edit_users[{{ $user->id }}][role]" id="edit-role-{{ $user->id }}" style="width:100%;">
                      @foreach($roles as $r)
                      <option value="{{ $r }}" @selected($user->role === $r)>{{ ucfirst($r) }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div>
                    <label class="panel-label">Account Status</label>
                    <select class="panel-select" name="edit_users[{{ $user->id }}][status]" id="edit-status-{{ $user->id }}" style="width:100%;">
                      <option value="active" @selected(($user->status ?? 'active') === 'active')>Active</option>
                      <option value="suspended" @selected(($user->status ?? 'active') === 'suspended')>Suspended</option>
                      <option value="locked" @selected(($user->status ?? 'active') === 'locked')>Locked</option>
                      <option value="archived" @selected(($user->status ?? 'active') === 'archived')>Archived</option>
                      <option value="pending_verification" @selected(($user->status ?? 'active') === 'pending_verification')>Pending Verification</option>
                    </select>
                  </div>
                  <div>
                    <label class="panel-label">Role Change Reason (Audited)</label>
                    <input class="panel-input" type="text" name="edit_users[{{ $user->id }}][reason]" id="edit-reason-{{ $user->id }}" placeholder="Reason for updating role" style="width:100%;">
                  </div>
                </div>
                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                  <button class="panel-btn" type="button" onclick="toggleEditRow({{ $user->id }})">Cancel</button>
                  <button class="panel-btn panel-btn-primary" type="button" onclick="submitSingleUserUpdate({{ $user->id }})">Save Changes</button>
                </div>
              </div>
            </td>
          </tr>

          {{-- Reset Password Row --}}
          <tr id="password-row-{{ $user->id }}" class="user-edit-row" style="display: none; background-color: #fef8f8;">
            <td colspan="8">
              <div class="user-form-container" style="padding: 16px;">
                <div class="user-form-grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));">
                  <div>
                    <label class="panel-label">New Password</label>
                    <input class="panel-input" type="password" id="reset-pass-{{ $user->id }}" placeholder="Minimum 8 chars, mixed case, symbols" style="width:100%;">
                  </div>
                  <div>
                    <label class="panel-label">Confirm New Password</label>
                    <input class="panel-input" type="password" id="reset-pass-confirm-{{ $user->id }}" placeholder="Confirm password" style="width:100%;">
                  </div>
                </div>
                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                  <button class="panel-btn" type="button" onclick="togglePasswordRow({{ $user->id }})">Cancel</button>
                  <button class="panel-btn panel-btn-danger" type="button" onclick="submitSinglePasswordReset({{ $user->id }})">Reset Password</button>
                </div>
              </div>
            </td>
          </tr>
          @empty
          <tr><td colspan="8" class="panel-muted">No user accounts found.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </form>
  <x-panel-pagination :paginator="$users" />
</section>

{{-- Login History logs --}}
<section class="panel-card audit-log-card">
  <h2 class="panel-section-title">Login History & Session Tracking</h2>
  <p class="panel-muted" style="margin-bottom:12px;">Real-time login session status, successful entries, blocked status, and device fingerprints.</p>
  <div class="panel-table-wrap">
    <table class="panel-table">
      <thead>
        <tr>
          <th>Login Time</th>
          <th>User / Email</th>
          <th>Status</th>
          <th>IP Address</th>
          <th>System / Browser</th>
          <th>Device</th>
          <th>Logout Time</th>
        </tr>
      </thead>
      <tbody>
        @forelse($loginRecords as $record)
        <tr>
          <td>{{ $record->login_at?->format('Y-m-d H:i:s') ?: '-' }}</td>
          <td>
            @if($record->user)
            <strong>{{ $record->user->name }}</strong><br>
            @endif
            <span class="panel-muted">{{ $record->email ?: ($record->user?->email ?: '-') }}</span>
          </td>
          <td>
            @if($record->is_success)
            <span class="panel-badge user-badge-active">Success</span>
            @else
            <span class="panel-badge user-badge-suspended" title="{{ $record->failure_reason }}">Failed</span><br>
            <small style="color: #991b1b; font-size: 0.75rem;">{{ $record->failure_reason }}</small>
            @endif
          </td>
          <td><code>{{ $record->ip_address ?: '-' }}</code></td>
          <td>
            <span>{{ $record->operating_system ?: 'Unknown OS' }}</span> / 
            <small class="panel-muted">{{ $record->browser ?: 'Unknown Browser' }}</small>
          </td>
          <td>
            <span class="panel-badge" style="background:#e2e8f0; color:#1e293b;">
              {{ ucfirst($record->device ?: 'Desktop') }}
            </span>
          </td>
          <td>{{ $record->logout_at?->format('Y-m-d H:i:s') ?: 'Active Session' }}</td>
        </tr>
        @empty
        <tr>
          <td colspan="7" class="panel-muted">No login histories tracked yet.</td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</section>

{{-- Helper forms for single action submissions --}}
<form id="single-action-form" method="post" style="display:none;">
  @csrf
  <input type="hidden" name="name" id="single-name">
  <input type="hidden" name="email" id="single-email">
  <input type="hidden" name="phone" id="single-phone">
  <input type="hidden" name="role" id="single-role">
  <input type="hidden" name="status" id="single-status">
  <input type="hidden" name="reason" id="single-reason">
</form>

<form id="single-password-form" method="post" style="display:none;">
  @csrf
  <input type="hidden" name="password" id="single-pass">
  <input type="hidden" name="password_confirmation" id="single-pass-confirm">
</form>

<script>
  function toggleEditRow(id) {
    const editRow = document.getElementById(`edit-row-${id}`);
    const passRow = document.getElementById(`password-row-${id}`);
    if (editRow.style.display === 'none') {
      editRow.style.display = 'table-row';
      passRow.style.display = 'none';
    } else {
      editRow.style.display = 'none';
    }
  }

  function togglePasswordRow(id) {
    const passRow = document.getElementById(`password-row-${id}`);
    const editRow = document.getElementById(`edit-row-${id}`);
    if (passRow.style.display === 'none') {
      passRow.style.display = 'table-row';
      editRow.style.display = 'none';
    } else {
      passRow.style.display = 'none';
    }
  }

  function handleBulkActionChange() {
    const action = document.getElementById('bulk-action-select').value;
    const roleContainer = document.getElementById('bulk-role-container');
    const passContainer = document.getElementById('bulk-password-container');

    roleContainer.style.display = action === 'change_role' ? 'block' : 'none';
    passContainer.style.display = action === 'reset_password' ? 'block' : 'none';
  }

  function toggleSelectAll(master) {
    const checkboxes = document.querySelectorAll('.user-select-checkbox');
    checkboxes.forEach(cb => {
      cb.checked = master.checked;
    });
  }

  function submitSingleUserUpdate(id) {
    const form = document.getElementById('single-action-form');
    form.action = `/admin/users/${id}/update`;

    document.getElementById('single-name').value = document.getElementById(`edit-name-${id}`).value;
    document.getElementById('single-email').value = document.getElementById(`edit-email-${id}`).value;
    document.getElementById('single-phone').value = document.getElementById(`edit-phone-${id}`).value;
    document.getElementById('single-role').value = document.getElementById(`edit-role-${id}`).value;
    document.getElementById('single-status').value = document.getElementById(`edit-status-${id}`).value;
    document.getElementById('single-reason').value = document.getElementById(`edit-reason-${id}`).value;

    form.submit();
  }

  function submitSinglePasswordReset(id) {
    const form = document.getElementById('single-password-form');
    form.action = `/admin/users/${id}/reset-password`;

    document.getElementById('single-pass').value = document.getElementById(`reset-pass-${id}`).value;
    document.getElementById('single-pass-confirm').value = document.getElementById(`reset-pass-confirm-${id}`).value;

    form.submit();
  }
</script>
@endsection
