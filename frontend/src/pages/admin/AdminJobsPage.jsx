import { JobsIndexPage } from '../jobs/JobsIndexPage'

export default function AdminJobsPage() {
  return (
    <JobsIndexPage
      basePath="/admin"
      title="Manage jobs"
      subtitle="Create, publish, and close roles across the platform."
    />
  )
}
