import { jobStatusLabel } from '../../constants/jobs'

const TONE = {
  draft: 'neutral',
  published: 'success',
  closed: 'warning',
  archived: 'info',
}

export function JobStatusBadge({ status }) {
  const label = jobStatusLabel(status)
  return (
    <span className={`badge badge--${TONE[status] || 'neutral'}`} aria-label={`Status: ${label}`}>
      {label}
    </span>
  )
}
