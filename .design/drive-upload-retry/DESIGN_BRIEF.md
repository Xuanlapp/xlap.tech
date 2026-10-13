# Drive upload retry

- Classification: repair of failed-upload recovery.
- Screenshot: failed uploads show 0/0 files and a 15-minute stale-processing error; underlying Drive/network failure is not proven without VPS logs.
- Preserve: status summary, search, pagination, Drive file links, permissions, transactional worker claim.
- UI: admin-only retry-all with failure count and confirmation; single retry queues instead of uploading in Livewire.
- States: failed with local files -> waiting -> processing -> completed/failed. Failed without local files are skipped.
- Responsive: retain current table scroll.
- Acceptance: no provider call in UI requests; no completed or running item requeued; stale processing not mutated by page render.
- Before: user screenshot desktop. After desktop/mobile unavailable; visual review unverified.
- Each claimed upload creates a fresh item-ID-suffixed Drive folder beneath the existing user folder; copies previous Drive files via API, uploads remaining local files, and swaps DB URLs/folder only after all calls complete. Failed attempts keep previous URLs.
- Recovery for 0/0 rows: inspect the previous Drive folder by folder ID and match uniquely numbered `assetId_itemN_*.png` files to source fields; ambiguous numbering fails without replacing DB links.
