# Glass keyword edit placement

- Repair: place Edit immediately beside the keyword, separate from approval.
- Preserve the Edit modal action, approval logic, delete action, and card content.
- On narrow screens, the keyword truncates before the Edit control; both light and dark modes retain legible text.
- Acceptance: Edit stays adjacent to keyword, approval remains at the opposite edge, and Blade renders without errors.
- Performance follow-up: dispatch Edit directly to its Livewire modal from the browser; update only the Glass keyword on save instead of re-rendering image cards. Preserve server authorization and validation.
