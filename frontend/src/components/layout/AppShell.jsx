import { NavLink } from 'react-router-dom'
import { APP_NAME } from '../../constants/roles'
import { Badge } from '../ui/Badge'
import { Button } from '../ui/Button'

export function AppShell({
  brand,
  navItems,
  user,
  notificationCount = 0,
  onLogout,
  mobileOpen,
  onToggleMobile,
  children,
}) {
  return (
    <div className={`app-shell ${mobileOpen ? 'is-nav-open' : ''}`}>
      <aside className="app-shell__sidebar" aria-label="Primary">
        <div className="app-shell__brand">
          <span className="brand-mark" aria-hidden="true">
            HF
          </span>
          <div>
            <p className="brand-name">{APP_NAME}</p>
            <p className="brand-role">{brand}</p>
          </div>
        </div>

        <nav className="app-shell__nav">
          {navItems.map((item) => (
            <NavLink
              key={item.to}
              to={item.to}
              className={({ isActive }) => `nav-link ${isActive ? 'is-active' : ''}`}
              onClick={() => onToggleMobile(false)}
            >
              {item.label}
            </NavLink>
          ))}
        </nav>
      </aside>

      {mobileOpen ? (
        <button
          type="button"
          className="app-shell__scrim"
          aria-label="Close navigation"
          onClick={() => onToggleMobile(false)}
        />
      ) : null}

      <div className="app-shell__main">
        <header className="app-shell__header">
          <button
            type="button"
            className="icon-button app-shell__menu"
            aria-label={mobileOpen ? 'Close menu' : 'Open menu'}
            aria-expanded={mobileOpen}
            onClick={() => onToggleMobile(!mobileOpen)}
          >
            ☰
          </button>

          <div className="app-shell__header-meta">
            <Badge tone="info">{notificationCount} notifications</Badge>
            <div className="user-chip">
              <div>
                <p className="user-chip__name">{user?.name}</p>
                <p className="user-chip__email">{user?.email}</p>
              </div>
              <Button type="button" variant="ghost" onClick={onLogout}>
                Sign out
              </Button>
            </div>
          </div>
        </header>

        <main className="app-shell__content">{children}</main>
      </div>
    </div>
  )
}
