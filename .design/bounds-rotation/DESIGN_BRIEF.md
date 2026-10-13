# Bounds Rotation

- Scope: Glass and Ceramic shared image review editor; polish interaction only.
- Goal: rotate the source image in 1-degree steps or drag a rotation slider while preserving move, zoom, guide opacity, and Save.
- States: editing, source loading, normal save, server fallback save; initial rotation 0 degrees each edit.
- Responsive: controls wrap in existing panel, slider shrinks on mobile; numeric angle is visible and buttons have labels.
- Acceptance: preview and exported PNG share the same center/angle; legacy zero-angle output is unchanged; fallback renderer honors angle.
- Visual verification: authenticated baseline and after screenshots unavailable; no visual PASS claimed.
