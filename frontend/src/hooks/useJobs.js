import { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import { useSearchParams } from 'react-router-dom'
import * as jobsApi from '../api/jobs'

function readFilters(searchParams) {
  return {
    page: Number(searchParams.get('page') || 1),
    search: searchParams.get('search') || '',
    status: searchParams.get('status') || '',
    department: searchParams.get('department') || '',
    employment_type: searchParams.get('employment_type') || '',
    per_page: Number(searchParams.get('per_page') || 15),
  }
}

export function useJobs() {
  const [searchParams, setSearchParams] = useSearchParams()
  const filters = useMemo(() => readFilters(searchParams), [searchParams])
  const [jobs, setJobs] = useState([])
  const [meta, setMeta] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [searchInput, setSearchInput] = useState(filters.search)
  const [departmentInput, setDepartmentInput] = useState(filters.department)
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
    const handle = window.setTimeout(() => {
      const next = departmentInput.trim()
      if (next === filters.department) return
      const params = new URLSearchParams(searchParams)
      if (next) params.set('department', next)
      else params.delete('department')
      params.set('page', '1')
      setSearchParams(params)
    }, 400)
    return () => window.clearTimeout(handle)
  }, [departmentInput, filters.department, searchParams, setSearchParams])

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
        if (filters.department) params.department = filters.department
        if (filters.employment_type) params.employment_type = filters.employment_type

        const response = await jobsApi.getJobs(params)
        if (cancelled || id !== requestId.current) return

        setJobs(response.data || [])
        setMeta(response.meta || null)
        setError('')
      } catch (err) {
        if (cancelled || id !== requestId.current) return
        setJobs([])
        setMeta(null)
        setError(err.normalized?.message || 'Unable to load jobs. Please try again.')
      } finally {
        if (!cancelled && id === requestId.current) {
          setLoading(false)
        }
      }
    }

    // Defer loading flag so the effect body stays sync-setState free for the linter.
    const tick = window.setTimeout(() => {
      if (!cancelled) setLoading(true)
      void run()
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

  function setPage(page) {
    patchParams({ page })
  }

  function setFilter(key, value) {
    patchParams({ [key]: value }, { resetPage: true })
  }

  function clearFilters() {
    setSearchInput('')
    setDepartmentInput('')
    setSearchParams({})
  }

  const refresh = useCallback(async () => {
    const id = ++requestId.current
    setLoading(true)
    try {
      const params = {
        page: filters.page,
        per_page: filters.per_page,
      }
      if (filters.search) params.search = filters.search
      if (filters.status) params.status = filters.status
      if (filters.department) params.department = filters.department
      if (filters.employment_type) params.employment_type = filters.employment_type

      const response = await jobsApi.getJobs(params)
      if (id !== requestId.current) return
      setJobs(response.data || [])
      setMeta(response.meta || null)
      setError('')
    } catch (err) {
      if (id !== requestId.current) return
      setError(err.normalized?.message || 'Unable to load jobs. Please try again.')
    } finally {
      if (id === requestId.current) setLoading(false)
    }
  }, [filters])

  function removeJobLocally(id) {
    setJobs((current) => current.filter((job) => job.id !== id))
  }

  function replaceJobLocally(updated) {
    setJobs((current) => current.map((job) => (job.id === updated.id ? updated : job)))
  }

  return {
    jobs,
    meta,
    loading,
    error,
    filters,
    searchInput,
    setSearchInput,
    departmentInput,
    setDepartmentInput,
    setPage,
    setFilter,
    clearFilters,
    refresh,
    removeJobLocally,
    replaceJobLocally,
  }
}
