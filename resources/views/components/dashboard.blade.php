@props(['css' => 'tailwind', 'healthData' => [], 'providerData' => [], 'livewire' => false])
<link rel="stylesheet" href="{{ route('health.assets', ['asset' => 'observability.css']) }}">
<script type="module" src="{{ route('health.assets', ['asset' => 'blade.js']) }}"></script>
<section {{ $attributes->merge(['class' => 'observability-dashboard '.($css === 'tailwind' ? 'max-w-5xl mx-auto' : 'container')]) }}
    @if($livewire) wire:ignore.self @endif data-observability data-css="{{ $css }}" data-theme="{{ config('observability.theme', 'system') }}"
    data-livewire="{{ $livewire ? 'true' : 'false' }}" data-health-url="{{ \Illuminate\Support\Facades\Route::has('health') ? route('health') : url(config('observability.health.route', '/health')) }}" data-providers-url="{{ \Illuminate\Support\Facades\Route::has('health.providers') ? route('health.providers') : url('/health/providers') }}" aria-label="System health">
    <header class="ob-header">
        <div>
            <p class="ob-eyebrow">Application monitoring</p>
            <h1>System health</h1>
            <p class="ob-muted">Service availability and monitoring providers at a glance.</p>
        </div>
        <div class="ob-actions">
            <label class="ob-theme" @if($livewire) wire:ignore @endif>Appearance
                <select data-theme-select aria-label="Appearance">
                    <option value="system">System</option>
                    <option value="light">Light</option>
                    <option value="dark">Dark</option>
                </select>
            </label>
            <button type="button" class="ob-button {{ $css === 'tailwind' ? 'rounded-lg border px-4 py-2 font-medium' : 'btn btn-outline-secondary' }}" data-refresh
                @if($livewire) wire:click="refresh" wire:loading.attr="disabled" @endif>Refresh</button>
        </div>
    </header>
    <div class="ob-status" data-status="{{ $healthData['status'] ?? 'loading' }}" role="status" aria-live="polite">
        <strong data-status-label>{{ ($healthData['status'] ?? '') === 'healthy' ? 'All systems operational' : (isset($healthData['status']) ? 'Some checks need attention' : 'Checking system health') }}</strong>
        <p class="ob-muted">Last checked: <span data-timestamp>{{ isset($healthData['timestamp']) ? \Illuminate\Support\Carbon::parse($healthData['timestamp'])->toDayDateTimeString() : 'Not checked yet' }}</span></p>
    </div>
    <h2>Health checks</h2>
    <div class="ob-grid" data-checks>
        @foreach($healthData['checks'] ?? [] as $name => $check)
            <article class="ob-card {{ $css === 'tailwind' ? 'rounded-xl border p-5' : 'card card-body' }}">
                <div class="ob-check-heading"><h3>{{ $name }}</h3><span class="ob-badge" data-status="{{ $check['status'] }}">{{ $check['status'] }}</span></div>
                <p class="ob-muted">{{ $check['message'] }}</p>
            </article>
        @endforeach
    </div>
    <p class="ob-muted" data-empty @if(!empty($healthData['checks']) || !isset($healthData['status'])) hidden @endif>No health checks to display.</p>
    <template data-check-template>
        <article class="ob-card {{ $css === 'tailwind' ? 'rounded-xl border p-5' : 'card card-body' }}">
            <div class="ob-check-heading"><h3 data-check-name></h3><span class="ob-badge" data-check-status></span></div>
            <p class="ob-muted" data-check-message></p>
        </article>
    </template>
    <h2>Monitoring providers</h2>
    <p class="ob-muted" data-provider-error role="status"></p>
    <div class="ob-providers">
        @foreach(['backend' => 'Backend', 'frontend' => 'Frontend', 'testing' => 'Testing', 'uptime' => 'Uptime'] as $type => $label)
            <article class="ob-card">
                <h3>{{ $label }}</h3>
                <ul data-providers="{{ $type }}">
                    @forelse($providerData[$type] ?? [] as $provider)
                        <li>{{ $provider }}</li>
                    @empty
                        <li>None enabled</li>
                    @endforelse
                </ul>
            </article>
        @endforeach
    </div>
    <footer class="ob-footer">Checks run when this page opens and when you refresh. Queue status reports the configured driver.</footer>
</section>
