# XLAP Design System

This file is the source of truth for XLAP interface work. Update it only when a reusable product-wide decision changes; page-specific decisions belong in `.design/<feature>/DESIGN_BRIEF.md`.

## Product Direction

- Professional internal production tool: clear, fast, restrained, and data-friendly.
- Preserve the existing XLAP/Offorest blue identity.
- Prioritize task completion over decoration.
- Use Blade + Livewire + Tailwind CSS. Do not introduce React for isolated component needs.
- Prefer shared `x-ui.*` Blade components over repeated Tailwind markup.

## Semantic Colors

Existing CSS variables in `resources/css/app.css` are authoritative:

| Role | Light | Dark |
|---|---|---|
| Page background | `--app-bg: #f5f7fb` | `--app-bg: #0b1220` |
| Panel | `--app-panel: #ffffff` | `--app-panel: #111c2e` |
| Muted panel | `--app-panel-muted: #f8fafc` | `--app-panel-muted: #162338` |
| Border | `--app-border: #e2e8f0` | translucent slate |
| Primary text | `--app-text: #0f172a` | `--app-text: #f8fafc` |
| Muted text | `--app-muted: #64748b` | `--app-muted: #aebbd0` |
| Primary action | `--app-accent: #2563eb` | `--app-accent: #60a5fa` |

Semantic status colors:

- Success: emerald.
- Warning: amber.
- Error/destructive: red.
- Information and primary selection: blue.
- Neutral metadata: slate.
- Never use color as the only status cue; include text or an icon.

## Typography

- Primary font: Figtree with system sans-serif fallback.
- Page title: `text-2xl` or `text-3xl`, semibold, tight tracking.
- Section title: `text-lg` or `text-xl`, semibold.
- Body: `text-sm` or `text-base`, normal weight, comfortable line height.
- Supporting text: `text-sm text-slate-500`, with a dark equivalent.
- Avoid uppercase except short data labels. Avoid multiple bold paragraphs.

## Spacing and Layout

- Use the Tailwind 4px spacing scale. Prefer `gap-2`, `gap-3`, `gap-4`, `gap-6`, and `gap-8`.
- Page horizontal padding: `px-4 sm:px-6 lg:px-8`.
- Primary content width should follow the task; forms normally use `max-w-2xl`, dashboards `max-w-7xl`.
- Cards normally use `p-4` on compact surfaces and `p-6` for primary content.
- Group related label, control, hint, and error text together.
- Avoid empty cards, excessive nested panels, and decorative containers without a user job.

## Shape and Depth

- Controls: `rounded-xl`.
- Cards and large surfaces: `rounded-2xl`.
- Pills only for compact status badges or filters.
- Default depth: subtle border plus `shadow-sm`; reserve stronger shadows for floating dialogs and menus.
- Hover must not move primary controls or override selected/pressed states.

## Components

- Shared production components live in `resources/views/components/ui/` and are invoked as `x-ui.*`.
- Prefer `x-ui.button`, `x-ui.card`, `x-ui.form-field`, `x-ui.input`, `x-ui.textarea`, `x-ui.select`, and `x-ui.badge` before creating page-local equivalents.
- Keep business logic in Livewire/PHP, not Blade components.
- Component APIs should use semantic props such as `variant`, `size`, `invalid`, `disabled`, and `loading`.
- Do not overwrite an existing shared component without checking every usage.

## Forms

- Every control requires a visible label unless its accessible name is otherwise explicit.
- Required fields show text for assistive technology, not only a red asterisk.
- Errors use `role="alert"`, clear correction text, and an invalid control state.
- Keep primary submit actions close to the fields they affect.
- Show disabled, loading, success, and failure states where applicable.

## Responsive Behavior

- Build mobile-first.
- Minimum review widths: mobile 375-390px and desktop 1280px or wider.
- Review tablet 768px when navigation, tables, multi-column forms, or dashboards change.
- Touch targets should be at least 44px high where practical.
- Multi-column forms collapse to one column on mobile.
- No horizontal scrolling except intentional data tables with a clear scroll container.

## Accessibility

- Maintain WCAG AA contrast: 4.5:1 for body text and 3:1 for large text or UI boundaries.
- Keep visible keyboard focus rings.
- Preserve heading order and semantic landmarks.
- Icon-only controls require an accessible label and tooltip/title when useful.
- Decorative SVGs use `aria-hidden="true"`.
- Motion must not block tasks and should respect reduced-motion preferences.

## Dark Mode

- Support both `theme-light` and `theme-dark` behavior already used by XLAP.
- Verify every changed surface, border, field, status, and focus state in dark mode.
- Do not rely on simple color inversion or hardcoded light backgrounds.

## Copy

- Use concise Vietnamese labels that state the outcome.
- Avoid generic actions such as `Continue` when the result can be named.
- Supporting copy should explain a real consequence, requirement, or next step.
- Keep one primary action per task surface whenever possible.

## Visual Review Gate

A UI task passes only when:

1. The original behavior and supplied content are preserved unless explicitly changed.
2. Relevant tests, Blade cache, and frontend build pass.
3. Before and after screenshots exist for the same states and breakpoints when a baseline is available.
4. Mobile and desktop layouts have no unintended overflow or clipped actions.
5. Loading, empty, error, disabled, focus, selected, and open states affected by the change are reviewed.
6. The design review contains no unresolved Must Fix findings.
7. The handoff names any unavailable evidence instead of claiming an unverified PASS.
