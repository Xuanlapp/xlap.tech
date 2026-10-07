# Frontend performance brief

## Goal
Reduce Lighthouse warnings on the XLAP Web Platform frontend without changing backend, database, API, business logic, or existing user flows.

## Scope
- Application layout asset loading and font loading.
- Vite/Tailwind production output.
- Image delivery hints and lazy loading on Glass/product surfaces.
- Non-composited animation and reduced-motion behavior.

## Preserved behavior
- Laravel Blade + Livewire + Alpine runtime.
- All `wire:*`, modal, polling, image preview, generation, approval, import/export and navigation behavior.
- Existing theme persistence and dark mode.

## Acceptance criteria
- `php artisan view:cache` passes.
- `npm run build` passes.
- No backend/database/API file changes.
- No unlabelled image or interactive-control regressions.
- Production HTML avoids blocking remote font CSS where possible.
- Images below the fold remain lazy and preview images have intrinsic dimensions where safe.
- Motion respects `prefers-reduced-motion` and avoids translating fixed controls on hover.
- Lighthouse should be rerun on authenticated Glass and representative public/login routes; exact score improvement is not assumed until measured.

## Review widths
- 390px mobile
- 768px tablet
- 1280px desktop
