import { Suspense, lazy } from 'react'
import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom'
import { ROLES } from '../constants/roles'
import { AdminLayout, CandidateLayout, HrLayout } from '../layouts/RoleLayouts'
import { GuestRoute } from './GuestRoute'
import { ProtectedRoute } from './ProtectedRoute'
import { RoleRoute } from './RoleRoute'
import { Spinner } from '../components/ui/Spinner'
import { LoginPage } from '../pages/auth/LoginPage'
import { RegisterPage } from '../pages/auth/RegisterPage'
import { ForbiddenPage } from '../pages/errors/ForbiddenPage'
import { NotFoundPage } from '../pages/errors/NotFoundPage'
import { DashboardRedirect } from '../pages/DashboardRedirect'

const AdminDashboard = lazy(() => import('../pages/admin/AdminDashboard'))
const AdminJobsPage = lazy(() => import('../pages/admin/AdminJobsPage'))
const AdminJobCreatePage = lazy(() => import('../pages/admin/AdminJobCreatePage'))
const AdminJobEditPage = lazy(() => import('../pages/admin/AdminJobEditPage'))
const AdminJobDetailsPage = lazy(() => import('../pages/admin/AdminJobDetailsPage'))
const AdminApplicationsPage = lazy(() => import('../pages/admin/AdminApplicationsPage'))
const AdminApplicationDetailsPage = lazy(() => import('../pages/admin/AdminApplicationDetailsPage'))

const HrDashboard = lazy(() => import('../pages/hr/HrDashboard'))
const HrJobsPage = lazy(() => import('../pages/hr/HrJobsPage'))
const HrJobCreatePage = lazy(() => import('../pages/hr/HrJobCreatePage'))
const HrJobEditPage = lazy(() => import('../pages/hr/HrJobEditPage'))
const HrJobDetailsPage = lazy(() => import('../pages/hr/HrJobDetailsPage'))
const HrApplicationsPage = lazy(() => import('../pages/hr/HrApplicationsPage'))
const HrApplicationDetailsPage = lazy(() => import('../pages/hr/HrApplicationDetailsPage'))

const CandidateDashboard = lazy(() => import('../pages/candidate/CandidateDashboard'))
const CandidateJobsPage = lazy(() => import('../pages/candidate/CandidateJobsPage'))
const CandidateJobDetailsPage = lazy(() => import('../pages/candidate/CandidateJobDetailsPage'))
const CandidateApplicationsPage = lazy(() => import('../pages/candidate/CandidateApplicationsPage'))
const CandidateApplicationDetailsPage = lazy(() => import('../pages/candidate/CandidateApplicationDetailsPage'))
const CandidateProfilePage = lazy(() => import('../pages/candidate/CandidateProfilePage'))

function LazyFallback() {
  return (
    <div className="page-loading">
      <Spinner label="Loading…" />
    </div>
  )
}

export function AppRoutes() {
  return (
    <BrowserRouter>
      <Suspense fallback={<LazyFallback />}>
        <Routes>
          <Route element={<GuestRoute />}>
            <Route path="/login" element={<LoginPage />} />
            <Route path="/register" element={<RegisterPage />} />
          </Route>

          <Route element={<ProtectedRoute />}>
            <Route path="/dashboard" element={<DashboardRedirect />} />

            <Route element={<RoleRoute allow={ROLES.ADMIN} />}>
              <Route path="/admin" element={<AdminLayout />}>
                <Route index element={<AdminDashboard />} />
                <Route path="jobs" element={<AdminJobsPage />} />
                <Route path="jobs/create" element={<AdminJobCreatePage />} />
                <Route path="jobs/:id" element={<AdminJobDetailsPage />} />
                <Route path="jobs/:id/edit" element={<AdminJobEditPage />} />
                <Route path="applications" element={<AdminApplicationsPage />} />
                <Route path="applications/:id" element={<AdminApplicationDetailsPage />} />
              </Route>
            </Route>

            <Route element={<RoleRoute allow={ROLES.HR} />}>
              <Route path="/hr" element={<HrLayout />}>
                <Route index element={<HrDashboard />} />
                <Route path="jobs" element={<HrJobsPage />} />
                <Route path="jobs/create" element={<HrJobCreatePage />} />
                <Route path="jobs/:id" element={<HrJobDetailsPage />} />
                <Route path="jobs/:id/edit" element={<HrJobEditPage />} />
                <Route path="applications" element={<HrApplicationsPage />} />
                <Route path="applications/:id" element={<HrApplicationDetailsPage />} />
              </Route>
            </Route>

            <Route element={<RoleRoute allow={ROLES.CANDIDATE} />}>
              <Route path="/candidate" element={<CandidateLayout />}>
                <Route index element={<CandidateDashboard />} />
                <Route path="jobs" element={<CandidateJobsPage />} />
                <Route path="jobs/:id" element={<CandidateJobDetailsPage />} />
                <Route path="applications" element={<CandidateApplicationsPage />} />
                <Route path="applications/:id" element={<CandidateApplicationDetailsPage />} />
                <Route path="profile" element={<CandidateProfilePage />} />
              </Route>
            </Route>
          </Route>

          <Route path="/403" element={<ForbiddenPage />} />
          <Route path="/404" element={<NotFoundPage />} />
          <Route path="/" element={<Navigate to="/dashboard" replace />} />
          <Route path="*" element={<NotFoundPage />} />
        </Routes>
      </Suspense>
    </BrowserRouter>
  )
}
