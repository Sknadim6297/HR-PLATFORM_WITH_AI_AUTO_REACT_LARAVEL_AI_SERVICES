import { AuthProvider } from './context/AuthContext'
import { ToastProvider } from './context/ToastContext'
import { ErrorBoundary } from './components/feedback/ErrorBoundary'
import { AppRoutes } from './routes/AppRoutes'

export default function App() {
  return (
    <ErrorBoundary>
      <AuthProvider>
        <ToastProvider>
          <AppRoutes />
        </ToastProvider>
      </AuthProvider>
    </ErrorBoundary>
  )
}
