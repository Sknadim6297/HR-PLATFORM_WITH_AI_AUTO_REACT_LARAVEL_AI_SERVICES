import { Navigate, Outlet } from 'react-router-dom'
import { useAuth } from '../hooks/useAuth'
import { ROLE_HOME } from '../constants/roles'

export function RoleRoute({ allow }) {
  const { user, homePath } = useAuth()
  const allowed = Array.isArray(allow) ? allow : [allow]

  if (!user?.role || !allowed.includes(user.role)) {
    if (user?.role && ROLE_HOME[user.role]) {
      return <Navigate to={homePath} replace />
    }
    return <Navigate to="/403" replace />
  }

  return <Outlet />
}
