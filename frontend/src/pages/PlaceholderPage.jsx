import { Card } from '../components/ui/Card'

export default function PlaceholderPage({ title, description }) {
  return (
    <div className="page">
      <header className="page__header">
        <h1>{title}</h1>
        <p>{description}</p>
      </header>
      <Card title="Coming soon" subtitle="Foundation route reserved for the next milestone.">
        <p className="muted">This section is intentionally lightweight so routing and layouts can ship first.</p>
      </Card>
    </div>
  )
}
