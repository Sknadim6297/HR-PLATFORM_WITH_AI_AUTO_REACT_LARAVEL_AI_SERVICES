import { ApplicationsIndexPage } from '../applications/ApplicationsIndexPage'

export default function AdminApplicationsPage() {
  return (
    <ApplicationsIndexPage
      basePath="/admin"
      title="Applications"
      subtitle="All applications across the platform."
      forStaff
      showScoreFilters
    />
  )
}
