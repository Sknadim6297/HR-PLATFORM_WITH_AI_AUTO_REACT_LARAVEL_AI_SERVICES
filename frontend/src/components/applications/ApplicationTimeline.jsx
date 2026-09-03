import { applicationStatusLabel } from '../../constants/applications'

const PIPELINE = ['applied', 'screening', 'shortlisted', 'interview', 'selected']

export function ApplicationTimeline({ status }) {
  const isTerminalSide = status === 'rejected' || status === 'withdrawn'
  const currentIndex = PIPELINE.indexOf(status)

  return (
    <ol className="application-timeline">
      {PIPELINE.map((step, index) => {
        let state = 'upcoming'
        if (isTerminalSide) {
          state = index === 0 ? 'done' : 'muted'
        } else if (currentIndex > index) {
          state = 'done'
        } else if (currentIndex === index) {
          state = 'current'
        }

        return (
          <li key={step} className={`application-timeline__item application-timeline__item--${state}`}>
            <span className="application-timeline__dot" aria-hidden="true" />
            <span>{applicationStatusLabel(step)}</span>
          </li>
        )
      })}
      {isTerminalSide ? (
        <li className="application-timeline__item application-timeline__item--current">
          <span className="application-timeline__dot" aria-hidden="true" />
          <span>{applicationStatusLabel(status)}</span>
        </li>
      ) : null}
    </ol>
  )
}
