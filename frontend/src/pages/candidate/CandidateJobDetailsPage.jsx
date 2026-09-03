import { useEffect, useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import * as applicationsApi from '../../api/applications'
import { ApplyJobModal } from '../../components/applications/ApplyJobModal'
import { Button } from '../../components/ui/Button'
import { Card } from '../../components/ui/Card'
import { ErrorState } from '../../components/ui/ErrorState'
import { Spinner } from '../../components/ui/Spinner'
import {
  employmentTypeLabel,
  formatJobDate,
  formatSalaryRange,
} from '../../constants/jobs'
import { useJob } from '../../hooks/useJob'

function DetailBlock({ label, children }) {
  if (!children) return null
  return (
    <div className="job-detail__block">
      <h3>{label}</h3>
      <p className="job-detail__prose">{children}</p>
    </div>
  )
}

export default function CandidateJobDetailsPage() {
  const { id } = useParams()
  const { job, loading, error, status, refresh } = useJob(id)
  const [applyOpen, setApplyOpen] = useState(false)
  const [existingApp, setExistingApp] = useState(null)
  const [checkingApply, setCheckingApply] = useState(true)

  useEffect(() => {
    if (!job?.id) return undefined
    let cancelled = false

    async function check() {
      try {
        const response = await applicationsApi.getApplications({
          job_id: job.id,
          per_page: 1,
        })
        if (cancelled) return
        setExistingApp(response.data?.[0] || null)
      } catch {
        if (!cancelled) setExistingApp(null)
      } finally {
        if (!cancelled) setCheckingApply(false)
      }
    }

    const tick = window.setTimeout(() => {
      if (!cancelled) {
        setCheckingApply(true)
        void check()
      }
    }, 0)

    return () => {
      cancelled = true
      window.clearTimeout(tick)
    }
  }, [job?.id])

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
        <Link to="/candidate/jobs">Back to jobs</Link>
      </div>
    )
  }

  const salary = formatSalaryRange(job.salary_min, job.salary_max)
  const experience =
    job.experience_min != null || job.experience_max != null
      ? `${job.experience_min ?? 0}–${job.experience_max ?? '∞'} years`
      : null

  const canApply = job.status === 'published' && !existingApp

  return (
    <div className="page">
      <header className="page__header">
        <div>
          <p className="eyebrow">Open role</p>
          <h1>{job.title}</h1>
          <p className="muted">
            {[job.department, job.location, employmentTypeLabel(job.employment_type)]
              .filter(Boolean)
              .join(' · ')}
          </p>
        </div>
      </header>

      <div className="job-detail__toolbar">
        {checkingApply ? (
          <Spinner label="Checking application…" />
        ) : existingApp ? (
          <>
            <Button type="button" variant="secondary" disabled>
              Already Applied
            </Button>
            <Link className="text-link" to={`/candidate/applications/${existingApp.id}`}>
              View your application
            </Link>
          </>
        ) : canApply ? (
          <Button type="button" onClick={() => setApplyOpen(true)}>
            Apply Now
          </Button>
        ) : (
          <Button type="button" variant="secondary" disabled>
            Not accepting applications
          </Button>
        )}
        <Link className="text-link" to="/candidate/jobs">
          Back to jobs
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
              <dt>Posted</dt>
              <dd>{formatJobDate(job.published_at || job.created_at)}</dd>
            </div>
            <div>
              <dt>Closing</dt>
              <dd>{formatJobDate(job.closing_at)}</dd>
            </div>
          </dl>
        </Card>
      </div>

      <ApplyJobModal open={applyOpen} job={job} onClose={() => setApplyOpen(false)} />
    </div>
  )
}
