import { useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import * as jobsApi from '../../api/jobs'
import { JobStatusBadge } from '../../components/jobs/JobStatusBadge'
import { Button } from '../../components/ui/Button'
import { Card } from '../../components/ui/Card'
import { ConfirmDialog } from '../../components/ui/Modal'
import { ErrorState } from '../../components/ui/ErrorState'
import { Spinner } from '../../components/ui/Spinner'
import {
  employmentTypeLabel,
  formatJobDate,
  formatSalaryRange,
} from '../../constants/jobs'
import { useJob } from '../../hooks/useJob'
import { useToast } from '../../hooks/useToast'

function DetailBlock({ label, children }) {
  if (!children) return null
  return (
    <div className="job-detail__block">
      <h3>{label}</h3>
      <p className="job-detail__prose">{children}</p>
    </div>
  )
}

export function JobDetailsPage({ basePath }) {
  const { id } = useParams()
  const navigate = useNavigate()
  const toast = useToast()
  const { job, setJob, loading, error, status, refresh } = useJob(id)
  const [confirm, setConfirm] = useState(null)
  const [busy, setBusy] = useState(false)

  async function runAction() {
    if (!confirm || !job) return
    setBusy(true)
    try {
      if (confirm === 'publish') {
        const response = await jobsApi.publishJob(job.id)
        setJob(response.data)
        toast.success('Job published.')
      } else if (confirm === 'close') {
        const response = await jobsApi.closeJob(job.id)
        setJob(response.data)
        toast.success('Job closed.')
      } else if (confirm === 'delete') {
        await jobsApi.deleteJob(job.id)
        toast.success('Job deleted.')
        navigate(`${basePath}/jobs`)
        return
      }
      setConfirm(null)
    } catch (err) {
      toast.error(err.normalized?.message || 'That action did not work.')
    } finally {
      setBusy(false)
    }
  }

  if (loading) {
    return (
      <div className="page">
        <Spinner label="Loading job…" />
      </div>
    )
  }

  if (error || !job) {
    return (
      <div className="page">
        <ErrorState
          title={status === 404 ? 'Job not found' : 'Could not open job'}
          description={error || 'Job not found.'}
          onRetry={refresh}
        />
        <Link to={`${basePath}/jobs`}>Back to jobs</Link>
      </div>
    )
  }

  const salary = formatSalaryRange(job.salary_min, job.salary_max)
  const experience =
    job.experience_min != null || job.experience_max != null
      ? `${job.experience_min ?? 0}–${job.experience_max ?? '∞'} years`
      : null

  const confirmCopy = {
    publish: {
      title: 'Publish this job?',
      description: 'Candidates will be able to see and apply to this posting.',
      confirmLabel: 'Publish',
    },
    close: {
      title: 'Close this job?',
      description: 'The posting will stop accepting new applications.',
      confirmLabel: 'Close job',
    },
    delete: {
      title: 'Delete this job?',
      description: 'This removes the job posting permanently from the list.',
      confirmLabel: 'Delete',
    },
  }[confirm] || {}

  return (
    <div className="page">
      <header className="page__header">
        <div>
          <p className="eyebrow">Job details</p>
          <h1>{job.title}</h1>
          <p className="muted">
            {[job.department, job.location, employmentTypeLabel(job.employment_type)]
              .filter(Boolean)
              .join(' · ')}
          </p>
        </div>
        <JobStatusBadge status={job.status} />
      </header>

      <div className="job-detail__toolbar">
        <Button type="button" variant="secondary" onClick={() => navigate(`${basePath}/jobs/${job.id}/edit`)}>
          Edit
        </Button>
        {job.status !== 'published' ? (
          <Button type="button" disabled={busy} onClick={() => setConfirm('publish')}>
            Publish
          </Button>
        ) : (
          <Button type="button" variant="secondary" disabled={busy} onClick={() => setConfirm('close')}>
            Close
          </Button>
        )}
        <Button type="button" variant="ghost" disabled={busy} onClick={() => setConfirm('delete')}>
          Delete
        </Button>
        <Link className="text-link" to={`${basePath}/jobs`}>
          Back to list
        </Link>
      </div>

      <div className="job-detail__grid">
        <Card title="About the role">
          <DetailBlock label="Description">{job.description}</DetailBlock>
          <DetailBlock label="Requirements">{job.requirements}</DetailBlock>
          <DetailBlock label="Responsibilities">{job.responsibilities}</DetailBlock>
        </Card>

        <Card title="Details">
          <dl className="job-meta">
            <div>
              <dt>Employment type</dt>
              <dd>{employmentTypeLabel(job.employment_type)}</dd>
            </div>
            <div>
              <dt>Department</dt>
              <dd>{job.department || '—'}</dd>
            </div>
            <div>
              <dt>Location</dt>
              <dd>{job.location || '—'}</dd>
            </div>
            <div>
              <dt>Salary</dt>
              <dd>{salary || '—'}</dd>
            </div>
            <div>
              <dt>Experience</dt>
              <dd>{experience || '—'}</dd>
            </div>
            <div>
              <dt>Published</dt>
              <dd>{formatJobDate(job.published_at)}</dd>
            </div>
            <div>
              <dt>Closing</dt>
              <dd>{formatJobDate(job.closing_at)}</dd>
            </div>
            <div>
              <dt>Created</dt>
              <dd>{formatJobDate(job.created_at)}</dd>
            </div>
            <div>
              <dt>Updated</dt>
              <dd>{formatJobDate(job.updated_at)}</dd>
            </div>
            <div>
              <dt>Posted by</dt>
              <dd>{job.created_by?.name || '—'}</dd>
            </div>
          </dl>
        </Card>
      </div>

      <ConfirmDialog
        open={Boolean(confirm)}
        title={confirmCopy.title}
        description={confirmCopy.description}
        confirmLabel={confirmCopy.confirmLabel}
        loading={busy}
        onClose={() => (busy ? null : setConfirm(null))}
        onConfirm={runAction}
      />
    </div>
  )
}
