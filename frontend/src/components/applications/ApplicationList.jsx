import { Link } from 'react-router-dom'
import { formatJobDate } from '../../constants/jobs'
import { ApplicationStatusBadge } from './ApplicationStatusBadge'

export function ApplicationCard({ application, basePath, forStaff }) {
  return (
    <article className="job-card">
      <div className="job-card__top">
        <div>
          <Link to={`${basePath}/applications/${application.id}`} className="job-card__title">
            {application.job?.title || `Application #${application.id}`}
          </Link>
          <p className="muted">
            {forStaff
              ? application.candidate?.name || 'Candidate'
              : application.job?.department || 'Role'}
            {' · '}
            Applied {formatJobDate(application.applied_at)}
          </p>
        </div>
        <ApplicationStatusBadge status={application.status} />
      </div>
      {application.job_match?.score != null ? (
        <p className="muted">Match score: {application.job_match.score}</p>
      ) : null}
      <Link className="text-link" to={`${basePath}/applications/${application.id}`}>
        Open application
      </Link>
    </article>
  )
}

export function ApplicationTable({ applications, basePath, forStaff }) {
  return (
    <div className="job-table-wrap">
      <table className="job-table">
        <thead>
          <tr>
            <th scope="col">Job</th>
            {forStaff ? <th scope="col">Candidate</th> : null}
            <th scope="col">Status</th>
            <th scope="col">Applied</th>
            <th scope="col">Match</th>
            <th scope="col">Updated</th>
            <th scope="col">Actions</th>
          </tr>
        </thead>
        <tbody>
          {applications.map((application) => (
            <tr key={application.id}>
              <td>
                <Link to={`${basePath}/applications/${application.id}`} className="job-table__title">
                  {application.job?.title || `#${application.id}`}
                </Link>
              </td>
              {forStaff ? <td>{application.candidate?.name || '—'}</td> : null}
              <td>
                <ApplicationStatusBadge status={application.status} />
              </td>
              <td>{formatJobDate(application.applied_at)}</td>
              <td>{application.job_match?.score ?? '—'}</td>
              <td>{formatJobDate(application.updated_at)}</td>
              <td>
                <Link className="text-link" to={`${basePath}/applications/${application.id}`}>
                  View
                </Link>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  )
}

export function ApplicationList({ applications, basePath, forStaff = false, emptyAction }) {
  if (!applications.length) {
    return (
      <div className="state-panel">
        <h3>No applications yet</h3>
        <p>{forStaff ? 'Nothing matches these filters.' : "You haven't applied to any jobs yet."}</p>
        {emptyAction || null}
      </div>
    )
  }

  return (
    <>
      <div className="job-list-desktop">
        <ApplicationTable applications={applications} basePath={basePath} forStaff={forStaff} />
      </div>
      <div className="job-list-mobile">
        {applications.map((application) => (
          <ApplicationCard
            key={application.id}
            application={application}
            basePath={basePath}
            forStaff={forStaff}
          />
        ))}
      </div>
    </>
  )
}
