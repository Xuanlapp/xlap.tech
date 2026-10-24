# Listing metadata retry by user

## Goal
Allow staff to scope bulk retry actions to one owner using the database `user_id`.

## Current Problems
- Bulk retry actions have no owner filter; retry-all can enqueue failures across all users.

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
- Selected user ID is visible in the selector and query string.
- Retry selected and retry all failed cannot enqueue another user's assets when a user is selected.
- Non-staff users cannot set the cross-user filter or invoke bulk retry.
