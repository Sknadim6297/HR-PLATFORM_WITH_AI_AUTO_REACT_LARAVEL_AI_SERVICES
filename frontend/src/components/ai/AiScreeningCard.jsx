import { useState } from 'react'
import { screeningRecommendationLabel } from '../../constants/ai'
import { AiScore } from './AiScore'
import { Alert } from '../ui/Alert'
import { Button } from '../ui/Button'
import { Card } from '../ui/Card'
import { ConfirmDialog } from '../ui/Modal'

export function AiScreeningCard({
  canRun,
  analysisReady,
  matchReady,
  screening,
  screeningMeta,
  loading,
  error,
  onRun,
}) {
  const [confirmOpen, setConfirmOpen] = useState(false)
  const ready = analysisReady && matchReady

  async function confirmRun() {
    try {
      await onRun?.()
      setConfirmOpen(false)
    } catch {
      // error surfaced via prop
    }
  }

  return (
    <Card title="AI screening">
      <Alert tone="info">
        AI screening is advisory and does not make the hiring decision. Final status changes require HR or Admin action.
      </Alert>

      {!ready ? (
        <p className="muted">
          Resume analysis and job match are required before AI screening can run.
        </p>
      ) : null}

      {canRun ? (
        <Button
          type="button"
          disabled={!ready || loading}
          loading={loading}
          onClick={() => setConfirmOpen(true)}
          aria-label="Run AI screening"
        >
          Run AI Screening
        </Button>
      ) : null}

      {error ? <Alert tone="error">{error}</Alert> : null}

      {screening ? (
        <div className="stack-gap" style={{ marginTop: '0.75rem' }}>
          <p>
            <strong>Recommendation:</strong>{' '}
            {screeningRecommendationLabel(screening.recommendation)}
          </p>
          <AiScore score={screening.score} maxScore={100} label="Advisory screening score" />
          {screening.confidence ? (
            <p className="muted">Confidence: {screening.confidence}</p>
          ) : null}
          {screening.reasoning ? (
            <div className="job-detail__block">
              <h3>Reason</h3>
              <p className="job-detail__prose">{screening.reasoning}</p>
            </div>
          ) : null}
          {screeningMeta?.message ? (
            <p className="muted">{screeningMeta.message}</p>
          ) : (
            <p className="muted">AI result is advisory. Human review is required.</p>
          )}
        </div>
      ) : null}

      <ConfirmDialog
        open={confirmOpen}
        title="Run AI screening?"
        description="This asks the backend for an advisory recommendation. It will not change the application status."
        confirmLabel="Run screening"
        loading={loading}
        onClose={() => (loading ? null : setConfirmOpen(false))}
        onConfirm={confirmRun}
      />
    </Card>
  )
}
