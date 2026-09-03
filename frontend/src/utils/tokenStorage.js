const TOKEN_KEY = 'hireflow_auth_token'

export function getToken() {
  try {
    return localStorage.getItem(TOKEN_KEY)
  } catch {
    return null
  }
}

export function setToken(token) {
  try {
    if (!token) {
      localStorage.removeItem(TOKEN_KEY)
      return
    }
    localStorage.setItem(TOKEN_KEY, token)
  } catch {
    // Ignore storage failures in private browsing contexts.
  }
}

export function clearToken() {
  setToken(null)
}
