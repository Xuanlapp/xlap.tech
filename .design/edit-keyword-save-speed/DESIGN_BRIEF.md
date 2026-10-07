# Edit Keyword Save Speed

- Goal: Save edited product keywords without duplicate Livewire renders.
- Route: product workspaces using the shared Edit Keyword modal.
- Classification: Repair.
- Preserve: validation, approval protection, per-product service update, activity logging, toast, modal close, and card refresh.
- Acceptance: each product dispatches one parent refresh event; the child status panel is refreshed through the parent render instead of receiving a duplicate event.
