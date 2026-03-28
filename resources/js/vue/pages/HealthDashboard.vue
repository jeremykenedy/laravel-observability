<script setup>
import { ref, onMounted } from 'vue'

const status = ref('loading')
const checks = ref({})
const timestamp = ref('')
const providers = ref({ backend: [], frontend: [], testing: [], uptime: [] })

async function fetchHealth() {
    const h = { Accept: 'application/json' }
    const [health, prov] = await Promise.all([
        fetch('/health', { headers: h }).then(r => r.json()),
        fetch('/health/providers', { headers: h }).then(r => r.json()),
    ])
    status.value = health.status
    checks.value = health.checks
    timestamp.value = new Date(health.timestamp).toLocaleString()
    providers.value = prov
}

onMounted(fetchHealth)
</script>

<template>
    <div>
        <h1 class="h3 mb-4">System Health</h1>
        <div class="alert" :class="status === 'healthy' ? 'alert-success' : 'alert-warning'">
            <strong>{{ status === 'healthy' ? 'All Systems Operational' : 'Degraded' }}</strong>
            <br><small class="text-muted">{{ timestamp }}</small>
        </div>
        <div class="row g-3 mb-4">
            <div v-for="(check, name) in checks" :key="name" class="col-sm-6">
                <div class="card"><div class="card-body py-2">
                    <div class="d-flex justify-content-between">
                        <span class="fw-medium text-capitalize">{{ name }}</span>
                        <span class="badge" :class="check.status === 'ok' ? 'bg-success' : 'bg-danger'">{{ check.status }}</span>
                    </div>
                    <small class="text-muted">{{ check.message }}</small>
                </div></div>
            </div>
        </div>
        <button @click="fetchHealth" class="btn btn-sm btn-outline-secondary">Refresh</button>
    </div>
</template>
