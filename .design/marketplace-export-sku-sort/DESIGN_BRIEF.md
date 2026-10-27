# Marketplace export SKU sort

- Goal: sort the Export table by SKU ascending or descending from its header.
- Scope: interaction polish; preserve existing filters, selections, export behavior, default order, and responsive table.
- Behavior: first click sorts A-Z; subsequent clicks toggle direction; reset pagination. Select-all follows the visible page.
- Accessibility: native button, visible direction, keyboard focus, and aria-sort.
- Acceptance: ordering applies before pagination, ties remain stable, existing export behavior is unchanged.
- Visual verification: desktop and mobile screenshots required; no authenticated browser session available.

- Follow-up: keep selected items across SKU search; display selected count on both export buttons. Both CSV exports use the active SKU order; Pattern uses the first sorted item as the parent. Default export order remains unchanged when SKU sorting is inactive.
- Acceptance: filtering cannot discard selected eligible items; Pattern validates the full selection; A-Z/Z-A applies across all selected pages.
