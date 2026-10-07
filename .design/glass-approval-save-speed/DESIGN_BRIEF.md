# Glass Approval Save Speed

- Goal: Finish Glass approval quickly without duplicate parent and child Livewire renders.
- Route: `offorest.products.glass`.
- Classification: Repair.
- Preserve: approval authorization, Drive upload queue registration, activity logging, status counts, and tab movement.
- Acceptance: approving/unapproving a Glass item sends one parent refresh event instead of four duplicate refresh events; the button remains disabled with Saving only for the single request.
