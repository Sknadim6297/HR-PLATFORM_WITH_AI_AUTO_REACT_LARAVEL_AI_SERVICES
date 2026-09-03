import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { useAuth } from '../../hooks/useAuth'
import { useToast } from '../../hooks/useToast'
import { ROLE_HOME } from '../../constants/roles'
import { Alert } from '../../components/ui/Alert'
import { Button } from '../../components/ui/Button'
import { Input } from '../../components/ui/Input'
import { APP_NAME } from '../../constants/roles'

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
      <div className="auth-screen__panel">
        <p className="eyebrow">{APP_NAME}</p>
        <h1>Welcome back</h1>
        <p className="auth-screen__lede">
          Sign in to continue. Your role comes from your account — you do not pick it here.
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
      <aside className="auth-screen__aside" aria-hidden="true">
        <div className="auth-screen__glow" />
        <h2>Hiring that stays organized</h2>
        <p>Post roles, review applications, and keep candidate updates in one place.</p>
      </aside>
    </div>
  )
}
