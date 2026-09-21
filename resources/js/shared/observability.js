export const providerGroups = { backend: 'Backend', frontend: 'Frontend', testing: 'Testing', uptime: 'Uptime' }

export function statusLabel(status) {
    return { loading: 'Checking system health', healthy: 'All systems operational', degraded: 'Some checks need attention', error: 'Unable to refresh health' }[status] || 'Status unavailable'
}

export function formatTimestamp(value) {
    const date = new Date(value)
    return value && !Number.isNaN(date.getTime()) ? date.toLocaleString() : 'Not checked yet'
}

export function frameworkClasses(css = 'bootstrap5') {
    if (css === 'tailwind') return { container: 'max-w-5xl mx-auto', button: 'rounded-lg border px-4 py-2 font-medium', card: 'rounded-xl border p-5' }
    return { container: 'container', button: 'btn btn-outline-secondary', card: 'card card-body' }
}

export function readTheme(fallback = 'system') {
    try {
        const saved = localStorage.getItem('observability-theme')
        if (['system', 'light', 'dark'].includes(saved)) return saved
    } catch {}
    return ['system', 'light', 'dark'].includes(fallback) ? fallback : 'system'
}

export function saveTheme(theme) {
    try { localStorage.setItem('observability-theme', theme) } catch {}
    return theme
}

function validHealth(data) {
    return data && ['healthy', 'degraded'].includes(data.status)
        && data.checks && typeof data.checks === 'object' && (!Array.isArray(data.checks) || data.checks.length === 0)
        && Object.values(data.checks).every(check => check && typeof check.status === 'string' && typeof check.message === 'string')
        && typeof data.timestamp === 'string'
}

export function createDashboard({ healthUrl = '/health', providersUrl = '/health/providers' } = {}, onChange) {
    let state = { status: 'loading', checks: {}, timestamp: '', providers: {}, loading: false, providerError: '' }
    let controller
    let disposed = false

    function emit(values) {
        state = { ...state, ...values }
        if (!disposed) onChange(state)
    }

    async function refresh() {
        if (disposed || state.loading) return
        controller = new AbortController()
        const timeout = setTimeout(() => controller.abort(), 15000)
        emit({ loading: true, providerError: '' })

        try {
            const options = { headers: { Accept: 'application/json' }, credentials: 'same-origin', signal: controller.signal }
            const [health, providers] = await Promise.allSettled([
                fetch(healthUrl, options).then(async response => {
                    const data = await response.json()
                    if ((!response.ok && response.status !== 503) || !validHealth(data)) throw new Error('Invalid health response')
                    return data
                }),
                fetch(providersUrl, options).then(async response => {
                    const data = await response.json()
                    if (!response.ok || !data || !Object.keys(providerGroups).every(key => Array.isArray(data[key]) && data[key].every(value => typeof value === 'string'))) throw new Error('Invalid provider response')
                    return data
                }),
            ])

            if (health.status !== 'fulfilled') {
                emit({ status: 'error', loading: false, providers: providers.status === 'fulfilled' ? providers.value : {}, providerError: providers.status === 'rejected' ? 'Provider status is unavailable.' : '' })
                return
            }

            emit({ ...health.value, providers: providers.status === 'fulfilled' ? providers.value : {}, loading: false,
                providerError: providers.status === 'rejected' ? 'Provider status is unavailable.' : '' })
        } finally {
            clearTimeout(timeout)
        }
    }

    return { refresh, destroy() { disposed = true; controller?.abort() } }
}
