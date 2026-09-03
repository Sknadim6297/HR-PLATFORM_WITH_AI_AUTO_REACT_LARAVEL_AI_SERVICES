import api from './axios'

export async function getApplications(params = {}) {
  const { data } = await api.get('/applications', { params })
  return data
}

export async function getApplication(id) {
  const { data } = await api.get(`/applications/${id}`)
  return data
}

export async function getJobApplications(jobId, params = {}) {
  const { data } = await api.get(`/jobs/${jobId}/applications`, { params })
  return data
}

export async function applyToJob(jobId, payload) {
  const { data } = await api.post(`/jobs/${jobId}/applications`, payload)
  return data
}

export async function updateApplicationStatus(id, status) {
  const { data } = await api.patch(`/applications/${id}/status`, { status })
  return data
}

export async function aiScreenApplication(id) {
  const { data } = await api.post(`/applications/${id}/ai-screen`)
  return data
}
