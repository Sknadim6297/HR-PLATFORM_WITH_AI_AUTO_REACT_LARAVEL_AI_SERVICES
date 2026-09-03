export const JOB_STATUSES = [
  { value: 'draft', label: 'Draft' },
  { value: 'published', label: 'Published' },
  { value: 'closed', label: 'Closed' },
  { value: 'archived', label: 'Archived' },
]

export const EMPLOYMENT_TYPES = [
  { value: 'full_time', label: 'Full time' },
  { value: 'part_time', label: 'Part time' },
  { value: 'contract', label: 'Contract' },
  { value: 'internship', label: 'Internship' },
]

export function employmentTypeLabel(value) {
  return EMPLOYMENT_TYPES.find((item) => item.value === value)?.label || value || '—'
}

export function jobStatusLabel(value) {
  return JOB_STATUSES.find((item) => item.value === value)?.label || value || '—'
}

export function formatJobDate(value) {
  if (!value) return '—'
  try {
    return new Intl.DateTimeFormat(undefined, {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
    }).format(new Date(value))
  } catch {
    return '—'
  }
}

export function formatSalaryRange(min, max) {
  if (min == null && max == null) return null
  const fmt = (n) => new Intl.NumberFormat(undefined, { maximumFractionDigits: 0 }).format(n)
  if (min != null && max != null) return `${fmt(min)} – ${fmt(max)}`
  if (min != null) return `From ${fmt(min)}`
  return `Up to ${fmt(max)}`
}

export function emptyJobForm() {
  return {
    title: '',
    department: '',
    description: '',
    requirements: '',
    responsibilities: '',
    employment_type: 'full_time',
    location: '',
    salary_min: '',
    salary_max: '',
    experience_min: '',
    experience_max: '',
    closing_at: '',
  }
}

export function jobToFormValues(job) {
  return {
    title: job.title || '',
    department: job.department || '',
    description: job.description || '',
    requirements: job.requirements || '',
    responsibilities: job.responsibilities || '',
    employment_type: job.employment_type || 'full_time',
    location: job.location || '',
    salary_min: job.salary_min ?? '',
    salary_max: job.salary_max ?? '',
    experience_min: job.experience_min ?? '',
    experience_max: job.experience_max ?? '',
    closing_at: job.closing_at ? String(job.closing_at).slice(0, 10) : '',
  }
}

/** Build API payload from form values; omit empty optional numbers/dates. */
export function formValuesToPayload(values) {
  const payload = {
    title: values.title.trim(),
    department: values.department.trim() || null,
    description: values.description.trim(),
    requirements: values.requirements.trim() || null,
    responsibilities: values.responsibilities.trim() || null,
    employment_type: values.employment_type,
    location: values.location.trim() || null,
  }

  const optionalInts = ['salary_min', 'salary_max', 'experience_min', 'experience_max']
  for (const key of optionalInts) {
    const raw = values[key]
    if (raw === '' || raw === null || raw === undefined) {
      payload[key] = null
    } else {
      payload[key] = Number(raw)
    }
  }

  payload.closing_at = values.closing_at ? values.closing_at : null

  return payload
}
