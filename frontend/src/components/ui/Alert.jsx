export function Alert({ tone = 'info', children }) {
  return (
    <div className={`alert alert--${tone}`} role="status">
      {children}
    </div>
  )
}
