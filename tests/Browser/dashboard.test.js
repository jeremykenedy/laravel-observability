import { afterEach, test } from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { compile } from 'svelte4/compiler'
import { createDashboard, formatTimestamp, statusLabel } from '../../resources/js/shared/observability.js'

const originalFetch = globalThis.fetch
const health = { status: 'healthy', checks: { cache: { status: 'ok', message: 'Cache working' } }, timestamp: '2026-09-21T12:00:00Z' }
const providers = { backend: ['sentry'], frontend: [], testing: [], uptime: [] }
afterEach(() => { globalThis.fetch = originalFetch })

function respond(value, status = 200) { return { ok: status === 200, status, json: async () => value } }

test('treats a valid 503 as a degraded health result', async () => {
    globalThis.fetch = async url => url === '/custom' ? respond({ ...health, status: 'degraded' }, 503) : respond(providers)
    let state
    await createDashboard({ healthUrl: '/custom' }, value => { state = value }).refresh()
    assert.equal(state.status, 'degraded')
    assert.deepEqual(state.providers, providers)
})

test('retains health when only provider loading fails', async () => {
    globalThis.fetch = async url => url === '/health' ? respond(health) : respond({}, 403)
    let state
    await createDashboard({}, value => { state = value }).refresh()
    assert.equal(state.status, 'healthy')
    assert.equal(state.providerError, 'Provider status is unavailable.')
})

test('reports network and invalid health responses without stale success', async () => {
    let state
    const client = createDashboard({}, value => { state = value })
    globalThis.fetch = async url => respond(url === '/health' ? health : providers)
    await client.refresh()
    globalThis.fetch = async () => { throw new Error('Network failure') }
    await client.refresh()
    assert.equal(state.status, 'error')
    assert.equal(state.loading, false)
    globalThis.fetch = async () => respond({ status: 'healthy', checks: 'invalid' })
    await client.refresh()
    assert.equal(state.status, 'error')
})

test('cancels requests on disposal and ignores late results', async () => {
    let calls = 0
    let signal
    globalThis.fetch = async (url, options) => {
        signal = options.signal
        return new Promise((resolve, reject) => { signal.addEventListener('abort', () => reject(new Error('Aborted'))) })
    }
    const client = createDashboard({}, () => { calls++ })
    const request = client.refresh()
    client.destroy()
    assert.equal(signal.aborted, true)
    await request
    await client.refresh()
    assert.equal(calls, 1)
})

test('does not start duplicate refresh requests', async () => {
    let requests = 0
    globalThis.fetch = async url => { requests++; return respond(url === '/health' ? health : providers) }
    const client = createDashboard({}, () => {})
    await Promise.all([client.refresh(), client.refresh()])
    assert.equal(requests, 2)
})

test('uses explicit labels for loading and unavailable states', () => {
    assert.equal(statusLabel('loading'), 'Checking system health')
    assert.equal(statusLabel('error'), 'Unable to refresh health')
    assert.equal(formatTimestamp('invalid'), 'Not checked yet')
})

test('keeps the Svelte component compatible with Svelte 4', () => {
    const result = compile(readFileSync('resources/js/svelte/pages/HealthDashboard.svelte', 'utf8'), { filename: 'HealthDashboard.svelte', generate: 'dom' })
    assert.ok(result.js.code.length > 0)
    assert.deepEqual(result.warnings, [])
})
