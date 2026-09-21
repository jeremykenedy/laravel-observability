<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { createDashboard, formatTimestamp, frameworkClasses, providerGroups, readTheme, saveTheme, statusLabel } from '../../shared/observability.js'
import '../../shared/observability.css'

const props = defineProps({
    healthUrl: { type: String, default: '/health' },
    providersUrl: { type: String, default: '/health/providers' },
    cssFramework: { type: String, default: 'bootstrap5' },
    theme: { type: String, default: 'system' },
})
const state = ref({ status: 'loading', checks: {}, providers: {}, timestamp: '', loading: true, providerError: '' })
const theme = ref(props.theme)
const classes = computed(() => frameworkClasses(props.cssFramework))
let dashboard

function connect() {
    dashboard?.destroy()
    dashboard = createDashboard(props, value => { state.value = value })
    dashboard.refresh()
}

onMounted(() => { theme.value = readTheme(props.theme); connect() })
watch(() => [props.healthUrl, props.providersUrl], connect)
onUnmounted(() => dashboard?.destroy())
</script>

<template>
    <section :class="['observability-dashboard', classes.container]" :data-theme="theme" :data-css="cssFramework" aria-label="System health" :aria-busy="state.loading">
        <header class="ob-header">
            <div><p class="ob-eyebrow">Application monitoring</p><h1>System health</h1><p class="ob-muted">Service availability and monitoring providers at a glance.</p></div>
            <div class="ob-actions">
                <label class="ob-theme">Appearance
                    <select v-model="theme" aria-label="Appearance" @change="saveTheme(theme)">
                        <option value="system">System</option><option value="light">Light</option><option value="dark">Dark</option>
                    </select>
                </label>
                <button type="button" :class="['ob-button', classes.button]" :disabled="state.loading" @click="dashboard?.refresh()">{{ state.loading ? 'Refreshing...' : 'Refresh' }}</button>
            </div>
        </header>
        <div class="ob-status" :data-status="state.status" role="status" aria-live="polite">
            <strong>{{ statusLabel(state.status) }}</strong><p class="ob-muted">Last checked: {{ formatTimestamp(state.timestamp) }}</p>
        </div>
        <h2>Health checks</h2>
        <div class="ob-grid">
            <article v-for="(check, name) in state.checks" :key="name" :class="['ob-card', classes.card]">
                <div class="ob-check-heading"><h3>{{ name }}</h3><span class="ob-badge" :data-status="check.status">{{ check.status }}</span></div>
                <p class="ob-muted">{{ check.message }}</p>
            </article>
        </div>
        <p v-if="!state.loading && !Object.keys(state.checks).length" class="ob-muted">No health checks to display.</p>
        <h2>Monitoring providers</h2>
        <p class="ob-muted" role="status">{{ state.providerError }}</p>
        <div class="ob-providers">
            <article v-for="(label, key) in providerGroups" :key="key" class="ob-card"><h3>{{ label }}</h3>
                <ul><li v-for="name in (state.providers[key]?.length ? state.providers[key] : ['None enabled'])" :key="name">{{ name }}</li></ul>
            </article>
        </div>
        <footer class="ob-footer">Checks run when this page opens and when you refresh. Queue status reports the configured driver.</footer>
    </section>
</template>
