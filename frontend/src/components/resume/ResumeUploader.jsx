import { useEffect, useState } from 'react'
import * as aiApi from '../../api/ai'
import { validateResumeFile } from '../../constants/applications'
import { ResumeFileInfo, ResumeStatus } from './ResumeStatus'
import { Button } from '../ui/Button'
import { Alert } from '../ui/Alert'

const POLL_MS = 4000

export function ResumeUploader({
  value,
  onChange,
  disabled = false,
}) {
  const [file, setFile] = useState(null)
  const [localError, setLocalError] = useState('')
  const [uploading, setUploading] = useState(false)
  const [progress, setProgress] = useState(0)
  const [document, setDocument] = useState(value || null)

  useEffect(() => {
    const tick = window.setTimeout(() => {
      setDocument(value || null)
    }, 0)
    return () => window.clearTimeout(tick)
  }, [value])

  useEffect(() => {
    if (!document?.id) return undefined
    if (document.status === 'completed' || document.status === 'failed') return undefined

    let cancelled = false
    let timer

    async function poll() {
      try {
        const response = await aiApi.getDocument(document.id)
        if (cancelled) return
        const next = response.data
        setDocument(next)
        onChange?.(next)
        if (next.status !== 'completed' && next.status !== 'failed') {
          timer = window.setTimeout(poll, POLL_MS)
        }
      } catch {
        if (!cancelled) {
          timer = window.setTimeout(poll, POLL_MS)
        }
      }
    }

    timer = window.setTimeout(poll, POLL_MS)
    return () => {
      cancelled = true
      window.clearTimeout(timer)
    }
  }, [document?.id, document?.status, onChange])

  async function handleUpload() {
    setLocalError('')
    const validationError = validateResumeFile(file)
    if (validationError) {
      setLocalError(validationError)
      return
    }

    setUploading(true)
    setProgress(0)
    try {
      const response = await aiApi.uploadDocument(file, {
        onUploadProgress: (event) => {
          if (!event.total) return
          setProgress(Math.round((event.loaded / event.total) * 100))
        },
      })
      const next = response.data
      setDocument(next)
      onChange?.(next)
    } catch (err) {
      setLocalError(err.normalized?.message || 'Upload failed. Please try again.')
    } finally {
      setUploading(false)
    }
  }

  return (
    <div className="resume-uploader">
      <label className="field" htmlFor="resume-file">
        <span className="field__label">Resume (PDF, DOCX, or TXT · max 10 MB)</span>
        <input
          id="resume-file"
          type="file"
          accept=".pdf,.docx,.txt,application/pdf,text/plain,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
          disabled={disabled || uploading}
          onChange={(event) => {
            setFile(event.target.files?.[0] || null)
            setLocalError('')
          }}
        />
      </label>

      {file ? <ResumeFileInfo file={file} /> : null}

      {localError ? <Alert tone="error">{localError}</Alert> : null}

      <Button type="button" variant="secondary" loading={uploading} disabled={!file || disabled} onClick={handleUpload}>
        Upload resume
      </Button>

      {uploading && progress > 0 ? (
        <p className="muted" aria-live="polite">
          Uploading… {progress}%
        </p>
      ) : null}

      {document ? <ResumeStatus document={document} /> : null}
    </div>
  )
}

export { ResumeStatus, ResumeFileInfo }