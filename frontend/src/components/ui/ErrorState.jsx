import { Button } from './Button'

export function ErrorState({ title = 'Something went wrong', description, onRetry }) {
  return (
    <div className="state-panel state-panel--error" role="alert">
      <h3>{title}</h3>
      {description ? <p>{description}</p> : null}
      {onRetry ? (
        <Button type="button" variant="secondary" onClick={onRetry}>
          Try again
        </Button>
      ) : null}
    </div>
  )
}
