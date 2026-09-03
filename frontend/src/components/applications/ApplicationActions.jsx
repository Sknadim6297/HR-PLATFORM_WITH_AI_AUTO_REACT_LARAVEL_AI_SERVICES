import { useState } from 'react'
import * as applicationsApi from '../../api/applications'
import { applicationStatusLabel, nextStatusesFor } from '../../constants/applications'
import { Button } from '../ui/Button'
import { ConfirmDialog } from '../ui/Modal'
import { Select } from '../ui/Select'
import { useToast } from '../../hooks/useToast'

export function ApplicationActions({ application, onUpdated, disabled = false }) {
  const toast = useToast()
  const [nextStatus, setNextStatus] = useState('')
  const [confirmOpen, setConfirmOpen] = useState(false)
  const [busy, setBusy] = useState(false)

  const options = nextStatusesFor(application?.status, { forStaff: true })

  if (!application || options.length === 0) {
    return (
      <p className="muted">
        No further status changes are available from {applicationStatusLabel(application?.status)}.
      </p>
    )
  }

  async function confirmTransition() {
    if (!nextStatus) return
    setBusy(true)
    try {
      const response = await applicationsApi.updateApplicationStatus(application.id, nextStatus)
      toast.success('Application status updated.')
      onUpdated?.(response.data)
      setNextStatus('')
      setConfirmOpen(false)
    } catch (err) {
      toast.error(err.normalized?.message || 'Could not update status.')
    } finally {
      setBusy(false)
    }
  }

  return (
    <div className="application-actions">
      <Select
        label="Move to"
        name="next_status"
        value={nextStatus}
        disabled={disabled || busy}
        onChange={(event) => setNextStatus(event.target.value)}
      >
        <option value="">Select status</option>
        {options.map((status) => (
          <option key={status} value={status}>
            {applicationStatusLabel(status)}
          </option>
        ))}
      </Select>
      <Button
        type="button"
        disabled={!nextStatus || disabled || busy}
        onClick={() => setConfirmOpen(true)}
      >
        Update status
      </Button>

      <ConfirmDialog
        open={confirmOpen}
        title={`Move this candidate to ${applicationStatusLabel(nextStatus)}?`}
        description={`Current status is ${applicationStatusLabel(application.status)}. This updates the application for everyone who can see it.`}
        confirmLabel={`Move to ${applicationStatusLabel(nextStatus)}`}
        loading={busy}
        onClose={() => (busy ? null : setConfirmOpen(false))}
        onConfirm={confirmTransition}
      />
    </div>
  )
}
