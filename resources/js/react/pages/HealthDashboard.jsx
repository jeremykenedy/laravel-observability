import { useState, useEffect } from 'react'

export default function HealthDashboard() {
    const [status, setStatus] = useState('loading')
    const [checks, setChecks] = useState({})
    const [timestamp, setTimestamp] = useState('')
    const [providers, setProviders] = useState({ backend: [], frontend: [], testing: [], uptime: [] })

    async function fetchHealth() {
        const h = { Accept: 'application/json' }
        const [health, prov] = await Promise.all([
            fetch('/health', { headers: h }).then(r => r.json()),
            fetch('/health/providers', { headers: h }).then(r => r.json()),
        ])
        setStatus(health.status)
        setChecks(health.checks)
        setTimestamp(new Date(health.timestamp).toLocaleString())
        setProviders(prov)
    }

    useEffect(() => { fetchHealth() }, [])

    return (
        <div>
            <h1 className="h3 mb-4">System Health</h1>
            <div className={`alert ${status === 'healthy' ? 'alert-success' : 'alert-warning'}`}>
                <strong>{status === 'healthy' ? 'All Systems Operational' : 'Degraded'}</strong>
                <br /><small className="text-muted">{timestamp}</small>
            </div>
            <div className="row g-3 mb-4">
                {Object.entries(checks).map(([name, check]) => (
                    <div key={name} className="col-sm-6">
                        <div className="card"><div className="card-body py-2">
                            <div className="d-flex justify-content-between">
                                <span className="fw-medium text-capitalize">{name}</span>
                                <span className={`badge ${check.status === 'ok' ? 'bg-success' : 'bg-danger'}`}>{check.status}</span>
                            </div>
                            <small className="text-muted">{check.message}</small>
                        </div></div>
                    </div>
                ))}
            </div>
            <button onClick={fetchHealth} className="btn btn-sm btn-outline-secondary">Refresh</button>
        </div>
    )
}
