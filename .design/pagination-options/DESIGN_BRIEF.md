# Pagination Filter Options

## Goal

Use one predictable page-size filter across all user-facing paginated pages.

## Acceptance Criteria

- Page-size selectors offer 5, 10, 20, 30, and 50; Order and Marketplace Export additionally offer 100.
- Invalid Livewire values fall back safely to a valid default.
- Pagination, search, and page-reset behavior remain unchanged.
