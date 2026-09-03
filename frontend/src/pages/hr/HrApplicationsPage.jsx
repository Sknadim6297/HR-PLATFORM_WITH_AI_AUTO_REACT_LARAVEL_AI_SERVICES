import { ApplicationsIndexPage } from '../applications/ApplicationsIndexPage'

export default function HrApplicationsPage() {
  return (
    <ApplicationsIndexPage
      basePath="/hr"
      title="Applications"
      subtitle="Review candidates and move them through the hiring pipeline."
      forStaff
      showScoreFilters
    />
  )
}
