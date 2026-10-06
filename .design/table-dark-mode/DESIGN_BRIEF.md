# Table Dark Mode

## Goal

Make all data tables use the same restrained dark surface language as the Marketplace Export table.

## Current Problem

Older tables use hardcoded light backgrounds such as `bg-white`, `bg-slate-50`, and bright status fills. The dark-mode compatibility layer makes some rows look white or cyan and creates inconsistent contrast.

## Preserved Behavior

- Preserve table content, sorting, pagination, row actions, links, status meaning, and responsive overflow.
- Preserve light-mode appearance.
- Preserve semantic red, amber, blue, cyan, and emerald states, but reduce their visual intensity in dark mode.

## Visual Direction

- Table surface: near-black `#111317`.
- Header surface: near-black `#0b0d10`.
- Borders: translucent slate.
- Body text: soft slate-white; metadata: muted blue-gray.
- Hover: `#22262e`.
- Error rows remain visibly red with a left accent rail; normal and blue/info rows stay black rather than blue-tinted.
- Dashboard panels and stat cards use the same near-black dark surface as data tables, without navy gradients.
- Dark dialogs have a visible slate frame, separated header/footer surfaces, and a clear close control.

## Acceptance Criteria

- All tables in `theme-dark` share the same base/header/row/hover treatment.
- No table row appears as a bright white or pale cyan block in dark mode.
- Status rows remain distinguishable and readable at WCAG contrast targets.
- Light theme and table behavior remain unchanged.
