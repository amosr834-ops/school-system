import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

const devApiTarget = process.env.VITE_DEV_API_TARGET || 'http://localhost:8080'

// https://vite.dev/config/
export default defineConfig({
  plugins: [react()],
  server: {
    proxy: {
      '/api': {
        target: devApiTarget,
        changeOrigin: true,
      },
    },
  },
})
