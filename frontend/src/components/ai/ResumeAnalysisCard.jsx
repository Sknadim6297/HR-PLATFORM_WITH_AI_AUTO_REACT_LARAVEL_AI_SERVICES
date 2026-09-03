import { formatAiListItem } from '../../constants/ai'
import { formatJobDate } from '../../constants/jobs'
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

function BulletList({ items }) {
  if (!items?.length) return <p className="muted">None listed</p>
  return (
    <ul className="ai-bullet-list">
      {items.map((item, index) => (
        <li key={`${formatAiListItem(item)}-${index}`}>{formatAiListItem(item)}</li>
      ))}
    </ul>
  )
}

export function ResumeAnalysisCard({ analysis, detailLevel = 'full', title = 'Resume analysis' }) {
  if (!analysis) {
    return (
      <Card title={title}>
        <p className="muted">AI analysis is not available yet.</p>
      </Card>
    )
  }

  return (
    <Card title={title}>
      <p className="muted ai-ready-note">AI analysis ready.</p>
      <DetailBlock label="Summary">{analysis.summary}</DetailBlock>
      <DetailBlock label="Skills">
        <ChipList items={analysis.skills} />
      </DetailBlock>
      {detailLevel === 'full' ? (
        <>
          <DetailBlock label="Experience">
            <BulletList items={analysis.experience} />
          </DetailBlock>
          <DetailBlock label="Education">
            <BulletList items={analysis.education} />
          </DetailBlock>
          <DetailBlock label="Strengths">
            <ChipList items={analysis.strengths} />
          </DetailBlock>
          <DetailBlock label="Gaps">
            <ChipList items={analysis.gaps} />
          </DetailBlock>
          {analysis.confidence ? (
            <p className="muted">Confidence: {analysis.confidence}</p>
          ) : null}
          {analysis.analyzed_at ? (
            <p className="muted">Analyzed {formatJobDate(analysis.analyzed_at)}</p>
          ) : null}
        </>
      ) : null}
    </Card>
  )
}
