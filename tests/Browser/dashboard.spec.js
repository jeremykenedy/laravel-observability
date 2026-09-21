import { test, expect } from '@playwright/test'
import AxeBuilder from '@axe-core/playwright'

const health = { status: 'healthy', checks: { database: { status: 'ok', message: 'Database connection successful' }, cache: { status: 'ok', message: 'Cache working' }, storage: { status: 'ok', message: 'Storage writable' }, queue: { status: 'ok', message: 'Queue driver: sync' } }, timestamp: '2026-09-21T12:00:00Z' }
const providers = { backend: ['sentry'], frontend: ['logrocket'], testing: [], uptime: ['uptimerobot'] }

for (const frontend of ['blade', 'vue', 'react', 'svelte']) {
    for (const css of ['tailwind', 'bootstrap5', 'bootstrap4']) {
        test(`${frontend} ${css} renders, refreshes, and persists dark mode`, async ({ page }) => {
            const errors = []
            page.on('pageerror', error => errors.push(error.message))
            let status = 'healthy'
            await page.route('**/status/check', route => route.fulfill({ status: status === 'degraded' ? 503 : 200, json: { ...health, status } }))
            await page.route('**/health/providers', route => route.fulfill({ json: providers }))
            const url = frontend === 'blade' ? `http://127.0.0.1:8173/health/dashboard?css=${css}` : `/?frontend=${frontend}&css=${css}`
            await page.goto(url)
            await expect(page.getByText('All systems operational')).toBeVisible()
            await expect(page.getByText('sentry', { exact: true })).toBeVisible()
            await expect(page.getByText('Storage writable')).toBeVisible()
            await page.getByLabel('Appearance', { exact: true }).selectOption('light')
            const lightAccessibility = await new AxeBuilder({ page }).include('.observability-dashboard').analyze()
            expect(lightAccessibility.violations).toEqual([])
            await page.getByLabel('Appearance', { exact: true }).selectOption('dark')
            await expect(page.locator('.observability-dashboard')).toHaveAttribute('data-theme', 'dark')
            const accessibility = await new AxeBuilder({ page }).include('.observability-dashboard').analyze()
            expect(accessibility.violations).toEqual([])
            await page.reload()
            await expect(page.locator('.observability-dashboard')).toHaveAttribute('data-theme', 'dark')
            await expect(page.getByRole('button', { name: 'Refresh', exact: true })).toBeEnabled()
            status = 'degraded'
            await page.getByRole('button', { name: 'Refresh', exact: true }).click()
            await expect(page.getByText('Some checks need attention')).toBeVisible()
            await page.setViewportSize({ width: 375, height: 812 })
            expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true)
            expect(errors).toEqual([])
        })
    }
}

for (const frontend of ['blade', 'react', 'vue', 'svelte']) {
    test(`${frontend} reports a failed refresh and can retry`, async ({ page }) => {
        await page.route('**/status/check', route => route.abort())
        await page.route('**/health/providers', route => route.fulfill({ json: providers }))
        await page.goto(frontend === 'blade' ? 'http://127.0.0.1:8173/health/dashboard' : `/?frontend=${frontend}`)
        await expect(page.getByText('Unable to refresh health')).toBeVisible()
        await page.unroute('**/status/check')
        await page.route('**/status/check', route => route.fulfill({ json: health }))
        await page.getByRole('button', { name: 'Refresh', exact: true }).click()
        await expect(page.getByText('All systems operational')).toBeVisible()
    })
}

for (const css of ['tailwind', 'bootstrap5', 'bootstrap4']) {
    test(`Livewire ${css} renders and refreshes`, async ({ page }) => {
        await page.goto(`http://127.0.0.1:8173/livewire-dashboard?css=${css}`)
        await expect(page.getByText('All systems operational')).toBeVisible()
        await page.getByLabel('Appearance', { exact: true }).selectOption('dark')
        const refresh = page.waitForResponse(response => response.url().includes('/livewire') && response.request().method() === 'POST')
        await page.getByRole('button', { name: 'Refresh', exact: true }).click()
        expect((await refresh).ok()).toBe(true)
        await expect(page.locator('.observability-dashboard')).toHaveAttribute('data-theme', 'dark')
        await expect(page.getByText('All systems operational')).toBeVisible()
    })
}
