import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { useAuth } from '../../hooks/useAuth'
import { useToast } from '../../hooks/useToast'
import { APP_NAME, ROLE_HOME } from '../../constants/roles'
import { Alert } from '../../components/ui/Alert'
import { Button } from '../../components/ui/Button'
import { Input } from '../../components/ui/Input'

export function RegisterPage() {
  const { register, authBusy } = useAuth()
  const toast = useToast()
  const navigate = useNavigate()
  const [form, setForm] = useState({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
  })
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
    if (!form.name.trim()) nextErrors.name = 'Please enter your name.'
    if (!form.email.trim()) nextErrors.email = 'Please enter your email.'
    if (form.password.length < 8) nextErrors.password = 'Use at least 8 characters.'
    if (form.password !== form.password_confirmation) {
      nextErrors.password_confirmation = 'Those passwords do not match.'
    }
    if (Object.keys(nextErrors).length) {
      setErrors(nextErrors)
      return
    }

    try {
      const user = await register(form)
      const destination = ROLE_HOME[user?.role] || '/candidate'
      toast.success('You are in. Let’s find a role that fits.')
      navigate(destination, { replace: true })
    } catch (error) {
      const normalized = error.normalized || {}
      setErrors(normalized.errors || {})
      setFormError(normalized.message || 'We could not create your account right now.')
    }
  }

  return (
    <div className="auth-screen">
      <div className="auth-screen__panel">
        <p className="eyebrow">{APP_NAME}</p>
        <h1>Join as a candidate</h1>
        <p className="auth-screen__lede">
          Set up your account to browse openings and apply. HR and admin access are handled by your company.
        </p>

        {formError ? <Alert tone="error">{formError}</Alert> : null}

        <form className="auth-form" onSubmit={handleSubmit} noValidate>
          <Input label="Full name" name="name" autoComplete="name" value={form.name} onChange={updateField} error={errors.name} required />
          <Input label="Email" name="email" type="email" autoComplete="email" value={form.email} onChange={updateField} error={errors.email} required />
          <Input label="Password" name="password" type="password" autoComplete="new-password" value={form.password} onChange={updateField} error={errors.password} required />
          <Input
            label="Confirm password"
            name="password_confirmation"
            type="password"
            autoComplete="new-password"
            value={form.password_confirmation}
            onChange={updateField}
            error={errors.password_confirmation}
            required
          />
          <Button type="submit" loading={authBusy} className="auth-form__submit">
            Create account
          </Button>
        </form>

        <p className="auth-screen__footer">
          Already have an account? <Link to="/login">Sign in</Link>
        </p>
      </div>
      <aside className="auth-screen__aside" aria-hidden="true">
        <div className="auth-screen__glow" />
        <h2>Apply once. Stay updated.</h2>
        <p>Keep your profile ready, send applications, and follow progress without chasing email threads.</p>
      </aside>
    </div>
  )
}
