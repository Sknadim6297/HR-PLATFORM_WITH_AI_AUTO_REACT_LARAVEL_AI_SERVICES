import { matchScoreLabel } from '../../constants/ai'

export function AiScore({ score, maxScore = 100, label }) {
  const numeric = score == null || Number.isNaN(Number(score)) ? null : Number(score)
  const safeMax = maxScore > 0 ? maxScore : 100
  const pct = numeric == null ? 0 : Math.max(0, Math.min(100, (numeric / safeMax) * 100))
  const textLabel = label || matchScoreLabel(numeric)

  return (
    <div className="ai-score" role="img" aria-label={numeric == null ? 'No score' : `${numeric} out of ${safeMax}, ${textLabel}`}>
      <div className="ai-score__ring" style={{ '--ai-score': `${pct}%` }}>
        <div className="ai-score__inner">
          <span className="ai-score__value">{numeric == null ? '—' : numeric}</span>
          <span className="ai-score__max">/ {safeMax}</span>
        </div>
      </div>
      <div className="ai-score__meta">
        <p className="ai-score__label">{textLabel}</p>
        <div className="ai-score__bar" aria-hidden="true">
          <span style={{ width: `${pct}%` }} />
        </div>
      </div>
    </div>
  )
}
