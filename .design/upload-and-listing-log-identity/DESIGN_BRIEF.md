# Upload and listing log identity

- Classification: repair.
- Goal: Show item ID with SKU rather than keyword in Drive upload and listing metadata log rows, and align search with the visible identity.
- Existing problem: screenshot shows several Drive uploads failed as stale after 15 minutes with 0 uploaded files; repository code marks stale processing at 15 minutes and does not establish why upload stopped.
- Preserve: status counts, filtering, access rules, retry behavior, metadata generation fields (including generic keyword), file links, upload job semantics.
- Hierarchy/states: retain existing status summary and table; use ID + SKU in each row, fallback dash when SKU missing; preserve waiting/running/completed/failed information.
- Responsive: retain horizontal table scrolling and current mobile layout.
- Acceptance: ID/SKU displayed and searchable on both log pages; no keyword in identifying row/search placeholder; existing output metadata remains intact; PHP lint, Blade cache, build pass.
- Evidence: screenshot supplied only for existing desktop Drive logs. No authenticated mobile or post-change screenshot available; visual result unverified.
