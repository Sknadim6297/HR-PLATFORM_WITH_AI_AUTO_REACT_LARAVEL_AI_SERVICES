import { AiErrorState, AiProcessingState } from './AiProcessingState'
import { AiStatusBadge } from './AiStatusBadge'
import { JobMatchCard } from './JobMatchCard'
import { ResumeAnalysisCard } from './ResumeAnalysisCard'
import { AiScreeningCard } from './AiScreeningCard'
import { ResumeStatus } from '../resume/ResumeStatus'
import { Alert } from '../ui/Alert'
import { Card } from '../ui/Card'
import { Spinner } from '../ui/Spinner'

/**
 * Staff: full analysis, match explanation, screening.
 * Candidate: resume status + limited analysis/match (no screening, no match explanation).
 */
export function AiAssessmentCard({
  forStaff = false,
  hasResume,
  document,
  documentLoading,
  documentError,
  analysis,
  match,
  uiState,
  isProcessing,
  screening,
  screeningMeta,
  screeningLoading,
  screeningError,
  onRefresh,
  onRunScreening,
}) {
  if (documentLoading && !document && hasResume) {
    return (
      <section className="ai-assessment" aria-labelledby="ai-assessment-title">
        <header className="ai-assessment__header">
          <div>
            <p className="eyebrow">Decision support</p>
            <h2 id="ai-assessment-title">AI Candidate Assessment</h2>
          </div>
        </header>
        <Card>
          <Spinner label="Loading AI assessment…" />
        </Card>
      </section>
    )
  }

  return (
    <section className="ai-assessment" aria-labelledby="ai-assessment-title">
      <header className="ai-assessment__header">
        <div>
          <p className="eyebrow">Decision support</p>
          <h2 id="ai-assessment-title">AI Candidate Assessment</h2>
          <p className="muted">
            {forStaff
              ? 'AI-assisted assessment for human review. AI does not select or reject candidates.'
              : 'Basic processing status for your application resume.'}
          </p>
        </div>
        <AiStatusBadge state={uiState} />
      </header>

      {!hasResume ? (
        <Card>
          <p className="muted">No resume available for AI analysis.</p>
        </Card>
      ) : (
        <>
          <Card title="Resume document">
            {documentError ? <Alert tone="error">{documentError}</Alert> : null}
            {document ? <ResumeStatus document={document} /> : (
              <p className="muted">Resume processing</p>
            )}
          </Card>

          {uiState === 'failed' ? (
            <AiErrorState
              title="AI analysis failed. Please try again later."
              description="The resume document failed processing. Upload a new resume on a new application if needed."
              onRetry={onRefresh}
            />
          ) : null}

          {isProcessing && uiState !== 'failed' ? (
            <AiProcessingState
              title={
                uiState === 'processing_match'
                  ? 'AI analysis is being processed.'
                  : 'AI analysis is being processed.'
              }
              description={
                uiState === 'processing_match'
                  ? 'Resume analysis is ready. Job match is still generating.'
                  : 'AI processing is in progress. This view refreshes automatically, or you can refresh later.'
              }
              onRefresh={onRefresh}
            />
          ) : null}

          {uiState === 'completed' || analysis || match ? (
            <div className="ai-assessment__grid">
              <ResumeAnalysisCard
                analysis={analysis}
                detailLevel={forStaff ? 'full' : 'basic'}
              />
              <JobMatchCard match={match} showExplanation={forStaff} />
            </div>
          ) : null}

          {!isProcessing && !analysis && !match && uiState !== 'failed' && hasResume ? (
            <Card>
              <p className="muted">AI analysis is not available yet.</p>
            </Card>
          ) : null}

          {forStaff ? (
            <AiScreeningCard
              canRun
              analysisReady={Boolean(analysis)}
              matchReady={Boolean(match)}
              screening={screening}
              screeningMeta={screeningMeta}
              loading={screeningLoading}
              error={screeningError}
              onRun={onRunScreening}
            />
          ) : null}
        </>
      )}
    </section>
  )
}
