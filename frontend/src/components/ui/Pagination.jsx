import { Button } from './Button'

export function Pagination({ meta, onPageChange }) {
  if (!meta || !meta.last_page || meta.last_page <= 1) {
    return null
  }

  const current = meta.current_page
  const last = meta.last_page
  const from = meta.from ?? 0
  const to = meta.to ?? 0
  const total = meta.total ?? 0

  return (
    <nav className="pagination" aria-label="Pagination">
      <p className="pagination__summary">
        Showing {from}–{to} of {total}
      </p>
      <div className="pagination__controls">
        <Button
          type="button"
          variant="ghost"
          disabled={current <= 1}
          onClick={() => onPageChange(current - 1)}
        >
          Previous
        </Button>
        <span className="pagination__page" aria-current="page">
          Page {current} of {last}
        </span>
        <Button
          type="button"
          variant="ghost"
          disabled={current >= last}
          onClick={() => onPageChange(current + 1)}
        >
          Next
        </Button>
      </div>
    </nav>
  )
}
