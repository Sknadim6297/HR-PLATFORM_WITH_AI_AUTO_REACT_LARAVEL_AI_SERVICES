import { Alert } from '../ui/Alert'
import { Button } from '../ui/Button'

export function AiProcessingState({
  title = 'AI analysis is being processed.',
  description = 'Results will appear here when the backend finishes. This page refreshes automatically while processing.',
  onRefresh,
}) {
  return (
    <div className="ai-state-card" aria-live="polite">
      <Alert tone="info">{title}</Alert>
      <p className="muted">{description}</p>
      {onRefresh ? (
        <Button type="button" variant="ghost" onClick={onRefresh}>
          Refresh now
        </Button>
      ) : null}
    </div>
  )
}

export function AiErrorState({
  title = 'AI processing failed.',
  description = 'Please try again later.',
  onRetry,
}) {
  return (
    <div className="ai-state-card" role="alert">
      <Alert tone="error">{title}</Alert>
      <p className="muted">{description}</p>
      {onRetry ? (
        <Button type="button" variant="secondary" onClick={onRetry}>
          Refresh
        </Button>
      ) : null}
    </div>
  )
}
