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

export { runAiScreening as aiScreenApplication } from './ai'

/** Aliases matching product language — same endpoints. */
export const getMyApplications = getApplications
export const getMyApplication = getApplication
export const getHRApplications = getApplications
export const getHRApplication = getApplication
export const getStaffApplications = getApplications
export const getStaffApplication = getApplication