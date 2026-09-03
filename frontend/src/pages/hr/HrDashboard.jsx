import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { getApplications } from '../../api/applications'
import { getJobs } from '../../api/jobs'
import { Badge } from '../../components/ui/Badge'
import { Button } from '../../components/ui/Button'
import { Card } from '../../components/ui/Card'
import { ErrorState } from '../../components/ui/ErrorState'
import { Spinner } from '../../components/ui/Spinner'

export default function HrDashboard() {
  const [state, setState] = useState({ loading: true, error: '', jobs: 0, applications: 0 })

  useEffect(() => {
    let cancelled = false

    async function load() {
      try {
        const [jobs, applications] = await Promise.all([
          getJobs({ per_page: 1 }),
          getApplications({ per_page: 1 }),
        ])

        if (cancelled) return

        setState({
          loading: false,
          error: '',
          jobs: jobs.meta?.total ?? 0,
          applications: applications.meta?.total ?? 0,
        })
      } catch (error) {
        if (cancelled) return
        setState({
          loading: false,
          error: error.normalized?.message || 'Unable to load HR overview.',
          jobs: 0,
          applications: 0,
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
          <p>See how many roles and applications you are working with today.</p>
        </div>
        <Badge tone="info">In progress</Badge>
      </header>

      {state.loading ? <Spinner label="Loading…" /> : null}
      {state.error ? (
        <ErrorState
          description={state.error}
          onRetry={() => window.location.reload()}
        />
      ) : null}

      {!state.loading && !state.error ? (
        <>
          <div className="stat-grid">
            <Card title="Jobs">
              <p className="stat-value">{state.jobs}</p>
              <p className="muted">Roles available in your view.</p>
            </Card>
            <Card title="Applications">
              <p className="stat-value">{state.applications}</p>
              <p className="muted">Candidates waiting for a review.</p>
            </Card>
            <Card title="Coming next">
              <p className="stat-value">Pipeline</p>
              <p className="muted">Full screening screens land in the next frontend pass.</p>
            </Card>
          </div>

          <div className="dashboard-actions">
            <Link to="/hr/jobs">
              <Button type="button" variant="secondary">Manage jobs</Button>
            </Link>
            <Link to="/hr/jobs/create">
              <Button type="button">Create job</Button>
            </Link>
          </div>
        </>
      ) : null}
    </div>
  )
}
