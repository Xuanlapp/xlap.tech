# Status filters in URL

- Goal: Make the selected status shareable and restorable from the URL on Listing Metadata and Drive Uploads.
- Current problem: Status uses Livewire session state, so the URL does not identify the visible filter.
- Preserve: Existing status counts, filtering, pagination reset, search session, polling, and retry actions.
- Hierarchy: No visual change; selected status tiles remain unchanged.
- States: `all` is the default and omitted from the URL; non-default status appears as `?status=waiting|processing|completed|failed`.
- Responsive: No layout changes; existing responsive layout preserved.
- Acceptance: Selecting a status updates URL, refreshing/opening that URL restores it, invalid status falls back to `all`.