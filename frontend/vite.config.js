import react from '@vitejs/plugin-react'
import { defineConfig } from 'vite'

// https://vite.dev/config/
export default defineConfig({
  plugins: [react()],
  server: {
    proxy: {
      // Avoid CORS in local dev by proxying API calls through Vite.
      // XAMPP serves Laravel from /ai-hr-platform/backend/public
      '/api': {
        target: 'http://localhost',
        changeOrigin: true,
        secure: false,
        rewrite: (path) => `/ai-hr-platform/backend/public${path}`,
      },
    },
  },
})
