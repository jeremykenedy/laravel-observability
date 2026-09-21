<script>
    import { onMount } from 'svelte'
    import { createDashboard, formatTimestamp, frameworkClasses, providerGroups, readTheme, saveTheme, statusLabel } from '../../shared/observability.js'
    import '../../shared/observability.css'

    export let healthUrl = '/health'
    export let providersUrl = '/health/providers'
    export let cssFramework = 'bootstrap5'
    export let theme = 'system'

    let state = { status: 'loading', checks: {}, providers: {}, timestamp: '', loading: true, providerError: '' }
    let dashboard
    let mounted = false
    $: classes = frameworkClasses(cssFramework)
    $: if (mounted) connect(healthUrl, providersUrl)

    function connect(healthUrl, providersUrl) {
        dashboard?.destroy()
        dashboard = createDashboard({ healthUrl, providersUrl }, value => { state = value })
        dashboard.refresh()
    }

    onMount(() => {
        theme = readTheme(theme)
        mounted = true
        return () => dashboard?.destroy()
    })
</script>

<section class="observability-dashboard {classes.container}" data-theme={theme} data-css={cssFramework} aria-label="System health" aria-busy={state.loading}>
    <header class="ob-header">
        <div><p class="ob-eyebrow">Application monitoring</p><h1>System health</h1><p class="ob-muted">Service availability and monitoring providers at a glance.</p></div>
        <div class="ob-actions">
            <label class="ob-theme">Appearance
                <select bind:value={theme} aria-label="Appearance" on:change={() => saveTheme(theme)}>
                    <option value="system">System</option><option value="light">Light</option><option value="dark">Dark</option>
                </select>
            </label>
            <button type="button" class="ob-button {classes.button}" disabled={state.loading} on:click={() => dashboard?.refresh()}>{state.loading ? 'Refreshing...' : 'Refresh'}</button>
        </div>
    </header>
    <div class="ob-status" data-status={state.status} role="status" aria-live="polite">
        <strong>{statusLabel(state.status)}</strong><p class="ob-muted">Last checked: {formatTimestamp(state.timestamp)}</p>
    </div>
    <h2>Health checks</h2>
    <div class="ob-grid">
        {#each Object.entries(state.checks) as [name, check] (name)}
            <article class="ob-card {classes.card}">
                <div class="ob-check-heading"><h3>{name}</h3><span class="ob-badge" data-status={check.status}>{check.status}</span></div>
                <p class="ob-muted">{check.message}</p>
            </article>
        {/each}
    </div>
    {#if !state.loading && !Object.keys(state.checks).length}<p class="ob-muted">No health checks to display.</p>{/if}
    <h2>Monitoring providers</h2>
    <p class="ob-muted" role="status">{state.providerError}</p>
    <div class="ob-providers">
        {#each Object.entries(providerGroups) as [key, label] (key)}
            <article class="ob-card"><h3>{label}</h3>
                <ul>{#each (state.providers[key]?.length ? state.providers[key] : ['None enabled']) as name (name)}<li>{name}</li>{/each}</ul>
            </article>
        {/each}
    </div>
    <footer class="ob-footer">Checks run when this page opens and when you refresh. Queue status reports the configured driver.</footer>
</section>
