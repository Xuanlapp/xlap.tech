# Order table colors

- Goal: restore neutral row backgrounds for SKU Order Items, History Orders, and Order Products in dark mode.
- Repair: remove orange hover classes mistakenly matched by the global dark-mode status selector; use neutral hover styling.
- Preserve table content, pagination, selection, actions, and light/dark support.
- Acceptance: rows are neutral by default and distinguishable on hover at desktop and mobile widths.

- Validation: OrderTextTest 12 tests / 95 assertions, view cache, and npm build passed. Screenshot review unavailable; Better Design quota exhausted.
