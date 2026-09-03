import { useState } from 'react'
import { Outlet, useNavigate } from 'react-router-dom'
import { useAuth } from '../hooks/useAuth'
import { useToast } from '../hooks/useToast'
import { AppShell } from '../components/layout/AppShell'

const NAV = {
  admin: [
    { to: '/admin', label: 'Overview' },
    { to: '/admin/jobs', label: 'Jobs' },
    { to: '/admin/applications', label: 'Applications' },
  ],
  hr: [
    { to: '/hr', label: 'Overview' },
    { to: '/hr/jobs', label: 'Jobs' },
    { to: '/hr/applications', label: 'Applications' },
  ],
  candidate: [
    { to: '/candidate', label: 'Overview' },
    { to: '/candidate/jobs', label: 'Browse jobs' },
    { to: '/candidate/applications', label: 'My applications' },
    { to: '/candidate/profile', label: 'Profile' },
  ],
}

const LABELS = {
  admin: 'Admin',
  hr: 'HR team',
  candidate: 'My applications',
}

export function RoleLayout({ role }) {
  const { user, logout } = useAuth()
  const toast = useToast()
  const navigate = useNavigate()
  const [mobileOpen, setMobileOpen] = useState(false)

  async function handleLogout() {
    await logout()
    toast.info('Signed out successfully.')
    navigate('/login', { replace: true })
  }

  return (
    <AppShell
      brand={LABELS[role]}
      navItems={NAV[role]}
      user={user}
      notificationCount={0}
      onLogout={handleLogout}
      mobileOpen={mobileOpen}
      onToggleMobile={setMobileOpen}
    >
      <Outlet />
    </AppShell>
  )
}

export function AdminLayout() {
  return <RoleLayout role="admin" />
}

export function HrLayout() {
  return <RoleLayout role="hr" />
}

export function CandidateLayout() {
  return <RoleLayout role="candidate" />
}
