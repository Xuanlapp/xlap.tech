# Mockup visibility sync

- Classification: repair.
- Goal: visible Glass, Sticker and Decal mockup cards update after jobs complete and synced files become available.
- Preserve: original-only preview, Bounds/download, database URLs, per-card manual refresh and lazy card mounting.
- Behavior: poll visible active jobs every 10 seconds; after completion, perform two refreshes and continue up to five minutes only if referenced local mockup files are missing. Stop otherwise. Version mockup URLs by job completion to invalidate stale browser images.
- Responsive: no layout change at mobile or desktop.
- Acceptance: no permanent polling after terminal state; card image and review use same original; manual refresh rechecks one card.
- Evidence: authenticated deployed screenshots/network timing unavailable locally; visual result unverified.
