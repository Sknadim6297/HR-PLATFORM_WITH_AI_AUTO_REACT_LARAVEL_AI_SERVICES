import { Link } from 'react-router-dom'
import { CandidateJobFilters } from '../../components/jobs/CandidateJobFilters'
import { CandidateJobList } from '../../components/jobs/CandidateJobList'
import { ErrorState } from '../../components/ui/ErrorState'
import { Pagination } from '../../components/ui/Pagination'
import { Spinner } from '../../components/ui/Spinner'
import { useCandidateJobs } from '../../hooks/useCandidateJobs'

export default function CandidateJobsPage() {
  const {
    jobs,
    meta,
    loading,
    error,
    filters,
    searchInput,
    setSearchInput,
    departmentInput,
    setDepartmentInput,
    setPage,
    setFilter,
    clearFilters,
    refresh,
  } = useCandidateJobs()

  return (
    <div className="page">
      <header className="page__header">
        <div>
          <p className="eyebrow">Jobs</p>
          <h1>Browse openings</h1>
          <p>Published roles you can apply to.</p>
        </div>
      </header>

      <CandidateJobFilters
        searchInput={searchInput}
        onSearchChange={setSearchInput}
        departmentInput={departmentInput}
        onDepartmentChange={setDepartmentInput}
        filters={filters}
        onFilterChange={setFilter}
        onClear={clearFilters}
      />

      {loading ? <Spinner label="Loading jobs…" /> : null}
      {error ? <ErrorState description={error} onRetry={refresh} /> : null}

      {!loading && !error ? (
        <>
          <CandidateJobList jobs={jobs} onBrowseClear={clearFilters} />
          <Pagination meta={meta} onPageChange={setPage} />
        </>
      ) : null}

      <p className="muted">
        <Link to="/candidate">Back to overview</Link>
      </p>
    </div>
  )
}
