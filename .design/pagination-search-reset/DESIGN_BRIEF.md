# Pagination Search Reset Brief

## Goal

Keep pagination labels and search results consistent when an active product list is filtered.

## Behavior

- Changing the search text resets every active status panel to page 1.
- Status, search text, and result layout remain unchanged.
- Applies to Glass, Sticker, and Decal workspaces, which use the parent-search/reactive-status-panel pattern.

## Acceptance Criteria

- Searching from page 3 with only one result page displays page 1 of 1, never page 3 of 1.
- No layout or visual changes occur.
