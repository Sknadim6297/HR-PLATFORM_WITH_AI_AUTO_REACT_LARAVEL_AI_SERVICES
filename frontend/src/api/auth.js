import api from './axios'

export async function login(credentials) {
  const { data } = await api.post('/login', credentials, { skipAuthLogout: true })
  return data
}

export async function register(payload) {
  const { data } = await api.post('/register', payload, { skipAuthLogout: true })
  return data
}

export async function logout() {
  const { data } = await api.post('/logout')
  return data
}

export async function getCurrentUser() {
  const { data } = await api.get('/me')
  return data.user
}
