import api from './axios'

export async function getJobs(params = {}) {
  const { data } = await api.get('/jobs', { params })
  return data
}

export async function getJob(id) {
  const { data } = await api.get(`/jobs/${id}`)
  return data
}

export async function createJob(payload) {
  const { data } = await api.post('/jobs', payload)
  return data
}

export async function updateJob(id, payload) {
  const { data } = await api.put(`/jobs/${id}`, payload)
  return data
}

export async function deleteJob(id) {
  const { data } = await api.delete(`/jobs/${id}`)
  return data
}

export async function publishJob(id) {
  const { data } = await api.post(`/jobs/${id}/publish`)
  return data
}

export async function closeJob(id) {
  const { data } = await api.post(`/jobs/${id}/close`)
  return data
}
