# Bounds layer controls

- Goal: Let operators switch the bounds guide above or below the main image while adjusting crop/scale.
- Current issue: The guide image is always stacked above the editor canvas, obscuring image details at high zoom; the screenshots supplied by the user show the lost colored guide context.
- Preserve: Existing image transform, bounds geometry, opacity slider, save/cancel actions, and persisted image output (guide remains a visual-only aid).
- Hierarchy: Keep layer-order controls adjacent to Guide opacity inside Bounds controls; visibly indicate the active layer order.
- States: Guide above image (default), image above guide; keyboard-operable buttons; layer preference persists locally.
- Responsive: Controls remain usable in the existing modal sidebar on mobile and desktop.
- Acceptance: Switching layer order changes only editor preview stacking, does not alter saved output or bounds geometry; existing bounds editing remains intact.
