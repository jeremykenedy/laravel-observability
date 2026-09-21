import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import react from '@vitejs/plugin-react'
import tailwindcss from '@tailwindcss/vite'
import { svelte } from '@sveltejs/vite-plugin-svelte'

export default defineConfig({
    root: 'tests/fixtures/frontend',
    plugins: [vue(), react(), svelte(), tailwindcss()],
    server: { port: 5173, strictPort: true, fs: { allow: ['.'] } },
    build: { outDir: '../../Browser/build', emptyOutDir: true },
})
