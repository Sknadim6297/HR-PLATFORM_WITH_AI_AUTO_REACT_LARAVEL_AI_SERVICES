import { Link } from 'react-router-dom'
import { useAuth } from '../../hooks/useAuth'

export function ForbiddenPage() {
  const { homePath, isAuthenticated } = useAuth()

  return (
    <div className="centered-page">
      <p className="eyebrow">403</p>
      <h1>Access denied</h1>
      <p>You do not have permission to view this area.</p>
      <Link className="text-link" to={isAuthenticated ? homePath : '/login'}>
        {isAuthenticated ? 'Back to your workspace' : 'Sign in'}
      </Link>
    </div>
  )
}
