import { useState } from 'react'
import { EMPLOYMENT_TYPES, emptyJobForm } from '../../constants/jobs'
import { Button } from '../ui/Button'
import { Input } from '../ui/Input'
import { Select } from '../ui/Select'
import { Textarea } from '../ui/Textarea'

export function JobForm({
  initialValues,
  submitLabel = 'Save job',
  onSubmit,
  submitting = false,
}) {
  const [values, setValues] = useState(initialValues || emptyJobForm())
  const [errors, setErrors] = useState({})
  const [formError, setFormError] = useState('')

  function updateField(event) {
    const { name, value } = event.target
    setValues((current) => ({ ...current, [name]: value }))
    setErrors((current) => ({ ...current, [name]: undefined }))
  }

  function validate() {
    const next = {}
    if (!values.title.trim() || values.title.trim().length < 3) {
      next.title = 'Title needs at least 3 characters.'
    }
    if (!values.description.trim() || values.description.trim().length < 20) {
      next.description = 'Description needs at least 20 characters.'
    }
    if (!values.employment_type) {
      next.employment_type = 'Pick an employment type.'
    }
    if (values.salary_min !== '' && values.salary_max !== '' && Number(values.salary_max) < Number(values.salary_min)) {
      next.salary_max = 'Max salary should be greater than or equal to min salary.'
    }
    if (
      values.experience_min !== ''
      && values.experience_max !== ''
      && Number(values.experience_max) < Number(values.experience_min)
    ) {
      next.experience_max = 'Max experience should be greater than or equal to min experience.'
    }
    return next
  }

  async function handleSubmit(event) {
    event.preventDefault()
    setFormError('')
    const nextErrors = validate()
    if (Object.keys(nextErrors).length) {
      setErrors(nextErrors)
      return
    }

    try {
      await onSubmit(values, {
        setErrors,
        setFormError,
      })
    } catch (error) {
      const normalized = error.normalized || {}
      setErrors(normalized.errors || {})
      setFormError(normalized.message || 'Could not save this job.')
    }
  }

  return (
    <form className="job-form" onSubmit={handleSubmit} noValidate>
      {formError ? <p className="field__error" role="alert">{formError}</p> : null}

      <Input
        label="Title *"
        name="title"
        value={values.title}
        onChange={updateField}
        error={errors.title}
        required
      />

      <div className="job-form__row">
        <Input
          label="Department"
          name="department"
          value={values.department}
          onChange={updateField}
          error={errors.department}
        />
        <Input
          label="Location"
          name="location"
          value={values.location}
          onChange={updateField}
          error={errors.location}
        />
      </div>

      <Select
        label="Employment type *"
        name="employment_type"
        value={values.employment_type}
        onChange={updateField}
        error={errors.employment_type}
        required
      >
        {EMPLOYMENT_TYPES.map((item) => (
          <option key={item.value} value={item.value}>
            {item.label}
          </option>
        ))}
      </Select>

      <Textarea
        label="Description *"
        name="description"
        rows={6}
        value={values.description}
        onChange={updateField}
        error={errors.description}
        required
      />

      <Textarea
        label="Requirements"
        name="requirements"
        rows={4}
        value={values.requirements}
        onChange={updateField}
        error={errors.requirements}
      />

      <Textarea
        label="Responsibilities"
        name="responsibilities"
        rows={4}
        value={values.responsibilities}
        onChange={updateField}
        error={errors.responsibilities}
      />

      <div className="job-form__row">
        <Input
          label="Salary min"
          name="salary_min"
          type="number"
          min="0"
          value={values.salary_min}
          onChange={updateField}
          error={errors.salary_min}
        />
        <Input
          label="Salary max"
          name="salary_max"
          type="number"
          min="0"
          value={values.salary_max}
          onChange={updateField}
          error={errors.salary_max}
        />
      </div>

      <div className="job-form__row">
        <Input
          label="Experience min (years)"
          name="experience_min"
          type="number"
          min="0"
          max="50"
          value={values.experience_min}
          onChange={updateField}
          error={errors.experience_min}
        />
        <Input
          label="Experience max (years)"
          name="experience_max"
          type="number"
          min="0"
          max="50"
          value={values.experience_max}
          onChange={updateField}
          error={errors.experience_max}
        />
      </div>

      <Input
        label="Closing date"
        name="closing_at"
        type="date"
        value={values.closing_at}
        onChange={updateField}
        error={errors.closing_at}
      />

      <div className="job-form__actions">
        <Button type="submit" loading={submitting}>
          {submitLabel}
        </Button>
      </div>
    </form>
  )
}
