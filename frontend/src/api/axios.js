import axios from 'axios'
import { clearToken, getToken } from '../utils/tokenStorage'
import { normalizeApiError } from '../utils/apiError'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '/api',
  timeout: 30000,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
})

let onUnauthorized = null
let handlingUnauthorized = false

export function setUnauthorizedHandler(handler) {
  onUnauthorized = handler
}

api.interceptors.request.use((config) => {
  const token = getToken()
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }

  if (typeof FormData !== 'undefined' && config.data instanceof FormData) {
    if (config.headers && typeof config.headers.delete === 'function') {
      config.headers.delete('Content-Type')
    } else if (config.headers) {
      delete config.headers['Content-Type']
    }
  }

  return config
})

api.interceptors.response.use(
  (response) => response,
  async (error) => {
    const normalized = normalizeApiError(error)

    if (normalized.status === 401 && !error.config?.skipAuthLogout) {
      if (!handlingUnauthorized) {
        handlingUnauthorized = true
        clearToken()
        try {
          await onUnauthorized?.(normalized)
        } finally {
          handlingUnauthorized = false
        }
      }
    }

    error.normalized = normalized
    return Promise.reject(error)
  },
)

export default api
