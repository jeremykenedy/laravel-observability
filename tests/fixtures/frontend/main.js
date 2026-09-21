import React from 'react'
import { createRoot } from 'react-dom/client'
import { createApp } from 'vue'
import { mount } from 'svelte'
import ReactDashboard from '../../../resources/js/react/pages/HealthDashboard.jsx'
import VueDashboard from '../../../resources/js/vue/pages/HealthDashboard.vue'
import SvelteDashboard from '../../../resources/js/svelte/pages/HealthDashboard.svelte'

const query = new URLSearchParams(location.search)
const frontend = query.get('frontend') || 'react'
const props = { cssFramework: query.get('css') || 'tailwind', healthUrl: '/status/check', providersUrl: '/health/providers' }
if (props.cssFramework === 'bootstrap5') await import('bootstrap/dist/css/bootstrap.min.css')
if (props.cssFramework === 'bootstrap4') await import('bootstrap4/dist/css/bootstrap.min.css')
if (props.cssFramework === 'tailwind') await import('./tailwind.css')
const target = document.querySelector('#app')
if (frontend === 'react') createRoot(target).render(React.createElement(ReactDashboard, props))
if (frontend === 'vue') createApp(VueDashboard, props).mount(target)
if (frontend === 'svelte') mount(SvelteDashboard, { target, props })
