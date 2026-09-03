import { useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import * as jobsApi from '../../api/jobs'
import { JobForm } from '../../components/jobs/JobForm'
import { Card } from '../../components/ui/Card'
import { ErrorState } from '../../components/ui/ErrorState'
import { Spinner } from '../../components/ui/Spinner'
import {
  emptyJobForm,
  formValuesToPayload,
  jobToFormValues,
} from '../../constants/jobs'
import { useJob } from '../../hooks/useJob'
import { useToast } from '../../hooks/useToast'

export function JobFormPage({ basePath, mode }) {
  const { id } = useParams()
  const isEdit = mode === 'edit'
  const navigate = useNavigate()
  const toast = useToast()
  const { job, loading, error, status } = useJob(isEdit ? id : null)
  const [submitting, setSubmitting] = useState(false)

  async function handleSubmit(values) {
    setSubmitting(true)
    try {
      const payload = formValuesToPayload(values)
      if (isEdit) {
        await jobsApi.updateJob(id, payload)
        toast.success('Job updated.')
        navigate(`${basePath}/jobs/${id}`)
      } else {
        const response = await jobsApi.createJob(payload)
        toast.success('Job created.')
        navigate(`${basePath}/jobs/${response.data.id}`)
      }
    } finally {
      setSubmitting(false)
    }
  }

  if (isEdit && loading) {
    return (
      <div className="page">
        <Spinner label="Loading job…" />
      </div>
    )
  }

  if (isEdit && error) {
    return (
      <div className="page">
        <ErrorState
          title={status === 404 ? 'Job not found' : 'Could not open job'}
          description={error}
        />
        <Link to={`${basePath}/jobs`}>Back to jobs</Link>
      </div>
    )
  }

  return (
    <div className="page">
      <header className="page__header">
        <div>
          <p className="eyebrow">Jobs</p>
          <h1>{isEdit ? 'Edit job' : 'Create job'}</h1>
          <p>
            {isEdit
              ? 'Update the posting details. Publishing stays a separate action.'
              : 'New jobs start as drafts until you publish them.'}
          </p>
        </div>
        <Link className="text-link" to={`${basePath}/jobs`}>
          Back to list
        </Link>
      </header>

      <Card>
        <JobForm
          key={isEdit ? job?.id : 'create'}
          initialValues={isEdit && job ? jobToFormValues(job) : emptyJobForm()}
          submitLabel={isEdit ? 'Save changes' : 'Create job'}
          submitting={submitting}
          onSubmit={handleSubmit}
        />
      </Card>
    </div>
  )
}
