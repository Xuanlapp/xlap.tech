# Ceramic bounds fallback

- Goal: display the existing admin Glass bounds as the Ceramic default when no Ceramic admin guide exists.
- Repair scope: Ceramic guide selection for list card, modal editor, and personal upload dialog; keep user-uploaded Ceramic guides highest priority.
- Preserve: existing Ceramic admin guide if present, user uploads, editor actions, save behavior, and responsive layout.
- States: personal Ceramic > admin Ceramic > admin Glass > no guide. Use the matching analyzer/config for the selected guide.
- Acceptance: guide URL, config and shared reference resolve to the same selected image; no changes to Glass data or queues.
- Visual evidence: authenticated production Ceramic page unavailable locally; no before/after screenshots.