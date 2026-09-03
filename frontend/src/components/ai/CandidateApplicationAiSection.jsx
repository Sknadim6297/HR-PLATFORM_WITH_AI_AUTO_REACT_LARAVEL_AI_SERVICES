import { AiErrorState, AiProcessingState } from './AiProcessingState'
import { JobMatchCard } from './JobMatchCard'
import { ResumeAnalysisCard } from './ResumeAnalysisCard'

/**
 * Candidate-safe AI results when a resume is attached to the application.
 * Does not show HR screening or internal assessment framing.
 */
export function CandidateApplicationAiSection({
  analysis,
  match,
  uiState,
  isProcessing,
  onRefresh,
}) {
  if (uiState === 'failed') {
    return (
      <section className="candidate-ai-section" aria-label="AI results">
        <AiErrorState
          title="Resume processing failed."
          description="We could not process your resume for this application. You may apply to another role with a new resume if needed."
          onRetry={onRefresh}
        />
      </section>
    )
  }

  if (isProcessing) {
    return (
      <section className="candidate-ai-section" aria-label="AI results">
        <AiProcessingState
          title={uiState === 'processing_match' ? 'Analyzing resume…' : 'Analyzing resume…'}
          description={
            uiState === 'processing_match'
              ? 'Resume analysis is ready. Job match is still being generated.'
              : 'Your resume is being processed. Results will appear here when ready.'
          }
          onRefresh={onRefresh}
        />
        {analysis ? (
          <ResumeAnalysisCard analysis={analysis} detailLevel="basic" title="AI Resume Analysis" />
        ) : null}
      </section>
    )
  }

  if (!analysis && !match) {
    return (
      <section className="candidate-ai-section" aria-label="AI results">
        <ResumeAnalysisCard analysis={null} title="AI Resume Analysis" />
        <JobMatchCard match={null} title="AI Job Match" />
      </section>
    )
  }

  return (
    <section className="candidate-ai-section" aria-label="AI results">
      <div className="ai-assessment__grid">
        <ResumeAnalysisCard analysis={analysis} detailLevel="basic" title="AI Resume Analysis" />
        <JobMatchCard match={match} showExplanation={false} title="AI Job Match" />
      </div>
    </section>
  )
}
