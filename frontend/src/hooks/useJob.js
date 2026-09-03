import { useCallback, useEffect, useState } from 'react'
import * as jobsApi from '../api/jobs'

export function useJob(id) {
  const [job, setJob] = useState(null)
  const [loading, setLoading] = useState(Boolean(id))
  const [error, setError] = useState('')
  const [status, setStatus] = useState(null)

  const load = useCallback(async () => {
    if (!id) return

    try {
      const response = await jobsApi.getJob(id)
      setJob(response.data)
      setError('')
      setStatus(null)
    } catch (err) {
      setJob(null)
      setStatus(err.normalized?.status ?? null)
      setError(
        err.normalized?.status === 404
          ? 'Job not found.'
          : err.normalized?.status === 403
            ? "You don't have permission to access this job."
            : err.normalized?.message || 'Unable to load this job.',
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
        const response = await jobsApi.getJob(id)
        if (cancelled) return
        setJob(response.data)
        setError('')
        setStatus(null)
      } catch (err) {
        if (cancelled) return
        setJob(null)
        setStatus(err.normalized?.status ?? null)
        setError(
          err.normalized?.status === 404
            ? 'Job not found.'
            : err.normalized?.status === 403
              ? "You don't have permission to access this job."
              : err.normalized?.message || 'Unable to load this job.',
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

  return { job, setJob, loading, error, status, refresh: load }
}
