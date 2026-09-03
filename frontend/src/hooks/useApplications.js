import { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import { useSearchParams } from 'react-router-dom'
import * as applicationsApi from '../api/applications'

function readFilters(searchParams) {
  return {
    page: Number(searchParams.get('page') || 1),
    search: searchParams.get('search') || '',
    status: searchParams.get('status') || '',
    job_id: searchParams.get('job_id') || '',
    from: searchParams.get('from') || '',
    to: searchParams.get('to') || '',
    min_score: searchParams.get('min_score') || '',
    max_score: searchParams.get('max_score') || '',
    per_page: Number(searchParams.get('per_page') || 15),
  }
}

export function useApplications() {
  const [searchParams, setSearchParams] = useSearchParams()
  const filters = useMemo(() => readFilters(searchParams), [searchParams])
  const [applications, setApplications] = useState([])
  const [meta, setMeta] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [searchInput, setSearchInput] = useState(filters.search)
  const requestId = useRef(0)

  useEffect(() => {
    const handle = window.setTimeout(() => {
      const next = searchInput.trim()
      if (next === filters.search) return
      const params = new URLSearchParams(searchParams)
      if (next) params.set('search', next)
      else params.delete('search')
      params.set('page', '1')
      setSearchParams(params)
    }, 400)
    return () => window.clearTimeout(handle)
  }, [searchInput, filters.search, searchParams, setSearchParams])

  useEffect(() => {
    let cancelled = false
    const id = ++requestId.current

    async function run() {
      try {
        const params = {
          page: filters.page,
          per_page: filters.per_page,
        }
        if (filters.search) params.search = filters.search
        if (filters.status) params.status = filters.status
        if (filters.job_id) params.job_id = filters.job_id
        if (filters.from) params.from = filters.from
        if (filters.to) params.to = filters.to
        if (filters.min_score) params.min_score = filters.min_score
        if (filters.max_score) params.max_score = filters.max_score

        const response = await applicationsApi.getApplications(params)
        if (cancelled || id !== requestId.current) return
        setApplications(response.data || [])
        setMeta(response.meta || null)
        setError('')
      } catch (err) {
        if (cancelled || id !== requestId.current) return
        setApplications([])
        setMeta(null)
        setError(err.normalized?.message || 'Unable to load applications.')
      } finally {
        if (!cancelled && id === requestId.current) setLoading(false)
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
  }, [filters])

  function patchParams(updates, { resetPage = false } = {}) {
    const params = new URLSearchParams(searchParams)
    Object.entries(updates).forEach(([key, value]) => {
      if (value === '' || value == null) params.delete(key)
      else params.set(key, String(value))
    })
    if (resetPage) params.set('page', '1')
    setSearchParams(params)
  }

  const refresh = useCallback(async () => {
    const id = ++requestId.current
    setLoading(true)
    try {
      const params = { page: filters.page, per_page: filters.per_page }
      if (filters.search) params.search = filters.search
      if (filters.status) params.status = filters.status
      if (filters.job_id) params.job_id = filters.job_id
      if (filters.from) params.from = filters.from
      if (filters.to) params.to = filters.to
      const response = await applicationsApi.getApplications(params)
      if (id !== requestId.current) return
      setApplications(response.data || [])
      setMeta(response.meta || null)
      setError('')
    } catch (err) {
      if (id !== requestId.current) return
      setError(err.normalized?.message || 'Unable to load applications.')
    } finally {
      if (id === requestId.current) setLoading(false)
    }
  }, [filters])

  return {
    applications,
    meta,
    loading,
    error,
    filters,
    searchInput,
    setSearchInput,
    setPage: (page) => patchParams({ page }),
    setFilter: (key, value) => patchParams({ [key]: value }, { resetPage: true }),
    clearFilters: () => {
      setSearchInput('')
      setSearchParams({})
    },
    refresh,
    replaceLocally: (updated) => {
      setApplications((current) => current.map((item) => (item.id === updated.id ? updated : item)))
    },
  }
}
