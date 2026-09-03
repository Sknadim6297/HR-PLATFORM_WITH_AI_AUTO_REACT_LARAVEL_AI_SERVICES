import { Link } from 'react-router-dom'
import { employmentTypeLabel, formatJobDate } from '../../constants/jobs'
import { Button } from '../ui/Button'

export function CandidateJobCard({ job }) {
  return (
    <article className="job-card">
      <div className="job-card__top">
        <div>
          <Link to={`/candidate/jobs/${job.id}`} className="job-card__title">
            {job.title}
          </Link>
          <p className="muted">
            {[job.department, job.location, employmentTypeLabel(job.employment_type)]
              .filter(Boolean)
              .join(' · ')}
          </p>
        </div>
      </div>
      <p className="muted">Posted {formatJobDate(job.published_at || job.created_at)}</p>
      <Link to={`/candidate/jobs/${job.id}`}>
        <Button type="button" variant="secondary">View role</Button>
      </Link>
    </article>
  )
}

export function CandidateJobList({ jobs, onBrowseClear }) {
  if (!jobs.length) {
    return (
      <div className="state-panel">
        <h3>No jobs are currently available</h3>
        <p>Try clearing filters, or check back soon for new openings.</p>
        {onBrowseClear ? (
          <Button type="button" variant="ghost" onClick={onBrowseClear}>
            Clear filters
          </Button>
        ) : null}
      </div>
    )
  }

  return (
    <div className="candidate-job-grid">
      {jobs.map((job) => (
        <CandidateJobCard key={job.id} job={job} />
      ))}
    </div>
  )
}
