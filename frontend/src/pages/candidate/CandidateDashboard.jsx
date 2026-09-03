import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { getApplications } from '../../api/applications'
import { getProfile } from '../../api/candidates'
import { getJobs } from '../../api/jobs'
import { getNotifications } from '../../api/notifications'
import { NotificationList } from '../../components/notifications/NotificationList'
import { Badge } from '../../components/ui/Badge'
import { Button } from '../../components/ui/Button'
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
    notifications: [],
  })

  useEffect(() => {
    let cancelled = false

    async function load() {
      try {
        const [jobs, applications, profileResult, notifications] = await Promise.all([
          getJobs({ per_page: 1 }),
          getApplications({ per_page: 1 }),
          getProfile().catch((error) => {
            if (error.normalized?.status === 404) return null
            throw error
          }),
          getNotifications({ per_page: 5 }).catch(() => ({ data: [] })),
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
          notifications: notifications.data || [],
        })
      } catch (error) {
        if (cancelled) return
        setState({
          loading: false,
          error: error.normalized?.message || 'Unable to load your dashboard.',
          jobs: 0,
          applications: 0,
          profileComplete: false,
          notifications: [],
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
        <>
          <div className="stat-grid">
            <Card title="Available jobs">
              <p className="stat-value">{state.jobs}</p>
              <p className="muted">Published roles you can browse.</p>
              <Link className="text-link" to="/candidate/jobs">Browse jobs</Link>
            </Card>
            <Card title="My applications">
              <p className="stat-value">{state.applications}</p>
              <p className="muted">Applications you have already sent.</p>
              <Link className="text-link" to="/candidate/applications">View applications</Link>
            </Card>
            <Card title="Profile">
              <p className="stat-value">{state.profileComplete ? 'Ready' : 'Needs work'}</p>
              <p className="muted">A headline and skills help matching later.</p>
              <Link className="text-link" to="/candidate/profile">Edit profile</Link>
            </Card>
          </div>

          <Card title="Recent notifications">
            <NotificationList
              notifications={state.notifications}
              applicationBasePath="/candidate"
            />
          </Card>

          <div className="dashboard-actions">
            <Link to="/candidate/jobs">
              <Button type="button">Browse jobs</Button>
            </Link>
            <Link to="/candidate/applications">
              <Button type="button" variant="secondary">My applications</Button>
            </Link>
            <Link to="/candidate/profile">
              <Button type="button" variant="ghost">Profile</Button>
            </Link>
          </div>
        </>
      ) : null}
    </div>
  )
}
