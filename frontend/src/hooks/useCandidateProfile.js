import { useCallback, useEffect, useState } from 'react'
import * as candidatesApi from '../api/candidates'

export function useCandidateProfile() {
  const [profile, setProfile] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [missing, setMissing] = useState(false)

  const load = useCallback(async () => {
    try {
      const response = await candidatesApi.getProfile()
      setProfile(response.data)
      setMissing(false)
      setError('')
    } catch (err) {
      if (err.normalized?.status === 404) {
        setProfile(null)
        setMissing(true)
        setError('')
      } else {
        setProfile(null)
        setMissing(false)
        setError(err.normalized?.message || 'Unable to load your profile.')
      }
    } finally {
      setLoading(false)
    }
  }, [])

  useEffect(() => {
    let cancelled = false

    async function run() {
      try {
        const response = await candidatesApi.getProfile()
        if (cancelled) return
        setProfile(response.data)
        setMissing(false)
        setError('')
      } catch (err) {
        if (cancelled) return
        if (err.normalized?.status === 404) {
          setProfile(null)
          setMissing(true)
          setError('')
        } else {
          setProfile(null)
          setMissing(false)
          setError(err.normalized?.message || 'Unable to load your profile.')
        }
      } finally {
        if (!cancelled) setLoading(false)
      }
    }

    const tick = window.setTimeout(() => {
      if (!cancelled) void run()
    }, 0)

    return () => {
      cancelled = true
      window.clearTimeout(tick)
    }
  }, [])

  return { profile, setProfile, loading, error, missing, refresh: load }
}
