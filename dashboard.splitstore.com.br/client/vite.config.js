import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig({
  plugins: [react()],
  base: '/',
  build: {
    outDir: '../public_html',
    emptyOutDir: true,
  },
  server: {
    port: 3000,
    host: '0.0.0.0'
  }
})
