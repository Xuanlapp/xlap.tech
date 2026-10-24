# Pagination Controls

## Goal

Replace the segmented current/total counter with the supplied compact numbered pagination pattern across paginated pages.

## Current Problems

- Shared XLAP pagination shows Previous / current-total / Next and hides direct page navigation.
- Order tables use Livewire's default pagination view, so presentation differs.
- Modal-specific pagination is not currently present in Blade modal views or Livewire modal components.

## Preserved Behavior

- Keep each paginator's page-name, Livewire page state, current scroll behavior, list data, and per-page settings.
- Keep existing XLAP semantic colors and light/dark theme support.

## Hierarchy

1. Compact previous/numbered/next controls, active page clearly marked.
2. Labeled "Đi tới trang" input and "Đi" action.

## States

- First/last page disables the relevant arrow; page-jump controls disable during navigation to avoid duplicate requests.
- Active page uses current-state styling and `aria-current`.
- Ellipses represent omitted ranges; direct page jump clamps input to valid bounds.
- Keyboard focus remains visible; accessible names on arrow-only buttons.

## Responsive Expectations

- Right-align the controls on desktop; keep them within the viewport on mobile.
- Keep the pagination surface content-sized; remove full-width card borders, shadows, and forced minimum widths.
- Maintain the same hierarchy on desktop and mobile.

## Acceptance Criteria

- All paginated page views use the shared numbered pattern.
- No modal currently owns pagination; shared component is reusable if a modal adds it later.
- Existing Livewire page names and no-scroll behavior are preserved.
- Blade cache and frontend build pass; visual result reported unverified if authenticated rendered screenshots are unavailable.

