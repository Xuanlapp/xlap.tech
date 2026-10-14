# Listing metadata and Drive upload responsiveness

- Goal: Status filters, search, and refresh should respond without repeating global recovery work; the manual Drive upload button must not hold a Livewire request open during external uploads.
- Scope: Listing metadata logs route and the User access manual upload action. Preserve status meanings, search, pagination, retry authorization, and Drive export output.
- Existing problems: Each render updates stale rows and computes five large counts. Manual Drive export runs network/file work synchronously.
- Hierarchy/states: Keep existing header, counts, filters, and table. Refresh shows a short loading label; Drive button confirms a queued request rather than completed upload.
- Responsive: Existing table scroll and filter grid remain unchanged at 390, 768, and 1280+ widths.
- Acceptance: No stale-recovery write in render; counts expire within ten seconds and explicit refresh invalidates them; manual Drive scan runs in the database queue worker, then existing Drive daemon/scheduler performs export; errors remain visible through existing Drive upload records/logs.
- Visual evidence: Authenticated baseline and after screenshots were unavailable in this session; render is unverified.
