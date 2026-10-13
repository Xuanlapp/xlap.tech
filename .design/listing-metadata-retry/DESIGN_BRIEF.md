# Listing metadata retry

- Classification: repair to failed-item recovery flow.
- Goal: nonblocking retry of failed metadata, owner-only single retry and admin/manager selected retry.
- Preserve: generated metadata fields, search, status tabs, worker claim lock, admin debug endpoint.
- Hierarchy: status counts and failed rows remain primary; selected retry is secondary admin action.
- States: failed -> waiting -> processing -> completed/failed. Running and completed are not selectable.
- Responsive: retain table horizontal scrolling; selection adds a narrow column.
- Acceptance: UI request does not call external provider; selected item IDs are reauthorized server-side; no automatic failed retry loops.
- Baseline: user-provided desktop screenshot. After desktop/mobile render not available, so visual review unverified.
