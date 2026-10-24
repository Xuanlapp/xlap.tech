# Marketplace Export Selection Repair

- Goal: Make row and page selection immediate and consistent with the selected count.
- Problem: Row selection mixes Livewire checkbox binding with a server-side toggle; page selection toggles from server state while the browser has already toggled the checkbox. Delayed updates can render stale checked state.
- Preserve: Existing filters, status tabs, pagination, selected IDs across visible pages, and export behavior.
- Hierarchy: Native checkboxes remain the selection controls; selected count remains the feedback.
- States: Explicitly select or deselect based on the checkbox's new checked state. Page checkbox applies that same state to visible items only.
- Responsive: Preserve existing table layout and checkbox sizing.
- Acceptance: Repeated/rapid selection stays aligned with selected count and exported selections; row checkbox checked state renders from the selected ID collection in every status tab; no manual DOM checkbox mutation.
