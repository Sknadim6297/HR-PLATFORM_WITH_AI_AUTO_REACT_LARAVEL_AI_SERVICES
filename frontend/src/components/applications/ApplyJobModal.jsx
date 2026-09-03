import { useEffect, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import * as applicationsApi from '../../api/applications'
import { ResumeUploader } from '../resume/ResumeUploader'
import { Button } from '../ui/Button'
import { Modal } from '../ui/Modal'
import { Textarea } from '../ui/Textarea'
import { Alert } from '../ui/Alert'
import { useToast } from '../../hooks/useToast'

export function ApplyJobModal({ open, job, onClose }) {
  const toast = useToast()
  const navigate = useNavigate()
  const [coverLetter, setCoverLetter] = useState('')
  const [document, setDocument] = useState(null)
  const [submitting, setSubmitting] = useState(false)
  const [error, setError] = useState('')

  useEffect(() => {
    if (!open) return undefined
    const tick = window.setTimeout(() => {
      setCoverLetter('')
      setDocument(null)
      setError('')
      setSubmitting(false)
    }, 0)
    return () => window.clearTimeout(tick)
  }, [open, job?.id])

  async function handleSubmit() {
    setError('')
    setSubmitting(true)
    try {
      const payload = {
        cover_letter: coverLetter.trim() || null,
        resume_document_id: document?.id || null,
      }
      const response = await applicationsApi.applyToJob(job.id, payload)
      toast.success('Application submitted.')
      onClose?.()
      navigate(`/candidate/applications/${response.data.id}`)
    } catch (err) {
      if (err.normalized?.status === 409) {
        setError('You have already applied for this position.')
      } else {
        setError(err.normalized?.message || 'Could not submit your application.')
      }
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <Modal
      open={open}
      title={`Apply to ${job?.title || 'this role'}`}
      onClose={submitting ? undefined : onClose}
      footer={
        <>
          <Button type="button" variant="ghost" disabled={submitting} onClick={onClose}>
            Cancel
          </Button>
          <Button type="button" loading={submitting} onClick={handleSubmit}>
            Submit application
          </Button>
        </>
      }
    >
      <div className="apply-modal">
        <p className="muted">
          You can add a short cover letter and attach a resume. Resume processing continues after you submit.
        </p>

        {error ? <Alert tone="error">{error}</Alert> : null}

        <Textarea
          label="Cover letter (optional)"
          name="cover_letter"
          rows={5}
          value={coverLetter}
          onChange={(event) => setCoverLetter(event.target.value)}
          disabled={submitting}
        />

        <ResumeUploader value={document} onChange={setDocument} disabled={submitting} />
      </div>
    </Modal>
  )
}
