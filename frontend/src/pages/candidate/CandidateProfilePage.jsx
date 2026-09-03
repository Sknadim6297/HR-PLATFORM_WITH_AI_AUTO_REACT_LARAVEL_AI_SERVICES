import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import * as candidatesApi from '../../api/candidates'
import { Alert } from '../../components/ui/Alert'
import { Button } from '../../components/ui/Button'
import { Card } from '../../components/ui/Card'
import { ErrorState } from '../../components/ui/ErrorState'
import { Input } from '../../components/ui/Input'
import { Spinner } from '../../components/ui/Spinner'
import { Textarea } from '../../components/ui/Textarea'
import { useCandidateProfile } from '../../hooks/useCandidateProfile'
import { useToast } from '../../hooks/useToast'

const EMPTY = {
  phone: '',
  location: '',
  headline: '',
  years_of_experience: '',
  current_company: '',
  current_role: '',
  education_summary: '',
  skillsText: '',
}

function profileToForm(profile) {
  if (!profile) return { ...EMPTY }
  return {
    phone: profile.phone || '',
    location: profile.location || '',
    headline: profile.headline || '',
    years_of_experience:
      profile.years_of_experience != null ? String(profile.years_of_experience) : '',
    current_company: profile.current_company || '',
    current_role: profile.current_role || '',
    education_summary: profile.education_summary || '',
    skillsText: Array.isArray(profile.skills) ? profile.skills.join(', ') : '',
  }
}

export default function CandidateProfilePage() {
  const toast = useToast()
  const { profile, setProfile, loading, error, missing, refresh } = useCandidateProfile()
  const [form, setForm] = useState(EMPTY)
  const [fieldErrors, setFieldErrors] = useState({})
  const [formError, setFormError] = useState('')
  const [saving, setSaving] = useState(false)

  useEffect(() => {
    const tick = window.setTimeout(() => {
      setForm(profileToForm(profile))
    }, 0)
    return () => window.clearTimeout(tick)
  }, [profile])

  function updateField(name, value) {
    setForm((current) => ({ ...current, [name]: value }))
  }

  async function handleSubmit(event) {
    event.preventDefault()
    setSaving(true)
    setFieldErrors({})
    setFormError('')

    const skills = form.skillsText
      .split(',')
      .map((item) => item.trim())
      .filter(Boolean)

    const payload = {
      phone: form.phone.trim() || null,
      location: form.location.trim() || null,
      headline: form.headline.trim() || null,
      years_of_experience:
        form.years_of_experience === '' ? null : Number(form.years_of_experience),
      current_company: form.current_company.trim() || null,
      current_role: form.current_role.trim() || null,
      education_summary: form.education_summary.trim() || null,
      skills,
    }

    try {
      const response = await candidatesApi.updateProfile(payload)
      setProfile(response.data)
      toast.success('Profile saved.')
    } catch (err) {
      setFieldErrors(err.normalized?.errors || {})
      setFormError(err.normalized?.message || 'Could not save your profile.')
    } finally {
      setSaving(false)
    }
  }

  if (loading) {
    return (
      <div className="page">
        <Spinner label="Loading profile…" />
      </div>
    )
  }

  if (error) {
    return (
      <div className="page">
        <ErrorState description={error} onRetry={refresh} />
      </div>
    )
  }

  return (
    <div className="page">
      <header className="page__header">
        <div>
          <p className="eyebrow">Profile</p>
          <h1>Your candidate profile</h1>
          <p>
            {missing
              ? 'Create a profile so recruiters understand your background.'
              : 'Keep your contact details and experience up to date.'}
          </p>
        </div>
      </header>

      <Card>
        <form className="job-form" onSubmit={handleSubmit} noValidate>
          {formError ? <Alert tone="error">{formError}</Alert> : null}

          <Input
            label="Headline"
            name="headline"
            value={form.headline}
            onChange={(event) => updateField('headline', event.target.value)}
            error={fieldErrors.headline}
            placeholder="e.g. Frontend engineer"
          />

          <div className="job-form__row">
            <Input
              label="Phone"
              name="phone"
              value={form.phone}
              onChange={(event) => updateField('phone', event.target.value)}
              error={fieldErrors.phone}
            />
            <Input
              label="Location"
              name="location"
              value={form.location}
              onChange={(event) => updateField('location', event.target.value)}
              error={fieldErrors.location}
            />
          </div>

          <div className="job-form__row">
            <Input
              label="Years of experience"
              name="years_of_experience"
              type="number"
              min="0"
              max="50"
              value={form.years_of_experience}
              onChange={(event) => updateField('years_of_experience', event.target.value)}
              error={fieldErrors.years_of_experience}
            />
            <Input
              label="Current role"
              name="current_role"
              value={form.current_role}
              onChange={(event) => updateField('current_role', event.target.value)}
              error={fieldErrors.current_role}
            />
          </div>

          <Input
            label="Current company"
            name="current_company"
            value={form.current_company}
            onChange={(event) => updateField('current_company', event.target.value)}
            error={fieldErrors.current_company}
          />

          <Textarea
            label="Education summary"
            name="education_summary"
            rows={4}
            value={form.education_summary}
            onChange={(event) => updateField('education_summary', event.target.value)}
            error={fieldErrors.education_summary}
          />

          <Input
            label="Skills"
            name="skills"
            value={form.skillsText}
            onChange={(event) => updateField('skillsText', event.target.value)}
            error={fieldErrors.skills || fieldErrors['skills.0']}
            hint="Comma-separated, e.g. React, Laravel, SQL"
          />

          <div className="job-form__actions">
            <Button type="submit" loading={saving}>
              Save profile
            </Button>
          </div>
        </form>
      </Card>

      <p className="muted">
        <Link to="/candidate">Back to overview</Link>
      </p>
    </div>
  )
}
