import { Navigate, Outlet, useLocation } from 'react-router-dom'
import { useAuth } from '../hooks/useAuth'
import { Spinner } from '../components/ui/Spinner'

export function GuestRoute() {
  const { isAuthenticated, bootstrapping, homePath } = useAuth()
  const location = useLocation()

  if (bootstrapping) {
    return (
      <div className="boot-screen">
        <Spinner label="Loading…" />
      </div>
    )
  }

  if (isAuthenticated) {
    const redirectTo = location.state?.from && location.state.from !== '/login'
      ? location.state.from
      : homePath
    return <Navigate to={redirectTo} replace />
  }

  return <Outlet />
}
