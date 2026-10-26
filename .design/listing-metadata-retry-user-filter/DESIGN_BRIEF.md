# Listing metadata retry by user

## Goal
Allow staff to scope bulk retry actions by owner (`user_id`) and authorized product Page (`product_id`).

## Current Problems
- Bulk retry actions need owner and Page scopes; retry-all must not enqueue unrelated users or products.

## Preserved Behavior
- Retry selected still retries only checked failed rows.
- Retry all failed remains admin-only; retry selected remains admin/manager-only.
- Existing status/search filters and per-user visibility remain intact.

## Hierarchy
1. User selector (all users or one user ID).
2. Retry selected and retry all failed actions.

## States
- Empty selection: retry-selected reports no queue action.
- Selected user: both retry methods enforce that owner server-side.
- Invalid/deleted user IDs are rejected or safely normalized to all users.

## Responsive Expectations
- Filter wraps above retry actions on narrow screens; controls remain keyboard accessible.

## Acceptance Criteria
- Selected user ID and Page ID are visible in selectors and query string.
- Retry selected and retry all failed cannot enqueue another user's assets or another Page when filters are selected.
- Non-staff users cannot set the cross-user filter or invoke bulk retry.

- The Page selector and both retry queries exclude every product outside the navigation Page group, including Support products.
