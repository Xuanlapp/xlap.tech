# Listing metadata retry by user

## Goal
Allow staff to scope bulk retry actions by owner (`user_id`) and authorized product Page (`product_id`).

## Current Problems
- Bulk retry actions need owner and Page scopes; retry-all must not enqueue unrelated users or products.

## Preserved Behavior
- Retry selected still retries only checked failed rows.
- Admins and managers can retry across the users they are allowed to manage. Regular users can retry only their own failed items on assigned active Pages.
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
- Regular users can choose among their assigned Pages and invoke both retry actions without accessing other users or unassigned Pages.

- The Page selector and all retry queries exclude products outside the Page group, including Support products.

- Retry controls disable during requests and show in-progress labels; checkbox state stays deferred to avoid one request per click.
