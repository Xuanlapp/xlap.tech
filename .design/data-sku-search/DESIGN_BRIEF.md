# DATA SKU Search

- Goal: let super_admin search approved and unapproved assets across every user by multiple comma-separated SKUs.
- Preserved behavior: read-only results; no changes to product records; existing DATA navigation and dark mode remain intact.
- Hierarchy: page title and explanation, one labeled multi-SKU search control, result count/table, pagination.
- States: empty input shows an instruction; valid input shows matching rows; no matches shows a clear next action; unauthorized users receive 403.
- Responsive: table scrolls horizontally on small screens; controls remain touch-friendly.
- Acceptance: `RGC8-1,RGC8-2` returns both exact SKU matches across all users, trims spaces, ignores case, and never leaks results to non-super-admin users.
