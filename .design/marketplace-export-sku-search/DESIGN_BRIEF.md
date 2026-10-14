# Marketplace Export SKU Search

## Goal
Restore partial SKU search by default and offer an explicit exact-match checkbox for comma-separated SKU lists.

## Preserve
Owner, status, marketplace, pagination, selection, CSV and pattern exports remain unchanged. Search remains scoped to SKU.

## Hierarchy and states
Keep the checkbox beside the existing SKU input, unchecked by default. Checked means exact SKU equality for each trimmed token; unchecked means each token matches anywhere in the SKU. Changing mode resets pagination. The same result set drives visible rows and export selection.

## Responsive and acceptance
Controls wrap on mobile without hiding the option. Keyboard users can toggle the labeled checkbox. Partial FCAMZ190 includes FCAMZ1905; exact FCAMZ190 excludes it. Comma-separated lists work in both modes.
- Missing SKU feedback lists input tokens with no eligible result across all pages, respecting current owner, marketplace, status and exact/partial mode. It does not infer whether the SKU exists outside these filters.
