import { Link } from 'react-router-dom'
import { employmentTypeLabel, formatJobDate } from '../../constants/jobs'
import { JobActions } from './JobActions'
import { JobStatusBadge } from './JobStatusBadge'

export function JobTable({ jobs, basePath, busyId, onPublish, onClose, onDelete }) {
  return (
    <div className="job-table-wrap">
      <table className="job-table">
        <thead>
          <tr>
            <th scope="col">Title</th>
            <th scope="col">Department</th>
            <th scope="col">Location</th>
            <th scope="col">Type</th>
            <th scope="col">Status</th>
            <th scope="col">Created</th>
            <th scope="col">Actions</th>
          </tr>
        </thead>
        <tbody>
          {jobs.map((job) => (
            <tr key={job.id}>
              <td>
                <Link to={`${basePath}/jobs/${job.id}`} className="job-table__title">
                  {job.title}
                </Link>
              </td>
              <td>{job.department || '—'}</td>
              <td>{job.location || '—'}</td>
              <td>{employmentTypeLabel(job.employment_type)}</td>
              <td>
                <JobStatusBadge status={job.status} />
              </td>
              <td>{formatJobDate(job.created_at)}</td>
              <td>
                <JobActions
                  job={job}
                  basePath={basePath}
                  busy={busyId === job.id}
                  onPublish={onPublish}
                  onClose={onClose}
                  onDelete={onDelete}
                  compact
                />
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  )
}
