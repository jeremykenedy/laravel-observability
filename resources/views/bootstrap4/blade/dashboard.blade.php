@extends('layouts.app')

@section('template_title', 'System Health')

@section('content')
<div class="container py-4" x-data="healthDashboard()" x-init="fetchHealth()">
    <h1 class="h3 mb-4">System Health</h1>

    <div class="alert mb-4" :class="status === 'healthy' ? 'alert-success' : 'alert-warning'">
        <div class="d-flex align-items-center gap-2">
            <span class="badge rounded-pill" :class="status === 'healthy' ? 'bg-success' : 'bg-warning'">&nbsp;</span>
            <strong x-text="status === 'healthy' ? 'All Systems Operational' : 'Degraded Performance'"></strong>
        </div>
        <small class="text-muted" x-text="'Last checked: ' + timestamp"></small>
    </div>

    <div class="row g-3 mb-4">
        <template x-for="(check, name) in checks" :key="name">
            <div class="col-sm-6">
                <div class="card h-100">
                    <div class="card-body py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-medium text-capitalize" x-text="name"></span>
                            <span class="badge" :class="check.status === 'ok' ? 'bg-success' : 'bg-danger'" x-text="check.status"></span>
                        </div>
                        <small class="text-muted" x-text="check.message"></small>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <h2 class="h5 mb-3">Monitoring Providers</h2>
    <div class="row g-3 mb-4">
        <div class="col-sm-4">
            <div class="card border-primary h-100"><div class="card-body">
                <h6 class="text-primary small text-uppercase">Backend</h6>
                <template x-for="p in providerData.backend" :key="p"><p class="small mb-1" x-text="p"></p></template>
                <p x-show="!providerData.backend?.length" class="text-muted small">None active</p>
            </div></div>
        </div>
        <div class="col-sm-4">
            <div class="card border-info h-100"><div class="card-body">
                <h6 class="text-info small text-uppercase">Frontend</h6>
                <template x-for="p in providerData.frontend" :key="p"><p class="small mb-1" x-text="p"></p></template>
                <p x-show="!providerData.frontend?.length" class="text-muted small">None active</p>
            </div></div>
        </div>
        <div class="col-sm-4">
            <div class="card border-warning h-100"><div class="card-body">
                <h6 class="text-warning small text-uppercase">Testing / Uptime</h6>
                <template x-for="p in [...(providerData.testing||[]), ...(providerData.uptime||[])]" :key="p"><p class="small mb-1" x-text="p"></p></template>
                <p x-show="!providerData.testing?.length && !providerData.uptime?.length" class="text-muted small">None active</p>
            </div></div>
        </div>
    </div>
    <div class="text-center"><button @click="fetchHealth()" class="btn btn-link btn-sm">Refresh</button></div>
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
                this.status = health.status; this.checks = health.checks;
                this.timestamp = new Date(health.timestamp).toLocaleString();
                this.providerData = providers;
            } catch (e) { this.status = 'error'; }
        }
    };
}
</script>
@endsection
@endsection
