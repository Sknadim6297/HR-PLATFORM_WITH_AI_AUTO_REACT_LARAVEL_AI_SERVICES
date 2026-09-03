/**
 * Normalize Axios / API errors into a consistent shape for UI use.
 * Never surfaces stack traces or provider internals.
 */
export function normalizeApiError(error) {
  const status = error?.response?.status ?? null
  const data = error?.response?.data

  if (error?.code === 'ECONNABORTED') {
    return {
      status: null,
      message: 'The request timed out. Please try again.',
      errors: {},
      raw: null,
    }
  }

  if (!error?.response) {
    return {
      status: null,
      message: 'We could not reach the server. Make sure Apache/XAMPP is running and try again.',
      errors: {},
      raw: null,
    }
  }

  const fieldErrors = normalizeValidationErrors(data?.errors)
  const message = pickMessage(status, data, fieldErrors)

  return {
    status,
    message,
    errors: fieldErrors,
    raw: typeof data === 'object' ? data : null,
  }
}

function normalizeValidationErrors(errors) {
  if (!errors || typeof errors !== 'object') {
    return {}
  }

  return Object.fromEntries(
    Object.entries(errors).map(([field, messages]) => [
      field,
      Array.isArray(messages) ? messages[0] : String(messages),
    ]),
  )
}

function pickMessage(status, data, fieldErrors) {
  if (typeof data?.message === 'string' && data.message.trim()) {
    return data.message
  }

  const firstFieldError = Object.values(fieldErrors)[0]
  if (firstFieldError) {
    return firstFieldError
  }

  switch (status) {
    case 401:
      return 'Your session has expired. Please sign in again.'
    case 403:
      return 'You do not have permission to perform this action.'
    case 404:
      return 'The requested resource was not found.'
    case 409:
      return 'This action conflicts with the current state.'
    case 422:
      return 'Please correct the highlighted fields and try again.'
    case 429:
      return 'Too many requests. Please wait a moment and try again.'
    case 500:
      return 'Something went wrong on our side. Please try again later.'
    case 502:
      return 'An upstream service is temporarily unavailable. Please try again.'
    default:
      return 'Something went wrong. Please try again.'
  }
}
