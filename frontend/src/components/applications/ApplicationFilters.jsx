import { APPLICATION_STATUSES } from '../../constants/applications'
import { Button } from '../ui/Button'
import { Input } from '../ui/Input'
import { Select } from '../ui/Select'

export function ApplicationFilters({
  searchInput,
  onSearchChange,
  filters,
  onFilterChange,
  onClear,
  showScoreFilters = false,
  forStaff = false,
}) {
  const hasActive =
    Boolean(filters.search)
    || Boolean(filters.status)
    || (forStaff && Boolean(filters.job_id))
    || (forStaff && Boolean(filters.from))
    || (forStaff && Boolean(filters.to))
    || Boolean(filters.min_score)
    || Boolean(filters.max_score)

  return (
    <div className="job-filters application-filters">
      <Input
        label="Search"
        name="search"
        value={searchInput}
        onChange={(event) => onSearchChange(event.target.value)}
        placeholder={forStaff ? 'Candidate, job, or cover letter' : 'Search by job title'}
      />

      <Select
        label="Status"
        name="status"
        value={filters.status}
        onChange={(event) => onFilterChange('status', event.target.value)}
      >
        <option value="">All statuses</option>
        {APPLICATION_STATUSES.map((item) => (
          <option key={item.value} value={item.value}>
            {item.label}
          </option>
        ))}
      </Select>

      {forStaff ? (
        <>
          <Input
            label="Job ID"
            name="job_id"
            value={filters.job_id}
            onChange={(event) => onFilterChange('job_id', event.target.value)}
            placeholder="Optional"
          />

          <Input
            label="From"
            name="from"
            type="date"
            value={filters.from}
            onChange={(event) => onFilterChange('from', event.target.value)}
          />

          <Input
            label="To"
            name="to"
            type="date"
            value={filters.to}
            onChange={(event) => onFilterChange('to', event.target.value)}
          />
        </>
      ) : null}

      {forStaff && showScoreFilters ? (
        <>
          <Input
            label="Min match score"
            name="min_score"
            type="number"
            min="0"
            max="100"
            value={filters.min_score}
            onChange={(event) => onFilterChange('min_score', event.target.value)}
          />
          <Input
            label="Max match score"
            name="max_score"
            type="number"
            min="0"
            max="100"
            value={filters.max_score}
            onChange={(event) => onFilterChange('max_score', event.target.value)}
          />
        </>
      ) : null}

      {hasActive ? (
        <div className="job-filters__clear">
          <Button type="button" variant="ghost" onClick={onClear}>
            Clear filters
          </Button>
        </div>
      ) : null}
    </div>
  )
}
