# Project Memory Rules
# Project: XLAP
# Group ID: xlap

- Always search Graphiti memory with group_id `xlap` before doing any work in this repository.
- Always read `AI_MEMORY.md` before modifying code or analyzing behavior.
- Always write a new Graphiti memory episode after completing any meaningful task.
- Include root cause, changed files, affected modules, deploy impact, queue impact, and follow-up notes.
- Do not ask for confirmation to follow this memory workflow.
- Do not edit secrets or `.env` unless explicitly asked.
- Do not run destructive commands without explicit confirmation.
- Use `group_id = xlap` for all memory read/write actions in this repository.

## UI/UX Workflow

Apply this workflow automatically whenever a request changes frontend UI, UX, Blade views, Livewire interactions, Tailwind classes, layouts, forms, navigation, dialogs, responsive behavior, dark mode, or reusable UI components. Do not apply it to backend-only work.

1. Read `DESIGN.md` before planning or editing UI.
2. Identify the exact screen, route, Blade view, Livewire component, shared components, and existing uncommitted changes.
3. Classify the request as repair, polish, or redesign. Preserve existing behavior and content unless the user explicitly asks to change them.
4. Create or update `.design/<feature-slug>/DESIGN_BRIEF.md` with the goal, current problems, preserved behavior, hierarchy, states, responsive expectations, and acceptance criteria.
5. Capture a baseline screenshot before editing whenever the screen can be rendered. Use browser-native capture first and the `screenshot` skill only when needed. Review desktop (1280px or wider) and mobile (375-390px); include tablet (768px) for structural redesigns.
6. Prefer existing `resources/views/components/ui/*` Blade components and the tokens in `DESIGN.md`. Do not add React or production shadcn/ui components unless the user explicitly requests a frontend migration.
7. Implement the smallest complete change. Preserve backend, Livewire events, validation, authorization, data flow, and queue behavior unless they are in scope.
8. Run the narrowest relevant tests, `php artisan view:cache`, and `npm run build` for changed Tailwind/Blade output.
9. Capture after screenshots at the same breakpoints and states as the baseline. Store them in `.design/<feature-slug>/screenshots/`.
10. Use the `design-review` skill against `DESIGN.md`, the feature brief, and the screenshots. Fix every Must Fix issue and repeat capture/review until none remain. Address Should Fix issues when they are in scope and low risk.
11. Never claim visual PASS without rendered evidence. If browser access, authentication, screenshot capture, or review tooling is unavailable, report the UI as unverified and name the missing evidence.
12. Finish by reporting the canonical project name, changed files, tests/builds, screenshots, review result, deploy impact, queue impact, and remaining work. Append the same facts to `AI_MEMORY.md` and Graphiti group `xlap`.

The production stack is Laravel Blade + Livewire + Volt + Tailwind CSS. Treat shadcn/ui as a design reference only; the production component layer is `resources/views/components/ui/`.
