import api from './axios'

export async function getDocuments(params = {}) {
  const { data } = await api.get('/ai/documents', { params })
  return data
}

export async function getDocument(id) {
  const { data } = await api.get(`/ai/documents/${id}`)
  return data
}

export async function uploadDocument(file, { onUploadProgress } = {}) {
  const formData = new FormData()
  formData.append('file', file)
  const { data } = await api.post('/ai/documents', formData, {
    onUploadProgress,
  })
  return data
}

/**
 * Application show already embeds resume_analysis + job_match when loaded.
 * There is no separate resume-analysis or job-match GET route.
 */
export async function getApplicationAiData(applicationId) {
  const { data } = await api.get(`/applications/${applicationId}`)
  return data
}

/** POST /applications/{id}/ai-screen — HR/Admin only. */
export async function runAiScreening(applicationId) {
  const { data } = await api.post(`/applications/${applicationId}/ai-screen`)
  return data
}

export async function searchDocuments(payload) {
  const { data } = await api.post('/ai/search', payload)
  return data
}

export async function askRag(payload) {
  const { data } = await api.post('/ai/ask', payload)
  return data
}

export async function runWorkflow(payload) {
  const { data } = await api.post('/ai/workflow', payload)
  return data
}

export async function getWorkflow(id) {
  const { data } = await api.get(`/ai/workflow/${id}`)
  return data
}
