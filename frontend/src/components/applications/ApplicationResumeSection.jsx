import { Alert } from '../ui/Alert'
import { Card } from '../ui/Card'
import { EmptyState } from '../ui/EmptyState'
import { Spinner } from '../ui/Spinner'
import { ResumeStatus } from '../resume/ResumeStatus'

/**
 * Application-level resume section.
 * Source of truth: application.resume_document_id (not candidate profile).
 */
export function ApplicationResumeSection({
  hasResume,
  document,
  documentLoading,
  documentError,
}) {
  if (!hasResume) {
    return (
      <Card title="Resume">
        <EmptyState
          title="No resume attached to this application"
          description="This application was submitted without a resume. AI-powered resume analysis and job matching are unavailable for this application."
        />
        <p className="muted application-resume-note">
          You can attach a resume when submitting a new application.
        </p>
      </Card>
    )
  }

  return (
    <Card title="Resume">
      {documentError ? <Alert tone="error">{documentError}</Alert> : null}
      {documentLoading && !document ? <Spinner label="Loading resume…" /> : null}
      {document ? (
        <ResumeStatus document={document} />
      ) : !documentLoading ? (
        <p className="muted">Resume processing</p>
      ) : null}
    </Card>
  )
}
