import { Component } from 'react'
import { Button } from '../ui/Button'

export class ErrorBoundary extends Component {
  constructor(props) {
    super(props)
    this.state = { hasError: false }
  }

  static getDerivedStateFromError() {
    return { hasError: true }
  }

  render() {
    if (this.state.hasError) {
      return (
        <div className="centered-page">
          <h1>Unexpected error</h1>
          <p>Something went wrong while rendering this page.</p>
          <Button type="button" onClick={() => window.location.assign('/')}>
            Reload application
          </Button>
        </div>
      )
    }

    return this.props.children
  }
}
