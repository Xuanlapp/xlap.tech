# Review Image Item Highlight

- Goal: Show the approved item's Amazon Item Highlight in the shared Image Information panel.
- Scope: Shared ReviewImage modal for every product that has an approved `item_highlight`.
- Preserve: Existing preview, source URL, bounds controls, listing information, and modal actions.
- States: Hide the field when no highlight exists; show the stored text without truncation when present.
- Acceptance: The modal loads `item_highlight` from the current asset and displays it consistently across products.
