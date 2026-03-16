<section class="panel-card panel-stack" id="backup-restore">
  <h3 class="panel-section-title">Backup &amp; Restore</h3>
  <form method="post" action="{{ route('admin.system-health.backup-settings.update') }}" class="panel-stack" style="margin-bottom: 16px;">
    @csrf
    <div class="panel-form-row" style="flex-wrap: wrap;">
      <label class="panel-inline-check">
        <input type="hidden" name="enabled" value="0">
        <input type="checkbox" name="enabled" value="1" @checked((bool) ($backupSettings?->enabled ?? false))>
        Enable auto backups
      </label>
      <label class="panel-muted" style="display:flex; flex-direction:column; gap:6px;">
        Run time (HH:MM)
        <input class="panel-input" type="time" name="run_time" value="{{ $backupSettings?->run_time ?? '02:30' }}">
      </label>
      <label class="panel-muted" style="display:flex; flex-direction:column; gap:6px;">
        Keep latest backups
        <input class="panel-input" type="number" name="keep_count" min="1" max="365" value="{{ $backupSettings?->keep_count ?? 30 }}">
      </label>
    </div>
    <div class="panel-form-row" style="flex-wrap: wrap;">
      @php
        $days = ['mon' => 'Mon','tue' => 'Tue','wed' => 'Wed','thu' => 'Thu','fri' => 'Fri','sat' => 'Sat','sun' => 'Sun'];
        $selectedDays = $backupSettings?->run_days ?? [];
      @endphp
      @foreach($days as $key => $label)
        <label class="panel-inline-check">
          <input type="checkbox" name="run_days[]" value="{{ $key }}" @checked(in_array($key, $selectedDays, true))>
          {{ $label }}
        </label>
      @endforeach
    </div>
    <button class="panel-btn panel-btn-primary" type="submit">Save Backup Settings</button>
  </form>

  <form method="post" action="{{ route('admin.system-health.backup-now') }}" data-confirm="Run a backup now and download it?" style="margin-bottom: 16px;">
    @csrf
    <button class="panel-btn panel-btn-primary" type="submit">Run Backup Now &amp; Download</button>
  </form>

  <form method="post" action="{{ route('admin.system-health.backup-restore') }}" data-confirm="Restore the selected backup? This will overwrite the database." class="panel-form-row" style="flex-wrap: wrap; margin-bottom: 12px;">
    @csrf
    <select class="panel-select" name="backup_file" required>
      <option value="">Select backup file</option>
      @foreach($backupFiles as $file)
        <option value="{{ $file }}">{{ $file }}</option>
      @endforeach
    </select>
    <button class="panel-btn panel-btn-danger" type="submit">Restore Backup</button>
  </form>

  <form method="post" action="{{ route('admin.system-health.backup-upload-restore') }}" enctype="multipart/form-data" data-confirm="Upload and restore this backup? This will overwrite the database." class="panel-form-row" style="flex-wrap: wrap; margin-bottom: 16px;">
    @csrf
    <input class="panel-input" type="file" name="backup_upload" accept=".sql,.sqlite" required>
    <button class="panel-btn panel-btn-danger" type="submit">Upload &amp; Restore</button>
  </form>
  <div class="panel-table-wrap">
    <table class="panel-table">
      <thead>
        <tr>
          <th>File</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        @forelse($backupFiles as $file)
          <tr>
            <td data-label="File">{{ $file }}</td>
            <td data-label="Action">
              <a class="panel-btn" href="{{ route('admin.system-health.backup-download', ['file' => $file]) }}">Download</a>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="2" class="panel-muted">No backup files found.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</section>
