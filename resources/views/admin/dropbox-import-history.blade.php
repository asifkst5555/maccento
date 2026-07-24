@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Dropbox Gallery Import History</h1>
            <p class="mt-2 text-sm text-gray-500">Monitor all asynchronous import queues, resume runs, and download duplicate logs.</p>
        </div>
        <a href="{{ route('admin.media-delivery.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition">
            <svg class="-ml-1 mr-2 h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to Media Delivery
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
        <form method="GET" action="{{ route('admin.media-delivery.import-history') }}" class="flex flex-col md:flex-row gap-4">
            <div class="flex-1">
                <label for="search" class="sr-only">Search</label>
                <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Search by Project Title or Dropbox Link..." class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
            </div>
            <div class="w-full md:w-48">
                <select name="status" id="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    <option value="">All Statuses</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="importing" {{ request('status') === 'importing' ? 'selected' : '' }}>Importing</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                </select>
            </div>
            <button type="submit" class="inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition">
                Search
            </button>
            @if(request()->anyFilled(['search', 'status']))
                <a href="{{ route('admin.media-delivery.import-history') }}" class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition">
                    Clear Filters
                </a>
            @endif
        </form>
    </div>

    <!-- History List -->
    <div class="bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Project</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Dropbox Folder Link</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Stage</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Progress & Stats</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Duration</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                        <th scope="col" class="px-6 py-3 class-right text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($sessions as $session)
                        <tr class="hover:bg-gray-50 transition" id="session-row-{{ $session->uuid }}">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                @if($session->project)
                                    <a href="{{ route('admin.projects.media.view', [$session->client_project_id, 0]) }}#dropbox-history" class="text-indigo-600 hover:text-indigo-900 font-semibold">
                                        {{ $session->project->title }}
                                    </a>
                                @else
                                    <span class="text-gray-400">Unknown Project</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                                <a href="{{ $session->folder_url }}" target="_blank" class="text-indigo-500 hover:underline inline-flex items-center">
                                    {{ $session->folder_url }}
                                    <svg class="ml-1 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <span class="capitalize px-2 py-0.5 rounded text-xs font-medium {{ $session->media_stage === 'edited' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }}">
                                    {{ $session->media_stage }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <div class="font-medium">
                                    {{ $session->imported_files }} / {{ $session->total_files }} Files
                                </div>
                                <div class="text-xs text-gray-500 flex flex-wrap gap-x-2 mt-0.5">
                                    <span class="text-green-600 font-semibold">{{ $session->imported_files }} imported</span>
                                    <span>•</span>
                                    <span class="text-yellow-600 font-semibold">{{ $session->duplicate_files }} duplicates</span>
                                    <span>•</span>
                                    <span class="text-red-600 font-semibold">{{ $session->failed_files }} failed</span>
                                </div>
                                @if($session->total_size > 0)
                                    <div class="text-xs text-gray-400 mt-0.5">
                                        Total Size: {{ number_format($session->total_size / (1024 * 1024), 2) }} MB
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @php
                                    $badgeClass = match ($session->status) {
                                        'pending' => 'bg-gray-100 text-gray-800',
                                        'importing' => 'bg-blue-100 text-blue-800 animate-pulse',
                                        'completed' => 'bg-green-100 text-green-800',
                                        'cancelled' => 'bg-yellow-100 text-yellow-800',
                                        'failed' => 'bg-red-100 text-red-800',
                                        default => 'bg-gray-100 text-gray-800',
                                    };
                                @endphp
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $badgeClass }}">
                                    {{ ucfirst($session->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $session->duration ? $session->duration . 's' : '--' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $session->created_at->format('M d, Y H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-2">
                                    @if(in_array($session->status, ['pending', 'importing']))
                                        <button onclick="cancelSession('{{ $session->uuid }}', '{{ $session->client_project_id }}')" class="inline-flex items-center px-2.5 py-1.5 border border-transparent text-xs font-semibold rounded text-red-700 bg-red-100 hover:bg-red-200 transition">
                                            Cancel
                                        </button>
                                    @endif

                                    @if(in_array($session->status, ['failed', 'cancelled']))
                                        <button onclick="retryFailedSession('{{ $session->uuid }}', '{{ $session->client_project_id }}')" class="inline-flex items-center px-2.5 py-1.5 border border-transparent text-xs font-semibold rounded text-indigo-700 bg-indigo-100 hover:bg-indigo-200 transition">
                                            Retry Failed
                                        </button>
                                    @endif

                                    @if($session->duplicate_files > 0)
                                        <a href="{{ route('admin.projects.dropbox.export-duplicates', [$session->client_project_id, $session->uuid]) }}" class="inline-flex items-center px-2.5 py-1.5 border border-gray-300 text-xs font-semibold rounded text-gray-700 bg-white hover:bg-gray-50 transition">
                                            Duplicate Report
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-sm text-gray-500">
                                No import history found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
            {{ $sessions->links() }}
        </div>
    </div>
</div>

<script>
    function cancelSession(uuid, projectId) {
        if (!confirm('Are you sure you want to cancel this background import session?')) {
            return;
        }

        fetch(`/admin/projects/${projectId}/dropbox/cancel-import/${uuid}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('Session cancel request submitted successfully!');
                window.location.reload();
            } else {
                alert('Error cancelling session: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(err => alert('Network error: ' + err.message));
    }

    function retryFailedSession(uuid, projectId) {
        if (!confirm('Would you like to retry all failed items from this session in the background queue?')) {
            return;
        }

        fetch(`/admin/projects/${projectId}/dropbox/retry-failed/${uuid}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('Retry job successfully dispatched to background queue!');
                window.location.reload();
            } else {
                alert('Error retrying session: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(err => alert('Network error: ' + err.message));
    }
</script>
@endsection
