# Design Review: Glass Mockup Auto Refresh

Reviewed against: `DESIGN_BRIEF.md`
Philosophy: Clear, fast, restrained internal production tool
Date: 2026-09-29

## Screenshots Captured
- User-provided broken mockup-card screenshot: `C:/Users/XUANLA~1/AppData/Local/Temp/codex-clipboard-0eb42e4e-6ab0-44fe-941c-404eae49ae4a.png`

## Findings
- Must fix: Signed preview URLs were invalidated by appending a query string after signing, causing broken image icons.
- Fixed: `ProductDesignCard::withPreviewVersion()` now leaves signed URLs unchanged; local storage URLs already include file mtime versioning.
- Unverified: Authenticated browser after-state capture was unavailable in this turn.
