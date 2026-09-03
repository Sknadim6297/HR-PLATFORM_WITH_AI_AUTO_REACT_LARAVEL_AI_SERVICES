import { EmptyState } from '../ui/EmptyState'
import { JobCard } from './JobCard'
import { JobTable } from './JobTable'

export function JobList({
  jobs,
  basePath,
  busyId,
  onPublish,
  onClose,
  onDelete,
  onCreate,
}) {
  if (!jobs.length) {
    return (
      <EmptyState
        title="No jobs found"
        description="Try clearing filters, or create a new job posting."
        actionLabel="Create job"
        onAction={onCreate}
      />
    )
  }

  return (
    <>
      <div className="job-list-desktop">
        <JobTable
          jobs={jobs}
          basePath={basePath}
          busyId={busyId}
          onPublish={onPublish}
          onClose={onClose}
          onDelete={onDelete}
        />
      </div>
      <div className="job-list-mobile">
        {jobs.map((job) => (
          <JobCard
            key={job.id}
            job={job}
            basePath={basePath}
            busy={busyId === job.id}
            onPublish={onPublish}
            onClose={onClose}
            onDelete={onDelete}
          />
        ))}
      </div>
    </>
  )
}
