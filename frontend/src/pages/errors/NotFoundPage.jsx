import { Link } from 'react-router-dom'
import { Button } from '../../components/ui/Button'

export function NotFoundPage() {
  return (
    <div className="centered-page">
      <p className="eyebrow">404</p>
      <h1>Page not found</h1>
      <p>The page you requested does not exist or may have moved.</p>
      <Button type="button" variant="secondary" onClick={() => window.history.back()}>
        Go back
      </Button>
      <Link className="text-link" to="/dashboard">
        Return to dashboard
      </Link>
    </div>
  )
}
