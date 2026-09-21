import { defineConfig } from '@playwright/test'

export default defineConfig({
    testDir: './tests/Browser',
    testMatch: '**/*.spec.js',
    fullyParallel: true,
    workers: process.env.CI ? 2 : 4,
    retries: 0,
    use: { baseURL: 'http://127.0.0.1:5173', trace: 'retain-on-failure', screenshot: 'only-on-failure' },
    webServer: [
        { command: 'npm run dev', url: 'http://127.0.0.1:5173', reuseExistingServer: !process.env.CI },
        { command: 'php -S 127.0.0.1:8173 tests/Browser/server.php', url: 'http://127.0.0.1:8173/status/check', reuseExistingServer: !process.env.CI },
    ],
})
