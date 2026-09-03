import api from './axios'

export async function getDocuments(params = {}) {
  const { data } = await api.get('/ai/documents', { params })
  return data
}

export async function uploadDocument(file) {
  const formData = new FormData()
  formData.append('file', file)
  const { data } = await api.post('/ai/documents', formData)
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
