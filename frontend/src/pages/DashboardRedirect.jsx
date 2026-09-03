import { Navigate } from 'react-router-dom'
import { useAuth } from '../hooks/useAuth'
import { ROLE_HOME } from '../constants/roles'
import { Alert } from '../components/ui/Alert'

export function DashboardRedirect() {
  const { user, homePath } = useAuth()

  if (user?.role && ROLE_HOME[user.role]) {
    return <Navigate to={homePath} replace />
  }

  return (
    <div className="centered-page">
      <Alert tone="error">Your account role is missing or invalid. Please contact an administrator.</Alert>
    </div>
  )
}
