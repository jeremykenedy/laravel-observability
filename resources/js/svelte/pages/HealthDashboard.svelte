<script>
    import { onMount } from 'svelte'

    let status = 'loading'
    let checks = {}
    let timestamp = ''
    let providers = { backend: [], frontend: [], testing: [], uptime: [] }

    async function fetchHealth() {
        const h = { Accept: 'application/json' }
        const [health, prov] = await Promise.all([
            fetch('/health', { headers: h }).then(r => r.json()),
            fetch('/health/providers', { headers: h }).then(r => r.json()),
        ])
        status = health.status
        checks = health.checks
        timestamp = new Date(health.timestamp).toLocaleString()
        providers = prov
    }

    onMount(fetchHealth)
</script>

<div>
    <h1 class="h3 mb-4">System Health</h1>
    <div class="alert {status === 'healthy' ? 'alert-success' : 'alert-warning'}">
        <strong>{status === 'healthy' ? 'All Systems Operational' : 'Degraded'}</strong>
        <br><small class="text-muted">{timestamp}</small>
    </div>
    <div class="row g-3 mb-4">
        {#each Object.entries(checks) as [name, check]}
            <div class="col-sm-6">
                <div class="card"><div class="card-body py-2">
                    <div class="d-flex justify-content-between">
                        <span class="fw-medium text-capitalize">{name}</span>
                        <span class="badge {check.status === 'ok' ? 'bg-success' : 'bg-danger'}">{check.status}</span>
                    </div>
                    <small class="text-muted">{check.message}</small>
                </div></div>
            </div>
        {/each}
    </div>
    <button on:click={fetchHealth} class="btn btn-sm btn-outline-secondary">Refresh</button>
</div>
