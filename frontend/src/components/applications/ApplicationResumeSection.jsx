import { Card } from '../ui/Card'
import { EmptyState } from '../ui/EmptyState'

/**
 * Candidate-facing resume confirmation only.
 * No AI processing / analysis / match UI — that is HR/Admin only.
 * Source of truth: application.resume_document_id
 */
export function ApplicationResumeSection({ hasResume }) {
  if (!hasResume) {
    return (
      <Card title="Resume">
        <EmptyState
          title="No resume attached to this application"
          description="This application was submitted without a resume. You can attach a resume when applying to a new role."
        />
      </Card>
    )
  }

  return (
    <Card title="Resume">
      <p>
        <strong>Resume attached</strong>
      </p>
      <p className="muted">
        Your resume was submitted with this application. HR will review it as part of the hiring process.
      </p>
    </Card>
  )
}
