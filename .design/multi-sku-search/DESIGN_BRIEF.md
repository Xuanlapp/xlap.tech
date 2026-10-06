# Multi-SKU search

- Goal: filter multiple product items by comma-separated SKU values.
- Scope: Glass, Decal, and Sticker workspace search fields backed by ProductDesignAssetRepository.
- Preserve: single-term keyword, SKU, ID, and item-number search behavior.
- Acceptance: `GPMN_021, GPMN_015` returns only both matching SKU rows; spaces are trimmed and duplicates ignored.
