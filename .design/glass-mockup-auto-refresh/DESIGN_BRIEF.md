# Glass Mockup Auto Refresh

- Goal: Make newly completed custom PSD mockups appear automatically in the Glass item card.
- Route: `offorest.products.glass`
- Classification: Repair.
- Preserve: Generate workflow, local worker queue, mockup slots, image review modal, Create Master and bounds behavior.
- Current problem: The card received mockup records but browser image URLs could be signed preview URLs with an extra cache-busting query appended after signing, producing broken image icons.
- Expected states: waiting/processing polls every 3 seconds; completed outputs render without signature errors; failed jobs show retry guidance; empty state remains unchanged.
- Acceptance: Completed mockup cards load without manual page refresh; signed local preview URLs remain valid; no queue or storage path changes.
