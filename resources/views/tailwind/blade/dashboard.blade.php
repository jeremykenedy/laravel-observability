@extends('layouts.app')

@section('template_title', 'System Health')

@section('content')
<div class="max-w-4xl mx-auto" x-data="healthDashboard()" x-init="fetchHealth()">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">System Health</h1>

    {{-- Overall Status --}}
    <div class="mb-6 p-4 rounded-lg border" :class="status === 'healthy' ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800' : 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800'">
        <div class="flex items-center gap-3">
            <span class="h-4 w-4 rounded-full" :class="status === 'healthy' ? 'bg-green-500' : 'bg-yellow-500'"></span>
            <span class="text-lg font-semibold text-gray-900 dark:text-gray-100" x-text="status === 'healthy' ? 'All Systems Operational' : 'Degraded Performance'"></span>
        </div>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400" x-text="'Last checked: ' + timestamp"></p>
    </div>

    {{-- Health Checks --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
        <template x-for="(check, name) in checks" :key="name">
            <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100 capitalize" x-text="name"></span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium" :class="check.status === 'ok' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'" x-text="check.status"></span>
                </div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-text="check.message"></p>
            </div>
        </template>
    </div>

    {{-- Active Providers --}}
    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Monitoring Providers</h2>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-8">
        <div class="p-3 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800">
            <p class="text-xs font-medium text-blue-600 dark:text-blue-400 uppercase mb-1">Backend</p>
            <template x-for="p in providerData.backend" :key="p"><p class="text-sm text-gray-700 dark:text-gray-300" x-text="p"></p></template>
            <p x-show="!providerData.backend?.length" class="text-sm text-gray-400">None active</p>
        </div>
        <div class="p-3 rounded-lg bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800">
            <p class="text-xs font-medium text-purple-600 dark:text-purple-400 uppercase mb-1">Frontend</p>
            <template x-for="p in providerData.frontend" :key="p"><p class="text-sm text-gray-700 dark:text-gray-300" x-text="p"></p></template>
            <p x-show="!providerData.frontend?.length" class="text-sm text-gray-400">None active</p>
        </div>
        <div class="p-3 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800">
            <p class="text-xs font-medium text-amber-600 dark:text-amber-400 uppercase mb-1">Testing / Uptime</p>
            <template x-for="p in [...(providerData.testing||[]), ...(providerData.uptime||[])]" :key="p"><p class="text-sm text-gray-700 dark:text-gray-300" x-text="p"></p></template>
            <p x-show="!providerData.testing?.length && !providerData.uptime?.length" class="text-sm text-gray-400">None active</p>
        </div>
    </div>

    <div class="text-center">
        <button @click="fetchHealth()" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">Refresh</button>
    </div>
</div>

@section('footer_scripts')
<script>
function healthDashboard() {
    return {
        status: 'loading', checks: {}, timestamp: '', providerData: {},
        async fetchHealth() {
            const h = { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content };
            try {
                const [health, providers] = await Promise.all([
                    fetch('/health', { headers: h }).then(r => r.json()),
                    fetch('/health/providers', { headers: h }).then(r => r.json()),
                ]);
                this.status = health.status;
                this.checks = health.checks;
                this.timestamp = new Date(health.timestamp).toLocaleString();
                this.providerData = providers;
            } catch (e) { this.status = 'error'; }
        }
    };
}
</script>
@endsection
@endsection
