import api from './axios'

export async function getNotifications(params = {}) {
  const { data } = await api.get('/notifications', { params })
  return data
}
