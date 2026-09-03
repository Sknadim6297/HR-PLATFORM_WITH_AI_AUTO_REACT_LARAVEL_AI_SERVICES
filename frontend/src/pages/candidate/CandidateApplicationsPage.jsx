import { ApplicationsIndexPage } from '../applications/ApplicationsIndexPage'

export default function CandidateApplicationsPage() {
  return (
    <ApplicationsIndexPage
      basePath="/candidate"
      title="My applications"
      subtitle="Track every role you have applied to."
      forStaff={false}
      emptyBrowseTo="/candidate/jobs"
    />
  )
}
