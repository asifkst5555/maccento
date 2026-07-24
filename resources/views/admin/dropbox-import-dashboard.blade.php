@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Cloud Import Analytics Dashboard</h1>
            <p class="mt-2 text-sm text-gray-500">Real-time statistics, storage quota limits, average download performance, and providers health indicators.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.media-delivery.providers') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition">
                Configure Providers
            </a>
            <a href="{{ route('admin.media-delivery.import-history') }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 transition">
                Import History Logs
            </a>
        </div>
    </div>

    <!-- Analytics Counters Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-200">
            <div class="text-sm font-medium text-gray-500 truncate">Running Imports</div>
            <div class="mt-2 flex items-center justify-between">
                <span class="text-3xl font-bold text-indigo-600">{{ $running }}</span>
                @if($running > 0)
                <span class="flex h-3 w-3 relative">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-indigo-500"></span>
                </span>
                @endif
            </div>
        </div>
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-200">
            <div class="text-sm font-medium text-gray-500 truncate">Completed</div>
            <div class="mt-2 text-3xl font-bold text-emerald-600">{{ $completed }}</div>
        </div>
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-200">
            <div class="text-sm font-medium text-gray-500 truncate">Failed</div>
            <div class="mt-2 text-3xl font-bold text-rose-600">{{ $failed }}</div>
        </div>
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-200">
            <div class="text-sm font-medium text-gray-500 truncate">Cancelled</div>
            <div class="mt-2 text-3xl font-bold text-amber-500">{{ $cancelled }}</div>
        </div>
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-200">
            <div class="text-sm font-medium text-gray-500 truncate">Pending in Queue</div>
            <div class="mt-2 text-3xl font-bold text-gray-600">{{ $pending }}</div>
        </div>
    </div>

    <!-- Quota Alerts & Storage Metrics -->
    <div class="grid lg:grid-cols-3 gap-8 mb-8">
        <!-- Storage Metrics -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 col-span-2">
            <h3 class="text-lg font-bold text-gray-900 mb-4">MAM Storage Usage & Growth</h3>
            
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div class="bg-slate-50 p-4 rounded-lg">
                    <span class="block text-xs font-semibold text-gray-500 uppercase">Total Files Imported</span>
                    <span class="text-2xl font-bold text-slate-800">{{ number_format($totalFiles) }}</span>
                </div>
                <div class="bg-slate-50 p-4 rounded-lg">
                    <span class="block text-xs font-semibold text-gray-500 uppercase">Storage Consumed</span>
                    <span class="text-2xl font-bold text-slate-800">{{ round($totalStorage / 1073741824, 2) }} GB</span>
                </div>
            </div>

            <!-- CSS Bar Graph (Feature 5) -->
            <div class="mb-4">
                <h4 class="text-xs font-semibold text-gray-500 uppercase mb-3">Daily Import Volume Trends</h4>
                <div class="flex items-end justify-between gap-1 h-36 bg-slate-50 p-4 rounded-lg">
                    @forelse($dailyImports as $d)
                        @php
                            $maxFiles = $dailyImports->max('files') ?: 1;
                            $pct = round(($d->files / $maxFiles) * 100);
                        @endphp
                        <div class="flex-1 flex flex-col items-center group h-full justify-end">
                            <div class="text-[10px] text-gray-600 opacity-0 group-hover:opacity-100 transition-opacity mb-1">{{ $d->files }}</div>
                            <div class="w-full bg-indigo-500 rounded-t-sm group-hover:bg-indigo-600 transition-all" style="height: {{ max(5, $pct) }}%;"></div>
                            <span class="text-[9px] text-gray-400 mt-1 truncate max-w-full" title="{{ $d->date_day }}">{{ date('M d', strtotime($d->date_day)) }}</span>
                        </div>
                    @empty
                        <div class="text-sm text-gray-400 text-center w-full my-auto">No import trend data recorded yet.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Performance Stats -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Queue Performance</h3>
            <ul class="space-y-4">
                <li class="flex justify-between items-center pb-3 border-b border-gray-100">
                    <span class="text-sm text-gray-600">Avg. Network Speed</span>
                    <span class="text-sm font-semibold text-indigo-700">
                        {{ $avgSpeed > 1048576 ? round($avgSpeed / 1048576, 1) . ' MB/s' : round($avgSpeed / 1024, 1) . ' KB/s' }}
                    </span>
                </li>
                <li class="flex justify-between items-center pb-3 border-b border-gray-100">
                    <span class="text-sm text-gray-600">Avg. Processing Duration</span>
                    <span class="text-sm font-semibold text-gray-800">{{ round($avgDuration) }} seconds</span>
                </li>
                <li class="flex justify-between items-center pb-3 border-b border-gray-100">
                    <span class="text-sm text-gray-600">Import Success Rate</span>
                    <span class="text-sm font-semibold text-emerald-600">
                        @php
                            $totalRuns = $completed + $failed + $cancelled;
                            $successRate = $totalRuns > 0 ? round(($completed / $totalRuns) * 100) : 100;
                        @endphp
                        {{ $successRate }}%
                    </span>
                </li>
                <li class="flex justify-between items-center pb-3 border-b border-gray-100">
                    <span class="text-sm text-gray-600">Active Queue Concurrency</span>
                    <span class="text-sm font-semibold text-gray-800">Parallel Workers Supported</span>
                </li>
            </ul>
        </div>
    </div>

    <!-- Details Leaderboard Grid -->
    <div class="grid lg:grid-cols-2 gap-8">
        <!-- Top Projects -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Top Imported Projects</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Project</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Imported Count</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($topProjects as $p)
                        <tr>
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $p->title ?: ('Project #' . $p->id) }}</td>
                            <td class="px-4 py-3 text-sm text-right text-gray-600 font-semibold">{{ $p->imported_count }} items</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="2" class="px-4 py-3 text-sm text-gray-400 text-center">No projects matched.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top Photographers -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Active Photographer Activity</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Photographer</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Total Items</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($topPhotographers as $ph)
                        <tr>
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $ph->name }}</td>
                            <td class="px-4 py-3 text-sm text-right text-gray-600 font-semibold">{{ $ph->imported_count }} items</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="2" class="px-4 py-3 text-sm text-gray-400 text-center">No photographer entries registered.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
