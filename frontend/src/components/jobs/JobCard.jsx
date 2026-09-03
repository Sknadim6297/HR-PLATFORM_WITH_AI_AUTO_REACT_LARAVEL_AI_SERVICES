import { Link } from 'react-router-dom'
import { employmentTypeLabel, formatJobDate } from '../../constants/jobs'
import { JobActions } from './JobActions'
import { JobStatusBadge } from './JobStatusBadge'

export function JobCard({ job, basePath, busy, onPublish, onClose, onDelete }) {
  return (
    <article className="job-card">
      <div className="job-card__top">
        <div>
          <Link to={`${basePath}/jobs/${job.id}`} className="job-card__title">
            {job.title}
          </Link>
          <p className="muted">
            {[job.department, job.location, employmentTypeLabel(job.employment_type)]
              .filter(Boolean)
              .join(' · ') || 'No extras listed'}
          </p>
        </div>
        <JobStatusBadge status={job.status} />
      </div>
      <p className="muted">Created {formatJobDate(job.created_at)}</p>
      <JobActions
        job={job}
        basePath={basePath}
        busy={busy}
        onPublish={onPublish}
        onClose={onClose}
        onDelete={onDelete}
        compact
      />
    </article>
  )
}
