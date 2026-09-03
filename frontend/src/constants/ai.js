export const AI_DOCUMENT_STATUSES = {
  uploaded: 'uploaded',
  processing: 'processing',
  completed: 'completed',
  failed: 'failed',
}

export const SCREENING_RECOMMENDATIONS = {
  shortlist: 'Shortlist',
  interview: 'Interview',
  reject: 'Reject',
  needs_review: 'Needs review',
}

export function screeningRecommendationLabel(value) {
  return SCREENING_RECOMMENDATIONS[value] || value || '—'
}

/** Score is 0–100 from backend JobMatchingService / AiScreeningService. */
export function matchScoreLabel(score) {
  if (score == null || Number.isNaN(Number(score))) return 'No score'
  const n = Number(score)
  if (n >= 80) return 'Strong match'
  if (n >= 60) return 'Good match'
  if (n >= 40) return 'Moderate match'
  return 'Weak match'
}

/**
 * Derive UI AI state from document + analysis + match.
 * Document statuses come from AiDocumentStatus; analysis/match have no separate status field.
 */
export function deriveAiUiState({
  hasResume,
  documentStatus,
  analysis,
  match,
}) {
  if (!hasResume) return 'no_resume'
  if (documentStatus === 'failed') return 'failed'
  if (documentStatus === 'uploaded' || documentStatus === 'processing') return 'processing'
  if (analysis && match) return 'completed'
  if (analysis && !match) return 'processing_match'
  if (documentStatus === 'completed' && !analysis) return 'processing'
  if (!documentStatus) return 'pending'
  return 'pending'
}

export function aiUiStateLabel(state) {
  switch (state) {
    case 'no_resume':
      return 'No resume'
    case 'pending':
      return 'Pending'
    case 'processing':
    case 'processing_match':
      return 'Processing'
    case 'completed':
      return 'Completed'
    case 'failed':
      return 'Failed'
    default:
      return 'Pending'
  }
}

export function formatAiListItem(item) {
  if (item == null) return ''
  if (typeof item === 'string') return item
  if (typeof item === 'object') {
    return item.title || item.name || item.degree || item.role || item.summary || JSON.stringify(item)
  }
  return String(item)
}
