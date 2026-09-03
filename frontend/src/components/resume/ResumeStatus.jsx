import { formatBytes } from '../../constants/applications'

export function ResumeFileInfo({ document, file }) {
  const name = document?.original_name || file?.name
  const size = document?.file_size ?? file?.size
  const type = document?.mime_type || file?.type

  if (!name) return null

  return (
    <p className="muted resume-file-info">
      {name}
      {size != null ? ` · ${formatBytes(size)}` : ''}
      {type ? ` · ${type}` : ''}
    </p>
  )
}

export function ResumeStatus({ document }) {
  if (!document) return null

  const label = {
    uploaded: 'Uploaded — waiting to process',
    processing: 'Processing resume',
    completed: 'Resume ready',
    failed: 'Processing failed',
  }[document.status] || document.status || 'Resume processing'

  return (
    <div className="resume-status" aria-live="polite">
      <p>
        <strong>{document.original_name || `Document #${document.id}`}</strong>
      </p>
      <ResumeFileInfo document={document} />
      <p className="muted">{label}</p>
      {document.status === 'uploaded' || document.status === 'processing' ? (
        <p className="resume-status__note">Resume uploaded. AI processing has started.</p>
      ) : null}
      {document.status === 'completed' ? (
        <p className="resume-status__note resume-status__note--ok">Resume processing finished.</p>
      ) : null}
      {document.status === 'failed' ? (
        <p className="resume-status__note resume-status__note--err">
          Resume processing failed. You can upload another file.
        </p>
      ) : null}
    </div>
  )
}
