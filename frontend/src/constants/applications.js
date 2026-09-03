export const APPLICATION_STATUSES = [
  { value: 'applied', label: 'Applied' },
  { value: 'screening', label: 'Screening' },
  { value: 'shortlisted', label: 'Shortlisted' },
  { value: 'interview', label: 'Interview' },
  { value: 'selected', label: 'Selected' },
  { value: 'rejected', label: 'Rejected' },
  { value: 'withdrawn', label: 'Withdrawn' },
]

/** Mirrors backend ApplicationStatus::allowedTransitions for HR/Admin actions. */
export const APPLICATION_TRANSITIONS = {
  applied: ['screening', 'withdrawn'],
  screening: ['shortlisted', 'rejected', 'withdrawn'],
  shortlisted: ['interview', 'rejected', 'withdrawn'],
  interview: ['selected', 'rejected'],
  selected: [],
  rejected: [],
  withdrawn: [],
}

export function applicationStatusLabel(value) {
  return APPLICATION_STATUSES.find((item) => item.value === value)?.label || value || '—'
}

export function nextStatusesFor(current, { forStaff = true } = {}) {
  const next = APPLICATION_TRANSITIONS[current] || []
  if (forStaff) {
    return next.filter((status) => status !== 'withdrawn')
  }
  return next.filter((status) => status === 'withdrawn')
}

export function formatBytes(bytes) {
  if (bytes == null) return '—'
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
}

export const RESUME_ACCEPT = {
  extensions: ['.pdf', '.docx', '.txt'],
  mimeTypes: [
    'application/pdf',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'text/plain',
  ],
  maxBytes: 10 * 1024 * 1024,
}

export function validateResumeFile(file) {
  if (!file) return 'Please choose a resume file.'
  if (file.size > RESUME_ACCEPT.maxBytes) {
    return 'Resume must be 10 MB or smaller.'
  }
  const name = file.name.toLowerCase()
  const okExt = RESUME_ACCEPT.extensions.some((ext) => name.endsWith(ext))
  const okMime = !file.type || RESUME_ACCEPT.mimeTypes.includes(file.type)
  if (!okExt && !okMime) {
    return 'Use a PDF, DOCX, or TXT file.'
  }
  return null
}
