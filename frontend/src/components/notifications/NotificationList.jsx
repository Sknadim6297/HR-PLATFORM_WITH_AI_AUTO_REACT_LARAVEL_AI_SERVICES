import { Link } from 'react-router-dom'
import { formatJobDate } from '../../constants/jobs'

function notificationMessage(item) {
  const data = item.data || {}
  if (typeof data.message === 'string' && data.message.trim()) return data.message
  if (typeof data.event === 'string') return data.event.replaceAll('_', ' ')
  return item.type?.split('\\').pop() || 'Notification'
}

export function NotificationList({
  notifications,
  emptyLabel = 'No notifications yet.',
  applicationBasePath = '/candidate',
}) {
  if (!notifications?.length) {
    return <p className="muted">{emptyLabel}</p>
  }

  return (
    <ul className="notification-list">
      {notifications.map((item) => {
        const data = item.data || {}
        const applicationId = data.application_id
        return (
          <li key={item.id} className="notification-list__item">
            <p>{notificationMessage(item)}</p>
            <p className="muted">{formatJobDate(item.created_at)}</p>
            {applicationId ? (
              <Link className="text-link" to={`${applicationBasePath}/applications/${applicationId}`}>
                View application
              </Link>
            ) : null}
          </li>
        )
      })}
    </ul>
  )
}
