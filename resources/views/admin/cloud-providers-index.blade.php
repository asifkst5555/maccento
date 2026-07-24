@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Cloud Provider Configurations</h1>
            <p class="mt-2 text-sm text-gray-500">Configure access credentials, test integrations, and toggle providers activation status.</p>
        </div>
        <a href="{{ route('admin.media-delivery.import-dashboard') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition">
            Back to Dashboard
        </a>
    </div>

    @if(session('success'))
    <div class="mb-6 p-4 bg-emerald-50 border-l-4 border-emerald-400 rounded-md">
        <p class="text-sm font-medium text-emerald-800">{{ session('success') }}</p>
    </div>
    @endif

    <!-- Provider Listing Grid -->
    <div class="grid lg:grid-cols-2 gap-8">
        @foreach($providers as $prov)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-900 capitalize">{{ $prov->provider === 'r2' ? 'Cloudflare R2' : ($prov->provider === 's3' ? 'Amazon S3' : $prov->provider) }}</h3>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $prov->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-800' }}">
                        {{ $prov->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>

                <form method="POST" action="{{ route('admin.media-delivery.providers.save') }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="provider" value="{{ $prov->provider }}">

                    <div class="flex items-center justify-between">
                        <label for="is_active_{{ $prov->provider }}" class="text-sm text-gray-700">Enable Provider Integration</label>
                        <input type="checkbox" name="is_active" id="is_active_{{ $prov->provider }}" value="1" {{ $prov->is_active ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Client ID</label>
                        <input type="text" name="client_id" value="{{ $prov->client_id }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 text-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Client Secret</label>
                        <input type="password" name="client_secret" value="{{ $prov->client_secret }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 text-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Access Token</label>
                        <textarea name="access_token" rows="2" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 text-sm">{{ $prov->access_token }}</textarea>
                    </div>

                    <div class="flex justify-between items-center pt-4">
                        <button type="button" onclick="testConnection('{{ $prov->provider }}')" class="inline-flex items-center px-3 py-1.5 border border-indigo-200 text-xs font-medium rounded-md text-indigo-700 bg-indigo-50 hover:bg-indigo-100 transition">
                            Test Connection
                        </button>
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 transition">
                            Save Settings
                        </button>
                    </div>
                </form>
            </div>

            <!-- Connection Status Notification Box -->
            <div id="test_status_{{ $prov->provider }}" class="mt-4 hidden p-3 rounded-lg text-xs"></div>
        </div>
        @endforeach
    </div>
</div>

<script>
function testConnection(provider) {
    const statusBox = document.getElementById('test_status_' + provider);
    statusBox.classList.remove('hidden', 'bg-emerald-50', 'text-emerald-800', 'bg-rose-50', 'text-rose-800');
    statusBox.classList.add('bg-slate-50', 'text-slate-700');
    statusBox.textContent = 'Testing connection...';
    statusBox.classList.remove('hidden');

    fetch("{{ route('admin.media-delivery.providers.test') }}", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": "{{ csrf_token() }}"
        },
        body: JSON.stringify({ provider: provider })
    })
    .then(res => res.json())
    .then(data => {
        statusBox.classList.remove('bg-slate-50', 'text-slate-700');
        if (data.success) {
            statusBox.classList.add('bg-emerald-50', 'text-emerald-800');
            statusBox.textContent = data.message;
        } else {
            statusBox.classList.add('bg-rose-50', 'text-rose-800');
            statusBox.textContent = data.message;
        }
    })
    .catch(err => {
        statusBox.classList.remove('bg-slate-50', 'text-slate-700');
        statusBox.classList.add('bg-rose-50', 'text-rose-800');
        statusBox.textContent = 'Testing error occurred: ' . err.message;
    });
}
</script>
@endsection
