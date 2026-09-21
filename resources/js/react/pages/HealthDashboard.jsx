import { useEffect, useState } from 'react'
import { createDashboard, formatTimestamp, frameworkClasses, providerGroups, readTheme, saveTheme, statusLabel } from '../../shared/observability.js'
import '../../shared/observability.css'

export default function HealthDashboard({ healthUrl = '/health', providersUrl = '/health/providers', cssFramework = 'bootstrap5', theme: defaultTheme = 'system' }) {
    const [state, setState] = useState({ status: 'loading', checks: {}, providers: {}, timestamp: '', loading: true, providerError: '' })
    const [dashboard, setDashboard] = useState(null)
    const [theme, setTheme] = useState(defaultTheme)
    const classes = frameworkClasses(cssFramework)

    useEffect(() => {
        setTheme(readTheme(defaultTheme))
        const client = createDashboard({ healthUrl, providersUrl }, setState)
        setDashboard(client)
        client.refresh()
        return () => client.destroy()
    }, [healthUrl, providersUrl, defaultTheme])

    return (
        <section className={`observability-dashboard ${classes.container}`} data-theme={theme} data-css={cssFramework} aria-label="System health" aria-busy={state.loading}>
            <header className="ob-header">
                <div><p className="ob-eyebrow">Application monitoring</p><h1>System health</h1><p className="ob-muted">Service availability and monitoring providers at a glance.</p></div>
                <div className="ob-actions">
                    <label className="ob-theme">Appearance
                        <select aria-label="Appearance" value={theme} onChange={event => setTheme(saveTheme(event.target.value))}>
                            <option value="system">System</option><option value="light">Light</option><option value="dark">Dark</option>
                        </select>
                    </label>
                    <button type="button" className={`ob-button ${classes.button}`} disabled={state.loading} onClick={() => dashboard?.refresh()}>{state.loading ? 'Refreshing...' : 'Refresh'}</button>
                </div>
            </header>
            <div className="ob-status" data-status={state.status} role="status" aria-live="polite">
                <strong>{statusLabel(state.status)}</strong><p className="ob-muted">Last checked: {formatTimestamp(state.timestamp)}</p>
            </div>
            <h2>Health checks</h2>
            <div className="ob-grid">
                {Object.entries(state.checks).map(([name, check]) => (
                    <article className={`ob-card ${classes.card}`} key={name}>
                        <div className="ob-check-heading"><h3>{name}</h3><span className="ob-badge" data-status={check.status}>{check.status}</span></div>
                        <p className="ob-muted">{check.message}</p>
                    </article>
                ))}
            </div>
            {!state.loading && !Object.keys(state.checks).length && <p className="ob-muted">No health checks to display.</p>}
            <h2>Monitoring providers</h2>
            <p className="ob-muted" role="status">{state.providerError}</p>
            <div className="ob-providers">
                {Object.entries(providerGroups).map(([key, label]) => (
                    <article className="ob-card" key={key}><h3>{label}</h3>
                        <ul>{(state.providers[key]?.length ? state.providers[key] : ['None enabled']).map(name => <li key={name}>{name}</li>)}</ul>
                    </article>
                ))}
            </div>
            <footer className="ob-footer">Checks run when this page opens and when you refresh. Queue status reports the configured driver.</footer>
        </section>
    )
}
