import {
  useCallback,
  useEffect,
  useMemo,
  useState,
} from 'react'
import * as authApi from '../api/auth'
import { setUnauthorizedHandler } from '../api/axios'
import { ROLE_HOME } from '../constants/roles'
import { clearToken, getToken, setToken } from '../utils/tokenStorage'
import { AuthContext } from './auth-context'

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null)
  const [bootstrapping, setBootstrapping] = useState(true)
  const [authBusy, setAuthBusy] = useState(false)

  const clearSession = useCallback(() => {
    clearToken()
    setUser(null)
  }, [])

  const refreshUser = useCallback(async () => {
    const token = getToken()
    if (!token) {
      setUser(null)
      return null
    }

    const nextUser = await authApi.getCurrentUser()
    setUser(nextUser)
    return nextUser
  }, [])

  useEffect(() => {
    setUnauthorizedHandler(() => {
      clearSession()
    })

    let cancelled = false

    async function bootstrap() {
      const token = getToken()
      if (!token) {
        if (!cancelled) {
          setBootstrapping(false)
        }
        return
      }

      try {
        const nextUser = await authApi.getCurrentUser()
        if (!cancelled) {
          setUser(nextUser)
        }
      } catch {
        clearToken()
        if (!cancelled) {
          setUser(null)
        }
      } finally {
        if (!cancelled) {
          setBootstrapping(false)
        }
      }
    }

    void bootstrap()

    return () => {
      cancelled = true
      setUnauthorizedHandler(null)
    }
  }, [clearSession])

  const login = useCallback(async (credentials) => {
    setAuthBusy(true)
    try {
      const data = await authApi.login(credentials)
      setToken(data.token)
      setUser(data.user)
      return data.user
    } finally {
      setAuthBusy(false)
    }
  }, [])

  const register = useCallback(async (payload) => {
    setAuthBusy(true)
    try {
      const data = await authApi.register(payload)
      setToken(data.token)
      setUser(data.user)
      return data.user
    } finally {
      setAuthBusy(false)
    }
  }, [])

  const logout = useCallback(async () => {
    setAuthBusy(true)
    try {
      try {
        await authApi.logout()
      } catch {
        // Local session is cleared even if the API call fails.
      }
      clearSession()
    } finally {
      setAuthBusy(false)
    }
  }, [clearSession])

  const homePath = user?.role ? ROLE_HOME[user.role] || '/login' : '/login'

  const value = useMemo(
    () => ({
      user,
      bootstrapping,
      authBusy,
      isAuthenticated: Boolean(user),
      homePath,
      login,
      register,
      logout,
      refreshUser,
      clearSession,
    }),
    [user, bootstrapping, authBusy, homePath, login, register, logout, refreshUser, clearSession],
  )

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}
