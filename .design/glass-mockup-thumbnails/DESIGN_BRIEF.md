# Glass mockup card thumbnails

- Scope: repair image loading in the third Glass card column only. Keep source/master display, preview modal, PSD actions, original URLs, approvals, and Drive upload unchanged.
- Original: `generated/glass/mockups/{asset_id}/MOCKUP N.png` under public storage.
- Thumbnail: `thumbnails/generated/glass/mockups/{asset_id}/MOCKUP N.webp` under the same storage root; local renderer writes alpha-preserving WebP, and existing file synchronization carries it to VPS.
- Hierarchy and states: preserve existing two-column scroll grid and lazy loading. Use a verified local thumbnail only when present; otherwise use the original preview, including after Drive export where upload metadata identifies the source slot.
- Responsive: no changes to mobile or desktop geometry. The modal gallery continues to use the original preview URL.
- Acceptance: PNG untouched; thumbnail failure does not fail job; missing/stale thumbnail falls back; clearing/deleting mockups removes the matching thumbnail directory; no new Drive image upload.
- Visual evidence: baseline and after screenshots unavailable without an authenticated browser session. Visual result is unverified.
