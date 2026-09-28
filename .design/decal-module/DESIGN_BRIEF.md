# Decal Module Design Brief

## Goal

Create a dedicated Decal product workspace with the same user capabilities and visual language as Sticker.

## Scope

- Add a separate Decal route, navigation entry, product permission, Livewire pages/modals, import flow, and PSD mockup flow.
- Preserve Sticker behavior and content unchanged.
- Use the existing Sticker layout and responsive behavior to avoid a visual redesign.

## States

- Empty, loading, error, approved/unapproved, generation, Excel import, PSD template upload, and local mockup polling states remain behaviorally equivalent to Sticker.

## Responsive Expectations

- Retain the existing Sticker layouts at mobile (390px), tablet (768px), and desktop (1280px+) widths.

## Acceptance Criteria

- Decal data, product access, event names, storage paths, AI credential function key, PSD templates, and local mockup jobs are isolated from Sticker.
- Existing Sticker workflows are unaffected.
- Product-card `STT` and `SKU` identifiers use the same indigo status treatment across all product workspaces.
- Blade compilation and frontend build succeed.
