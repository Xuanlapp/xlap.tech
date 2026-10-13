# Glass Item Custom PSD

- Goal: choose a saved PSD for one Glass item without changing the user's primary PSD selection.
- Existing behavior: primary PSD modal still uploads/activates globally; generation and approval remain unchanged.
- Hierarchy: always-visible, labeled per-item PSD select beneath the primary PSD row.
- States: primary fallback, item override, deleted template falls back to primary, generation in progress, save failure.
- Responsive: selector wraps within the card at narrow widths; text remains visible in dark mode.
- Acceptance: an item override affects only that item's subsequent mockup jobs; other cards use the primary PSD. No original mockups are replaced merely by changing the selection.
- Visual verification: authenticated desktop/mobile baseline and after screenshots unavailable; review pending.

- Repair: primary PSD appears once as the fallback option; the custom list excludes that active PSD, avoiding duplicate choices.
- Repair: per-item selection requires one dropdown click, with no preceding disclosure action.
