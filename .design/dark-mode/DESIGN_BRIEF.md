# Dark Mode Consistency

## Goal
Make Tailwind's standard `dark:` utilities respond consistently with XLAP's existing `theme-dark` preference.

## Preserved behavior
- Keep `theme-light`/`theme-dark`, localStorage persistence, and the saved user preference.
- Keep existing compatibility CSS and product-specific dark-mode styling.
- Do not change page content, authorization, Livewire state, or navigation behavior.

## Acceptance criteria
- The root document exposes Tailwind's `dark` class whenever the selected theme is dark.
- Switching themes updates `dark`, `theme-dark`, `theme-light`, and `data-theme-mode` together.
- Existing `dark:*` classes on shared components, forms, navigation, dialogs, and cards are active in dark mode.
- Light mode removes the `dark` class and remains visually unchanged.
