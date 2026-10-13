# Marketplace Export Page Size

## Goal
Allow selecting 500 or 1000 exportable items on one page for bulk export.

## Preserve
Existing status, owner, marketplace and exact SKU filters, selection semantics, CSV/pattern export and pagination.

## States and responsive behavior
The existing page-size select shows 500 and 1000 on desktop and mobile. Selection remains scoped to the visible page; a large page may take longer to render.

## Acceptance
Choosing 500 or 1000 updates pagination and visible selection; export actions include only selected eligible items. No database or background workers.
