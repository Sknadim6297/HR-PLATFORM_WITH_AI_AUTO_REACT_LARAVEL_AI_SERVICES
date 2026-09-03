import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import * as jobsApi from '../../api/jobs'
import { JobFilters } from '../../components/jobs/JobFilters'
import { JobList } from '../../components/jobs/JobList'
import { Button } from '../../components/ui/Button'
import { ConfirmDialog } from '../../components/ui/Modal'
import { ErrorState } from '../../components/ui/ErrorState'
import { Pagination } from '../../components/ui/Pagination'
import { Spinner } from '../../components/ui/Spinner'
import { useJobs } from '../../hooks/useJobs'
import { useToast } from '../../hooks/useToast'

export function JobsIndexPage({ basePath, title, subtitle }) {
  const navigate = useNavigate()
  const toast = useToast()
  const {
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
  } = useJobs()

  const [confirm, setConfirm] = useState(null)
  const [busyId, setBusyId] = useState(null)

  function openConfirm(type, job) {
    setConfirm({ type, job })
  }

  async function runConfirmedAction() {
    if (!confirm?.job) return
    const { type, job } = confirm
    setBusyId(job.id)

    try {
      if (type === 'publish') {
        const response = await jobsApi.publishJob(job.id)
        replaceJobLocally(response.data)
        toast.success('Job published.')
      } else if (type === 'close') {
        const response = await jobsApi.closeJob(job.id)
        replaceJobLocally(response.data)
        toast.success('Job closed.')
      } else if (type === 'delete') {
        await jobsApi.deleteJob(job.id)
        removeJobLocally(job.id)
        toast.success('Job deleted.')
      }
      setConfirm(null)
    } catch (err) {
      toast.error(err.normalized?.message || 'That action did not work.')
    } finally {
      setBusyId(null)
    }
  }

  const confirmCopy = {
    publish: {
      title: 'Publish this job?',
      description: 'Candidates will be able to see and apply to this posting.',
      confirmLabel: 'Publish',
    },
    close: {
      title: 'Close this job?',
      description: 'The posting will stop accepting new applications from candidates.',
      confirmLabel: 'Close job',
    },
    delete: {
      title: 'Delete this job?',
      description: 'This removes the job posting. This cannot be undone from the UI.',
      confirmLabel: 'Delete',
    },
  }[confirm?.type] || {}

  return (
    <div className="page">
      <header className="page__header">
        <div>
          <p className="eyebrow">Jobs</p>
          <h1>{title}</h1>
          <p>{subtitle}</p>
        </div>
        <Button type="button" onClick={() => navigate(`${basePath}/jobs/create`)}>
          Create job
        </Button>
      </header>

      <JobFilters
        searchInput={searchInput}
        onSearchChange={setSearchInput}
        departmentInput={departmentInput}
        onDepartmentChange={setDepartmentInput}
        filters={filters}
        onFilterChange={setFilter}
        onClear={clearFilters}
      />

      {loading ? <Spinner label="Loading jobs…" /> : null}
      {error ? <ErrorState description={error} onRetry={refresh} /> : null}

      {!loading && !error ? (
        <>
          <JobList
            jobs={jobs}
            basePath={basePath}
            busyId={busyId}
            onPublish={(job) => openConfirm('publish', job)}
            onClose={(job) => openConfirm('close', job)}
            onDelete={(job) => openConfirm('delete', job)}
            onCreate={() => navigate(`${basePath}/jobs/create`)}
          />
          <Pagination meta={meta} onPageChange={setPage} />
        </>
      ) : null}

      <ConfirmDialog
        open={Boolean(confirm)}
        title={confirmCopy.title}
        description={confirmCopy.description}
        confirmLabel={confirmCopy.confirmLabel}
        loading={Boolean(busyId)}
        onClose={() => (busyId ? null : setConfirm(null))}
        onConfirm={runConfirmedAction}
      />

      <p className="muted">
        <Link to={basePath}>Back to overview</Link>
      </p>
    </div>
  )
}
