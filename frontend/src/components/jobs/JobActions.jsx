import { Link } from 'react-router-dom'
import { Button } from '../ui/Button'

export function JobActions({
  job,
  basePath,
  busy = false,
  onPublish,
  onClose,
  onDelete,
  compact = false,
}) {
  const canPublish = job.status === 'draft' || job.status === 'closed' || job.status === 'archived'
  const canClose = job.status === 'published'

  return (
    <div className={`job-actions ${compact ? 'job-actions--compact' : ''}`}>
      <Link className="text-link job-actions__link" to={`${basePath}/jobs/${job.id}`}>
        View
      </Link>
      <Link className="text-link job-actions__link" to={`${basePath}/jobs/${job.id}/edit`}>
        Edit
      </Link>
      {canPublish ? (
        <Button type="button" variant="secondary" disabled={busy} onClick={() => onPublish(job)}>
          Publish
        </Button>
      ) : null}
      {canClose ? (
        <Button type="button" variant="secondary" disabled={busy} onClick={() => onClose(job)}>
          Close
        </Button>
      ) : null}
      <Button type="button" variant="ghost" disabled={busy} onClick={() => onDelete(job)}>
        Delete
      </Button>
    </div>
  )
}
