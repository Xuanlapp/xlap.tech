# Marketplace Export Pattern

Restore Item Highlight in both exports. Group selected SKU variants in contiguous Pattern blocks. Preserve SKU search, selection, and normal export. Show lone-prefix validation inline. Normal export excludes SKU pattern; Pattern includes it.

Pattern export stays disabled until at least two exportable items are selected in total. A group may contain one item; server validation enforces the same rule.

Pattern CSV includes redesign and mockup1-11 direct links. It excludes marketplace_listing_error, data_item_add, image_sub, redesign_candidates, and lifestyle1-3. Item Highlight remains included; Amazon has bullets and generic_keyword, Etsy has tags. Mixed selections use the union of content columns with non-applicable values blank.


Pattern export omits image_link; image references remain in redesign and mockup1-11.

Pattern export treats all selected items as one group. The first selected item supplies sku_pattern, so selecting SF6 first produces SF6Pattern for every exported row. Selection order is preserved.
