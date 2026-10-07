# Edit Keyword Save Speed

- Goal: Save edited product keywords without duplicate Livewire renders.
- Route: product workspaces using the shared Edit Keyword modal.
- Classification: Repair.
- Preserve: validation, approval protection, per-product service update, activity logging, toast, modal close, and card refresh.
- Acceptance: each product dispatches one targeted status-panel refresh event; the expensive parent page render is not triggered for a keyword-only change.
