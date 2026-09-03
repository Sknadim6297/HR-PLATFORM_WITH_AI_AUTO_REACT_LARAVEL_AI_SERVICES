import api from './axios'

export async function getProfile() {
  const { data } = await api.get('/candidate/profile')
  return data
}

export async function updateProfile(payload) {
  const { data } = await api.put('/candidate/profile', payload)
  return data
}

export async function getCandidateProfile(id) {
  const { data } = await api.get(`/candidate/profiles/${id}`)
  return data
}
