import { Link } from 'react-router-dom'
import { ApplicationFilters } from '../../components/applications/ApplicationFilters'
import { ApplicationList } from '../../components/applications/ApplicationList'
import { Button } from '../../components/ui/Button'
import { ErrorState } from '../../components/ui/ErrorState'
import { Pagination } from '../../components/ui/Pagination'
import { Spinner } from '../../components/ui/Spinner'
import { useApplications } from '../../hooks/useApplications'

export function ApplicationsIndexPage({
  basePath,
  title,
  subtitle,
  forStaff = false,
  showScoreFilters = false,
  emptyBrowseTo,
}) {
  const {
    applications,
    meta,
    loading,
    error,
    filters,
    searchInput,
    setSearchInput,
    setPage,
    setFilter,
    clearFilters,
    refresh,
  } = useApplications()

  const statusTabs = [
    ['All', ''],
    ['Screening', 'screening'],
    ['Shortlisted', 'shortlisted'],
    ['Interview', 'interview'],
    ['Selected', 'selected'],
    ['Rejected', 'rejected'],
  ]

  return (
    <div className="page">
      <header className="page__header">
        <div>
          <p className="eyebrow">Applications</p>
          <h1>{title}</h1>
          <p>{subtitle}</p>
        </div>
      </header>

      {forStaff ? (
        <div className="application-tabs" role="tablist" aria-label="Application status filters">
          {statusTabs.map(([label, value]) => (
            <Button
              key={value || 'all'}
              type="button"
              variant={filters.status === value ? 'primary' : 'ghost'}
              onClick={() => setFilter('status', value)}
            >
              {label}
            </Button>
          ))}
        </div>
      ) : null}

      <ApplicationFilters
        searchInput={searchInput}
        onSearchChange={setSearchInput}
        filters={filters}
        onFilterChange={setFilter}
        onClear={clearFilters}
        showScoreFilters={showScoreFilters}
        forStaff={forStaff}
      />

      {loading ? <Spinner label="Loading applications…" /> : null}
      {error ? <ErrorState description={error} onRetry={refresh} /> : null}

      {!loading && !error ? (
        <>
          <ApplicationList
            applications={applications}
            basePath={basePath}
            forStaff={forStaff}
            emptyAction={
              emptyBrowseTo ? (
                <Link to={emptyBrowseTo}>
                  <Button type="button" variant="secondary">
                    Browse jobs
                  </Button>
                </Link>
              ) : null
            }
          />
          <Pagination meta={meta} onPageChange={setPage} />
        </>
      ) : null}

      <p className="muted">
        <Link to={basePath}>Back to overview</Link>
      </p>
    </div>
  )
}
