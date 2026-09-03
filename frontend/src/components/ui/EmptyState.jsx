import { Button } from './Button'

export function EmptyState({ title, description, actionLabel, onAction }) {
  return (
    <div className="state-panel">
      <h3>{title}</h3>
      {description ? <p>{description}</p> : null}
      {actionLabel && onAction ? (
        <Button type="button" variant="secondary" onClick={onAction}>
          {actionLabel}
        </Button>
      ) : null}
    </div>
  )
}
