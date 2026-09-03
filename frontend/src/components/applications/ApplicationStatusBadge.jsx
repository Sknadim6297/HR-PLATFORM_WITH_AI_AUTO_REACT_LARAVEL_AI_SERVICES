import { applicationStatusLabel } from '../../constants/applications'

const TONE = {
  applied: 'info',
  screening: 'neutral',
  shortlisted: 'success',
  interview: 'info',
  selected: 'success',
  rejected: 'warning',
  withdrawn: 'neutral',
}

export function ApplicationStatusBadge({ status }) {
  const label = applicationStatusLabel(status)
  return (
    <span className={`badge badge--${TONE[status] || 'neutral'}`} aria-label={`Status: ${label}`}>
      {label}
    </span>
  )
}
