import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { useAuth } from '../../hooks/useAuth'
import { useToast } from '../../hooks/useToast'
import { ROLE_HOME, APP_NAME } from '../../constants/roles'
import { Alert } from '../../components/ui/Alert'
import { Button } from '../../components/ui/Button'
import { Input } from '../../components/ui/Input'

export function LoginPage() {
  const { login, authBusy } = useAuth()
  const toast = useToast()
  const navigate = useNavigate()
  const [form, setForm] = useState({ email: '', password: '' })
  const [showPassword, setShowPassword] = useState(false)
  const [errors, setErrors] = useState({})
  const [formError, setFormError] = useState('')

  function updateField(event) {
    const { name, value } = event.target
    setForm((current) => ({ ...current, [name]: value }))
    setErrors((current) => ({ ...current, [name]: undefined }))
  }

  async function handleSubmit(event) {
    event.preventDefault()
    setFormError('')

    const nextErrors = {}
    if (!form.email.trim()) nextErrors.email = 'Please enter your email.'
    if (!form.password) nextErrors.password = 'Please enter your password.'
    if (Object.keys(nextErrors).length) {
      setErrors(nextErrors)
      return
    }

    try {
      const user = await login(form)
      const destination = ROLE_HOME[user?.role]
      if (!destination) {
        setFormError('We could not open your workspace. Ask an admin to check your account role.')
        return
      }
      toast.success(`Welcome back${user?.name ? `, ${user.name.split(' ')[0]}` : ''}.`)
      navigate(destination, { replace: true })
    } catch (error) {
      const normalized = error.normalized || {}
      setErrors(normalized.errors || {})
      setFormError(normalized.message || 'Login failed. Double-check your email and password.')
    }
  }

  return (
    <div className="auth-screen">
      <aside className="auth-screen__hero" aria-hidden="true">
        <div className="auth-screen__hero-shade" />
        <div className="auth-screen__hero-content">
          <p className="auth-screen__mark">HF</p>
          <h1 className="auth-screen__brand">{APP_NAME}</h1>
          <p className="auth-screen__tagline">
            Post roles, review applications, and keep hiring updates in one place.
          </p>
        </div>
      </aside>

      <main className="auth-screen__panel">
        <div className="auth-screen__panel-inner">
          <p className="auth-screen__mobile-brand">{APP_NAME}</p>
          <h2 className="auth-screen__title">Welcome back</h2>
          <p className="auth-screen__lede">
            Sign in to continue. Your workspace opens from your account role.
          </p>

          {formError ? <Alert tone="error">{formError}</Alert> : null}

          <form className="auth-form" onSubmit={handleSubmit} noValidate>
            <Input
              label="Work email"
              name="email"
              type="email"
              autoComplete="email"
              value={form.email}
              onChange={updateField}
              error={errors.email}
              required
            />

            <div className="password-field">
              <Input
                label="Password"
                name="password"
                type={showPassword ? 'text' : 'password'}
                autoComplete="current-password"
                value={form.password}
                onChange={updateField}
                error={errors.password}
                required
              />
              <button
                type="button"
                className="password-field__toggle"
                onClick={() => setShowPassword((value) => !value)}
                aria-label={showPassword ? 'Hide password' : 'Show password'}
              >
                {showPassword ? 'Hide' : 'Show'}
              </button>
            </div>

            <Button type="submit" loading={authBusy} className="auth-form__submit">
              Sign in
            </Button>
          </form>

          <p className="auth-screen__footer">
            Looking for a job? <Link to="/register">Create an account</Link>
          </p>
        </div>
      </main>
    </div>
  )
}
