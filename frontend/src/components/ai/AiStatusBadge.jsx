import { aiUiStateLabel } from '../../constants/ai'

const TONE = {
  no_resume: 'neutral',
  pending: 'neutral',
  processing: 'info',
  processing_match: 'info',
  completed: 'success',
  failed: 'warning',
}

export function AiStatusBadge({ state }) {
  const label = aiUiStateLabel(state)
  return (
    <span className={`badge badge--${TONE[state] || 'neutral'}`} aria-label={`AI status: ${label}`}>
      {label}
    </span>
  )
}
