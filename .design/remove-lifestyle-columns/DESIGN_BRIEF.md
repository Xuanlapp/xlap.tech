# Remove Lifestyle Image Columns

- Goal: retire `lifestyle1–3`; keep generated/selectable mockups in `mockup1–11`.
- Current problem: old lifestyle fields remain in model, Drive queue, approval checks, and Marketplace Excel export.
- Preserve: Create Master, selectable PSD mockup ordering, approval rules, Drive upload behavior, and Excel fields unrelated to lifestyle.
- Data migration: move any existing lifestyle URL into the next empty mockup slot before dropping columns; stop migration if the 11 slots cannot hold the data.
- Acceptance: no runtime code reads/writes lifestyle fields; regular and pattern exports omit them; stored mockups remain addressable as mockup slots; migration succeeds without discarding legacy values.
