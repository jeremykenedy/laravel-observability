import { createDashboard, formatTimestamp, providerGroups, readTheme, saveTheme, statusLabel } from './observability.js'

function setText(root, selector, value) {
    root.querySelector(selector).textContent = value
}

function render(root, state) {
    root.setAttribute('aria-busy', String(state.loading))
    root.querySelector('[data-refresh]').disabled = state.loading
    setText(root, '[data-refresh]', state.loading ? 'Refreshing...' : 'Refresh')
    root.querySelector('[data-status]').dataset.status = state.status
    setText(root, '[data-status-label]', statusLabel(state.status))
    setText(root, '[data-timestamp]', formatTimestamp(state.timestamp))
    setText(root, '[data-provider-error]', state.providerError)
    const checks = root.querySelector('[data-checks]')
    checks.replaceChildren()
    for (const [name, check] of Object.entries(state.checks)) {
        const card = root.querySelector('[data-check-template]').content.cloneNode(true)
        setText(card, '[data-check-name]', name)
        setText(card, '[data-check-message]', check.message)
        const badge = card.querySelector('[data-check-status]')
        badge.textContent = check.status
        badge.dataset.status = check.status
        checks.append(card)
    }
    root.querySelector('[data-empty]').hidden = Object.keys(state.checks).length > 0 || state.status === 'loading'
    for (const key of Object.keys(providerGroups)) {
        const list = root.querySelector(`[data-providers="${key}"]`)
        list.replaceChildren()
        for (const name of state.providers[key]?.length ? state.providers[key] : ['None enabled']) {
            const item = document.createElement('li')
            item.textContent = name
            list.append(item)
        }
    }
}

function mount(root) {
    const select = root.querySelector('[data-theme-select]')
    select.value = readTheme(root.dataset.theme)
    root.dataset.theme = select.value
    select.addEventListener('change', () => { root.dataset.theme = saveTheme(select.value) })
    if (root.dataset.livewire === 'true') return
    const dashboard = createDashboard({ healthUrl: root.dataset.healthUrl, providersUrl: root.dataset.providersUrl }, state => render(root, state))
    root.querySelector('[data-refresh]').addEventListener('click', () => dashboard.refresh())
    dashboard.refresh()
    window.addEventListener('pagehide', event => { if (!event.persisted) dashboard.destroy() })
}

for (const root of document.querySelectorAll('[data-observability]')) mount(root)
