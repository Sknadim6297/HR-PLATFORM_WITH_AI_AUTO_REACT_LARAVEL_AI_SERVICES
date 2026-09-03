export function Input({
  id,
  label,
  error,
  type = 'text',
  className = '',
  hint,
  ...props
}) {
  const inputId = id || props.name

  return (
    <label className={`field ${className}`.trim()} htmlFor={inputId}>
      {label ? <span className="field__label">{label}</span> : null}
      <input id={inputId} type={type} className={`field__control ${error ? 'is-invalid' : ''}`} aria-invalid={Boolean(error)} {...props} />
      {hint && !error ? <span className="field__hint">{hint}</span> : null}
      {error ? <span className="field__error" role="alert">{error}</span> : null}
    </label>
  )
}
