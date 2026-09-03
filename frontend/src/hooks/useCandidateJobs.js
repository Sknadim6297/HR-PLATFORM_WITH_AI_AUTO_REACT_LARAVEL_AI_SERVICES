import { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import { useSearchParams } from 'react-router-dom'
import * as jobsApi from '../api/jobs'

/**
 * Candidate browse uses GET /jobs (backend returns published only).
 * Search/department/employment filters are applied client-side because
 * JobService ignores those params for candidate role.
 */
export function useCandidateJobs() {
  const [searchParams, setSearchParams] = useSearchParams()
  const page = Number(searchParams.get('page') || 1)
  const search = searchParams.get('search') || ''
  const department = searchParams.get('department') || ''
  const employmentType = searchParams.get('employment_type') || ''
  const perPage = 12

  const [allJobs, setAllJobs] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [searchInput, setSearchInput] = useState(search)
  const [departmentInput, setDepartmentInput] = useState(department)
  const requestId = useRef(0)

  useEffect(() => {
    const handle = window.setTimeout(() => {
      const next = searchInput.trim()
      if (next === search) return
      const params = new URLSearchParams(searchParams)
      if (next) params.set('search', next)
      else params.delete('search')
      params.set('page', '1')
      setSearchParams(params)
    }, 400)
    return () => window.clearTimeout(handle)
  }, [searchInput, search, searchParams, setSearchParams])

  useEffect(() => {
    const handle = window.setTimeout(() => {
      const next = departmentInput.trim()
      if (next === department) return
      const params = new URLSearchParams(searchParams)
      if (next) params.set('department', next)
      else params.delete('department')
      params.set('page', '1')
      setSearchParams(params)
    }, 400)
    return () => window.clearTimeout(handle)
  }, [departmentInput, department, searchParams, setSearchParams])

  useEffect(() => {
    let cancelled = false
    const id = ++requestId.current

    async function run() {
      try {
        // Pull a larger page so client filters remain useful for typical catalogs.
        const response = await jobsApi.getJobs({ per_page: 100, page: 1 })
        if (cancelled || id !== requestId.current) return
        setAllJobs(response.data || [])
        setError('')
      } catch (err) {
        if (cancelled || id !== requestId.current) return
        setAllJobs([])
        setError(err.normalized?.message || 'Unable to load jobs.')
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
  }, [])

  const filtered = useMemo(() => {
    const q = search.trim().toLowerCase()
    return allJobs.filter((job) => {
      if (employmentType && job.employment_type !== employmentType) return false
      if (department && !(job.department || '').toLowerCase().includes(department.toLowerCase())) return false
      if (!q) return true
      const haystack = `${job.title || ''} ${job.department || ''} ${job.location || ''} ${job.description || ''}`.toLowerCase()
      return haystack.includes(q)
    })
  }, [allJobs, search, department, employmentType])

  const meta = useMemo(() => {
    const total = filtered.length
    const lastPage = Math.max(1, Math.ceil(total / perPage))
    const current = Math.min(page, lastPage)
    const from = total === 0 ? 0 : (current - 1) * perPage + 1
    const to = Math.min(current * perPage, total)
    return {
      current_page: current,
      last_page: lastPage,
      per_page: perPage,
      total,
      from,
      to,
    }
  }, [filtered.length, page, perPage])

  const jobs = useMemo(() => {
    const start = (meta.current_page - 1) * perPage
    return filtered.slice(start, start + perPage)
  }, [filtered, meta.current_page, perPage])

  function setPage(nextPage) {
    const params = new URLSearchParams(searchParams)
    params.set('page', String(nextPage))
    setSearchParams(params)
  }

  function setFilter(key, value) {
    const params = new URLSearchParams(searchParams)
    if (!value) params.delete(key)
    else params.set(key, value)
    params.set('page', '1')
    setSearchParams(params)
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
      const response = await jobsApi.getJobs({ per_page: 100, page: 1 })
      if (id !== requestId.current) return
      setAllJobs(response.data || [])
      setError('')
    } catch (err) {
      if (id !== requestId.current) return
      setError(err.normalized?.message || 'Unable to load jobs.')
    } finally {
      if (id === requestId.current) setLoading(false)
    }
  }, [])

  return {
    jobs,
    meta,
    loading,
    error,
    filters: {
      search,
      department,
      employment_type: employmentType,
      page: meta.current_page,
    },
    searchInput,
    setSearchInput,
    departmentInput,
    setDepartmentInput,
    setPage,
    setFilter,
    clearFilters,
    refresh,
  }
}
