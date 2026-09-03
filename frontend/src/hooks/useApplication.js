import { useCallback, useEffect, useState } from 'react'
import * as applicationsApi from '../api/applications'

export function useApplication(id) {
  const [application, setApplication] = useState(null)
  const [loading, setLoading] = useState(Boolean(id))
  const [error, setError] = useState('')
  const [status, setStatus] = useState(null)

  const load = useCallback(async () => {
    if (!id) return
    try {
      const response = await applicationsApi.getApplication(id)
      setApplication(response.data)
      setError('')
      setStatus(null)
    } catch (err) {
      setApplication(null)
      setStatus(err.normalized?.status ?? null)
      setError(
        err.normalized?.status === 404
          ? 'Application not found.'
          : err.normalized?.message || 'Unable to load this application.',
      )
    } finally {
      setLoading(false)
    }
  }, [id])

  useEffect(() => {
    if (!id) return undefined
    let cancelled = false

    async function run() {
      try {
        const response = await applicationsApi.getApplication(id)
        if (cancelled) return
        setApplication(response.data)
        setError('')
        setStatus(null)
      } catch (err) {
        if (cancelled) return
        setApplication(null)
        setStatus(err.normalized?.status ?? null)
        setError(
          err.normalized?.status === 404
            ? 'Application not found.'
            : err.normalized?.message || 'Unable to load this application.',
        )
      } finally {
        if (!cancelled) setLoading(false)
      }
    }

    const tick = window.setTimeout(() => {
      if (!cancelled) {
        setLoading(true)
        void run()
      }
    }, 0)

    return () => {
      cancelled = true
      window.clearTimeout(tick)
    }
  }, [id])

  return { application, setApplication, loading, error, status, refresh: load }
}
