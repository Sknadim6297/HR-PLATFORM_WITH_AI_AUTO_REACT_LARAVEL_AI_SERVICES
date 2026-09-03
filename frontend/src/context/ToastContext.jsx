import { useCallback, useMemo, useState } from 'react'
import { ToastContext } from './toast-context'

let toastId = 0

export function ToastProvider({ children }) {
  const [toasts, setToasts] = useState([])

  const dismiss = useCallback((id) => {
    setToasts((current) => current.filter((toast) => toast.id !== id))
  }, [])

  const push = useCallback(
    (type, message, options = {}) => {
      const id = ++toastId
      const toast = {
        id,
        type,
        message,
        duration: options.duration ?? 4000,
      }
      setToasts((current) => [...current, toast])
      window.setTimeout(() => dismiss(id), toast.duration)
      return id
    },
    [dismiss],
  )

  const value = useMemo(
    () => ({
      toasts,
      dismiss,
      success: (message, options) => push('success', message, options),
      error: (message, options) => push('error', message, options),
      warning: (message, options) => push('warning', message, options),
      info: (message, options) => push('info', message, options),
    }),
    [toasts, dismiss, push],
  )

  return (
    <ToastContext.Provider value={value}>
      {children}
      <div className="toast-viewport" aria-live="polite" aria-relevant="additions">
        {toasts.map((toast) => (
          <div key={toast.id} className={`toast toast--${toast.type}`} role="status">
            <p>{toast.message}</p>
            <button type="button" className="toast__close" onClick={() => dismiss(toast.id)} aria-label="Dismiss notification">
              ×
            </button>
          </div>
        ))}
      </div>
    </ToastContext.Provider>
  )
}
