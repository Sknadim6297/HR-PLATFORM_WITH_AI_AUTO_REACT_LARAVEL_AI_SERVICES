import { Link, useParams } from 'react-router-dom'
import { AiAssessmentCard } from '../../components/ai/AiAssessmentCard'
import { CandidateApplicationAiSection } from '../../components/ai/CandidateApplicationAiSection'
import { ApplicationActions } from '../../components/applications/ApplicationActions'
import { ApplicationResumeSection } from '../../components/applications/ApplicationResumeSection'
import { ApplicationStatusBadge } from '../../components/applications/ApplicationStatusBadge'
import { ApplicationTimeline } from '../../components/applications/ApplicationTimeline'
import { ErrorState } from '../../components/ui/ErrorState'
import { Spinner } from '../../components/ui/Spinner'
import { Card } from '../../components/ui/Card'
import { employmentTypeLabel, formatJobDate } from '../../constants/jobs'
import { useApplication } from '../../hooks/useApplication'
import { useApplicationAi } from '../../hooks/useApplicationAi'
import { useToast } from '../../hooks/useToast'

function DetailBlock({ label, children }) {
  if (children == null || children === '') return null
  return (
    <div className="job-detail__block">
      <h3>{label}</h3>
      <div className="job-detail__prose">{children}</div>
    </div>
  )
}

function ChipList({ items }) {
  if (!items?.length) return <p className="muted">None listed</p>
  return (
    <ul className="chip-list">
      {items.map((item) => (
        <li key={item}>{item}</li>
      ))}
    </ul>
  )
}

export function ApplicationDetailsPage({ basePath, forStaff = false }) {
  const { id } = useParams()
  const toast = useToast()
  const { application, setApplication, loading, error, status, refresh } = useApplication(id)
  const hasResume = Boolean(application?.resume_document_id)
  const ai = useApplicationAi(application, {
    refreshApplication: refresh,
    enabled: Boolean(application) && (forStaff || hasResume),
  })

  async function handleScreening() {
    try {
      await ai.runScreening()
      toast.success('AI screening complete. Review the advisory result below.')
    } catch {
      // Error shown in AiScreeningCard
    }
  }

  if (loading) {
    return (
      <div className="page">
        <Spinner label="Loading application…" />
      </div>
    )
  }

  if (error || !application) {
    return (
      <div className="page">
        <ErrorState
          title={status === 404 ? 'Application not found' : 'Could not open application'}
          description={
            status === 403 || status === 404
              ? "You don't have permission to view this application."
              : error || 'Application not found.'
          }
          onRetry={refresh}
        />
        <Link to={`${basePath}/applications`}>Back to applications</Link>
      </div>
    )
  }

  const job = application.job
  const profile = application.candidate?.profile

  return (
    <div className="page">
      <header className="page__header">
        <div>
          <p className="eyebrow">Application</p>
          <h1>{job?.title || `Application #${application.id}`}</h1>
          <p className="muted">
            Applied {formatJobDate(application.applied_at)}
            {' · '}
            Updated {formatJobDate(application.updated_at)}
          </p>
        </div>
        <ApplicationStatusBadge status={application.status} />
      </header>

      <p className="muted">
        <Link to={`${basePath}/applications`}>Back to applications</Link>
      </p>

      <div className="job-detail__grid">
        <div className="stack-gap">
          <Card title="Role">
            {job ? (
              <>
                <dl className="job-meta">
                  <div>
                    <dt>Department</dt>
                    <dd>{job.department || '—'}</dd>
                  </div>
                  <div>
                    <dt>Location</dt>
                    <dd>{job.location || '—'}</dd>
                  </div>
                  <div>
                    <dt>Employment type</dt>
                    <dd>{employmentTypeLabel(job.employment_type)}</dd>
                  </div>
                </dl>
                {forStaff ? (
                  <Link className="text-link" to={`${basePath}/jobs/${job.id}`}>
                    Open job posting
                  </Link>
                ) : (
                  <Link className="text-link" to={`/candidate/jobs/${job.id}`}>
                    View job posting
                  </Link>
                )}
              </>
            ) : (
              <p className="muted">Job details unavailable.</p>
            )}
          </Card>

          {application.cover_letter ? (
            <Card title="Cover letter">
              <p className="job-detail__prose">{application.cover_letter}</p>
            </Card>
          ) : null}

          {!forStaff ? (
            <ApplicationResumeSection
              hasResume={hasResume}
              document={ai.document}
              documentLoading={ai.documentLoading}
              documentError={ai.documentError}
            />
          ) : null}
        </div>

        <div className="stack-gap">
          <Card title="Status">
            <ApplicationTimeline status={application.status} />
          </Card>

          {forStaff ? (
            <Card title="Update status">
              <ApplicationActions
                application={application}
                onUpdated={(next) => {
                  setApplication(next)
                  void refresh()
                }}
              />
            </Card>
          ) : null}

          {forStaff && application.candidate ? (
            <Card title="Candidate">
              <dl className="job-meta">
                <div>
                  <dt>Name</dt>
                  <dd>{application.candidate.name || '—'}</dd>
                </div>
                <div>
                  <dt>Email</dt>
                  <dd>{application.candidate.email || '—'}</dd>
                </div>
                {profile ? (
                  <>
                    <div>
                      <dt>Headline</dt>
                      <dd>{profile.headline || '—'}</dd>
                    </div>
                    <div>
                      <dt>Location</dt>
                      <dd>{profile.location || '—'}</dd>
                    </div>
                    <div>
                      <dt>Phone</dt>
                      <dd>{profile.phone || '—'}</dd>
                    </div>
                    <div>
                      <dt>Experience</dt>
                      <dd>
                        {profile.years_of_experience != null
                          ? `${profile.years_of_experience} years`
                          : '—'}
                      </dd>
                    </div>
                    <div>
                      <dt>Current role</dt>
                      <dd>
                        {[profile.current_role, profile.current_company].filter(Boolean).join(' at ')
                          || '—'}
                      </dd>
                    </div>
                  </>
                ) : null}
              </dl>
              {profile?.skills?.length ? (
                <DetailBlock label="Skills">
                  <ChipList items={profile.skills} />
                </DetailBlock>
              ) : null}
            </Card>
          ) : null}
        </div>
      </div>

      {forStaff ? (
        <AiAssessmentCard
          forStaff
          hasResume={hasResume}
          document={ai.document}
          documentLoading={ai.documentLoading}
          documentError={ai.documentError}
          analysis={ai.analysis}
          match={ai.match}
          uiState={ai.uiState}
          isProcessing={ai.isProcessing}
          screening={ai.screening}
          screeningMeta={ai.screeningMeta}
          screeningLoading={ai.screeningLoading}
          screeningError={ai.screeningError}
          onRefresh={ai.refresh}
          onRunScreening={handleScreening}
        />
      ) : hasResume ? (
        <CandidateApplicationAiSection
          analysis={ai.analysis}
          match={ai.match}
          uiState={ai.uiState}
          isProcessing={ai.isProcessing}
          onRefresh={ai.refresh}
        />
      ) : null}
    </div>
  )
}
