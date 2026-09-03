import { useEffect, useState } from 'react'
import { getApplications } from '../../api/applications'
import { getJobs } from '../../api/jobs'
import { getProfile } from '../../api/candidates'
import { Badge } from '../../components/ui/Badge'
import { Card } from '../../components/ui/Card'
import { ErrorState } from '../../components/ui/ErrorState'
import { Spinner } from '../../components/ui/Spinner'

export default function CandidateDashboard() {
  const [state, setState] = useState({
    loading: true,
    error: '',
    jobs: 0,
    applications: 0,
    profileComplete: false,
  })

  useEffect(() => {
    let cancelled = false

    async function load() {
      try {
        const [jobs, applications, profileResult] = await Promise.all([
          getJobs({ per_page: 1 }),
          getApplications({ per_page: 1 }),
          getProfile().catch((error) => {
            if (error.normalized?.status === 404) {
              return null
            }
            throw error
          }),
        ])

        if (cancelled) return

        const profile = profileResult?.data
        const profileComplete = Boolean(profile?.headline && (profile?.skills?.length || 0) > 0)

        setState({
          loading: false,
          error: '',
          jobs: jobs.meta?.total ?? jobs.data?.length ?? 0,
          applications: applications.meta?.total ?? applications.data?.length ?? 0,
          profileComplete,
        })
      } catch (error) {
        if (cancelled) return
        setState({
          loading: false,
          error: error.normalized?.message || 'Unable to load your dashboard.',
          jobs: 0,
          applications: 0,
          profileComplete: false,
        })
      }
    }

    void load()

    return () => {
      cancelled = true
    }
  }, [])

  return (
    <div className="page">
      <header className="page__header">
        <div>
          <p className="eyebrow">Candidate</p>
          <h1>Hello there</h1>
          <p>Open roles, your applications, and whether your profile is ready.</p>
        </div>
        <Badge tone={state.profileComplete ? 'success' : 'warning'}>
          {state.profileComplete ? 'Profile looks good' : 'Finish your profile'}
        </Badge>
      </header>

      {state.loading ? <Spinner label="Loading…" /> : null}
      {state.error ? (
        <ErrorState description={state.error} onRetry={() => window.location.reload()} />
      ) : null}

      {!state.loading && !state.error ? (
        <div className="stat-grid">
          <Card title="Open jobs">
            <p className="stat-value">{state.jobs}</p>
            <p className="muted">Published roles you can browse.</p>
          </Card>
          <Card title="My applications">
            <p className="stat-value">{state.applications}</p>
            <p className="muted">Applications you have already sent.</p>
          </Card>
          <Card title="Profile">
            <p className="stat-value">{state.profileComplete ? 'Ready' : 'Needs work'}</p>
            <p className="muted">A headline and skills help matching later.</p>
          </Card>
        </div>
      ) : null}
    </div>
  )
}
