import { formatAiListItem, matchScoreLabel } from '../../constants/ai'
import { formatJobDate } from '../../constants/jobs'
import { AiScore } from './AiScore'
import { Card } from '../ui/Card'

function DetailBlock({ label, children }) {
  if (children == null || children === '') return null
  return (
    <div className="job-detail__block">
      <h3>{label}</h3>
      <div className="job-detail__prose">{children}</div>
    </div>
  )
}

function ChipList({ items }) {
  if (!items?.length) return <p className="muted">None listed</p>
  return (
    <ul className="chip-list">
      {items.map((item, index) => (
        <li key={`${formatAiListItem(item)}-${index}`}>{formatAiListItem(item)}</li>
      ))}
    </ul>
  )
}

export function JobMatchCard({ match, showExplanation = false, title = 'Job match' }) {
  if (!match) {
    return (
      <Card title={title}>
        <p className="muted">Job match has not been generated yet.</p>
      </Card>
    )
  }

  return (
    <Card title={title}>
      <AiScore score={match.score} maxScore={100} label={matchScoreLabel(match.score)} />
      <DetailBlock label="Matched skills">
        <ChipList items={match.matched_skills} />
      </DetailBlock>
      <DetailBlock label="Missing skills">
        <ChipList items={match.missing_skills} />
      </DetailBlock>
      {showExplanation && match.reasoning ? (
        <DetailBlock label="Match explanation">{match.reasoning}</DetailBlock>
      ) : null}
      {match.confidence ? <p className="muted">Confidence: {match.confidence}</p> : null}
      {match.generated_at ? (
        <p className="muted">Generated {formatJobDate(match.generated_at)}</p>
      ) : null}
    </Card>
  )
}
