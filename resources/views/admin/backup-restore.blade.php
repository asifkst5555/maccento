@extends('layouts.panel', [
  'title' => 'Backup & Restore',
  'heading' => 'Backup & Restore',
  'subheading' => 'Manage scheduled backups, downloads, and restores.',
])

@section('content')
<div class="corp-admin-shell panel-stack">
  <section class="panel-card panel-stack">
    <div class="panel-form-row" style="justify-content: space-between; align-items: center;">
      <div>
        <span class="panel-badge">Operations</span>
        <h2 class="panel-section-title" style="margin-top: 12px;">Backup &amp; Restore</h2>
        <p class="panel-muted">Keep your CRM data safe with scheduled backups and restores.</p>
      </div>
    </div>
  </section>

  @include('admin.partials.backup-restore', [
    'backupFiles' => $backupFiles,
    'backupSettings' => $backupSettings,
  ])
</div>
@endsection
