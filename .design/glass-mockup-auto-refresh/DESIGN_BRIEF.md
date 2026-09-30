# Glass Mockup Auto Refresh

- Goal: Make newly completed custom PSD mockups appear automatically in the Glass item card.
- Route: `offorest.products.glass`
- Classification: Repair.
- Preserve: Generate workflow, local worker queue, mockup slots, image review modal, Create Master and bounds behavior.
- Current problem: The card received mockup records but browser image URLs could be signed preview URLs with an extra cache-busting query appended after signing, producing broken image icons.
- Expected states: only items with a database job in waiting/processing state poll every ten seconds while the Mockup section is visible; polling stops as soon as the database reports completed/failed. A labeled refresh control updates only that item's Livewire card and shows a spinning state. Waiting/processing, completed, failed and empty states retain their existing messages and actions.
- Acceptance: No unbounded per-card polling; image URLs remain valid; newly synced mockups appear on either automatic refresh or manual refresh; no queue or storage path changes. Lazy image loading remains independent of polling.
