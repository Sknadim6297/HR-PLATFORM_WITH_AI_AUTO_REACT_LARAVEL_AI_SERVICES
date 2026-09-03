import { EMPLOYMENT_TYPES, JOB_STATUSES } from '../../constants/jobs'
import { Button } from '../ui/Button'
import { Input } from '../ui/Input'
import { Select } from '../ui/Select'

export function JobFilters({
  searchInput,
  onSearchChange,
  departmentInput,
  onDepartmentChange,
  filters,
  onFilterChange,
  onClear,
}) {
  const hasActive =
    Boolean(filters.search)
    || Boolean(filters.status)
    || Boolean(filters.department)
    || Boolean(filters.employment_type)

  return (
    <div className="job-filters">
      <Input
        label="Search"
        name="search"
        value={searchInput}
        onChange={(event) => onSearchChange(event.target.value)}
        placeholder="Title, department, or description"
      />

      <Select
        label="Status"
        name="status"
        value={filters.status}
        onChange={(event) => onFilterChange('status', event.target.value)}
      >
        <option value="">All statuses</option>
        {JOB_STATUSES.map((item) => (
          <option key={item.value} value={item.value}>
            {item.label}
          </option>
        ))}
      </Select>

      <Input
        label="Department"
        name="department"
        value={departmentInput}
        onChange={(event) => onDepartmentChange(event.target.value)}
        placeholder="e.g. Engineering"
      />

      <Select
        label="Employment type"
        name="employment_type"
        value={filters.employment_type}
        onChange={(event) => onFilterChange('employment_type', event.target.value)}
      >
        <option value="">All types</option>
        {EMPLOYMENT_TYPES.map((item) => (
          <option key={item.value} value={item.value}>
            {item.label}
          </option>
        ))}
      </Select>

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
