import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { getApplications } from '../../api/applications'
import { getJobs } from '../../api/jobs'
import { Badge } from '../../components/ui/Badge'
import { Button } from '../../components/ui/Button'
import { Card } from '../../components/ui/Card'
import { ErrorState } from '../../components/ui/ErrorState'
import { Spinner } from '../../components/ui/Spinner'

export default function AdminDashboard() {
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
          jobs: jobs.meta?.total ?? jobs.data?.length ?? 0,
          applications: applications.meta?.total ?? applications.data?.length ?? 0,
        })
      } catch (error) {
        if (cancelled) return
        setState({
          loading: false,
          error: error.normalized?.message || 'Unable to load admin overview.',
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
          <p className="eyebrow">Admin</p>
          <h1>Overview</h1>
          <p>A quick look at jobs and applications across the platform.</p>
        </div>
        <Badge tone="success">Online</Badge>
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
              <p className="muted">Job postings currently in the system.</p>
            </Card>
            <Card title="Applications">
              <p className="stat-value">{state.applications}</p>
              <p className="muted">Total applications received so far.</p>
            </Card>
            <Card title="Status">
              <p className="stat-value">All good</p>
              <p className="muted">Auth and API are responding.</p>
            </Card>
          </div>

          <div className="dashboard-actions">
            <Link to="/admin/jobs">
              <Button type="button" variant="secondary">Manage jobs</Button>
            </Link>
            <Link to="/admin/jobs/create">
              <Button type="button">Create job</Button>
            </Link>
          </div>
        </>
      ) : null}
    </div>
  )
}
