# Listing Metadata Contract

## Goal

Prevent incomplete Amazon/Etsy metadata from being marked completed.

## Acceptance Criteria

- Amazon requires non-empty `title`, `description`, `item_highlight`, five bullet fields, and `generic_keyword`.
- Etsy requires non-empty `title`, `description`, and `tags`.
- Missing, empty, extra, or invalid JSON fields throw a clear error and flow into `failed`, allowing Retry.
