@extends('layouts.panel', [
  'title' => 'Role-Permission Matrix',
  'heading' => 'Role-Permission Matrix',
  'subheading' => 'Configure dynamic capabilities across enterprise roles. Changes apply instantly.',
])

@section('content')
<style>
  .matrix-table th {
    text-align: center;
    vertical-align: middle;
  }
  .matrix-table td {
    text-align: center;
    vertical-align: middle;
  }
  .matrix-checkbox {
    width: 20px;
    height: 20px;
    cursor: pointer;
    accent-color: #b71d34;
  }
  .role-header-col {
    min-width: 120px;
  }
  .perm-desc {
    display: block;
    font-size: 0.75rem;
    color: #64748b;
    margin-top: 2px;
  }
</style>

<section class="panel-card">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h2 class="panel-section-title" style="margin: 0;">Global Access Control</h2>
    <a href="{{ route('admin.users.index') }}" class="panel-btn">Back to Users</a>
  </div>

  <form method="post" action="{{ route('admin.permissions.update') }}">
    @csrf
    <div class="panel-table-wrap">
      <table class="panel-table matrix-table">
        <thead>
          <tr>
            <th style="text-align: left; min-width: 250px;">Capability / Permission</th>
            @foreach($roles as $role)
            <th class="role-header-col">
              <strong>{{ $role->display_name }}</strong><br>
              <code style="font-size:0.75rem; color:#b71d34;">{{ $role->name }}</code>
            </th>
            @endforeach
          </tr>
        </thead>
        <tbody>
          @foreach($permissions as $perm)
          <tr>
            <td style="text-align: left;">
              <strong>{{ $perm->display_name }}</strong><br>
              <span class="perm-desc">{{ $perm->description ?: 'No description provided.' }}</span>
              <code style="font-size: 0.7rem; background:#f1f5f9; padding: 2px 4px; border-radius: 3px;">{{ $perm->name }}</code>
            </td>
            @foreach($roles as $role)
            <td>
              @if($role->name === 'super_admin')
              {{-- Super Admin has all permissions, locked --}}
              <input class="matrix-checkbox" type="checkbox" checked disabled title="Super Admin has absolute authority" aria-label="Super Admin check status">
              @else
              <input class="matrix-checkbox" type="checkbox" name="matrix[{{ $role->id }}][{{ $perm->id }}]" value="{{ $perm->id }}"
                @checked($role->permissions->contains($perm->id)) aria-label="Permission state for role">
              @endif
            </td>
            @endforeach
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    <div style="margin-top: 20px; display: flex; justify-content: flex-end;">
      <button class="panel-btn panel-btn-primary" type="submit">Save Matrix Configuration</button>
    </div>
  </form>
</section>
@endsection
