import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { getApplications } from '../../api/applications'
import { getJobs } from '../../api/jobs'
import { getNotifications } from '../../api/notifications'
import { NotificationList } from '../../components/notifications/NotificationList'
import { Badge } from '../../components/ui/Badge'
import { Button } from '../../components/ui/Button'
import { Card } from '../../components/ui/Card'
import { ErrorState } from '../../components/ui/ErrorState'
import { Spinner } from '../../components/ui/Spinner'

export default function HrDashboard() {
  const [state, setState] = useState({
    loading: true,
    error: '',
    jobs: 0,
    applications: 0,
    notifications: [],
  })

  useEffect(() => {
    let cancelled = false

    async function load() {
      try {
        const [jobs, applications, notifications] = await Promise.all([
          getJobs({ per_page: 1 }),
          getApplications({ per_page: 1 }),
          getNotifications({ per_page: 5 }).catch(() => ({ data: [] })),
        ])

        if (cancelled) return

        setState({
          loading: false,
          error: '',
          jobs: jobs.meta?.total ?? 0,
          applications: applications.meta?.total ?? 0,
          notifications: notifications.data || [],
        })
      } catch (error) {
        if (cancelled) return
        setState({
          loading: false,
          error: error.normalized?.message || 'Unable to load HR overview.',
          jobs: 0,
          applications: 0,
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
          <p className="eyebrow">HR</p>
          <h1>Hiring desk</h1>
          <p>Jobs, applications, and recent hiring alerts.</p>
        </div>
        <Badge tone="info">Active</Badge>
      </header>

      {state.loading ? <Spinner label="Loading…" /> : null}
      {state.error ? (
        <ErrorState description={state.error} onRetry={() => window.location.reload()} />
      ) : null}

      {!state.loading && !state.error ? (
        <>
          <div className="stat-grid">
            <Card title="Jobs">
              <p className="stat-value">{state.jobs}</p>
              <p className="muted">Roles in your workspace.</p>
              <Link className="text-link" to="/hr/jobs">Manage jobs</Link>
            </Card>
            <Card title="Applications">
              <p className="stat-value">{state.applications}</p>
              <p className="muted">Candidates in the pipeline.</p>
              <Link className="text-link" to="/hr/applications">Review applications</Link>
            </Card>
            <Card title="Candidates">
              <p className="stat-value">Pipeline</p>
              <p className="muted">Open an application to see candidate details.</p>
              <Link className="text-link" to="/hr/applications">Open pipeline</Link>
            </Card>
          </div>

          <Card title="Recent notifications">
            <NotificationList
              notifications={state.notifications}
              applicationBasePath="/hr"
            />
          </Card>

          <div className="dashboard-actions">
            <Link to="/hr/applications">
              <Button type="button">Applications</Button>
            </Link>
            <Link to="/hr/jobs">
              <Button type="button" variant="secondary">Manage jobs</Button>
            </Link>
            <Link to="/hr/jobs/create">
              <Button type="button" variant="ghost">Create job</Button>
            </Link>
          </div>
        </>
      ) : null}
    </div>
  )
}
