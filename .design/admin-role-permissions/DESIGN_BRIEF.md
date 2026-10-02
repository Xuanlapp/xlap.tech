# Admin role permissions

## Goal
Make `adminxlap` the Admin tong (super admin) and allow it to grant explicit capabilities to normal Admin accounts.

## Preserved behavior
User and Manager roles continue to work. Existing product access remains unchanged. Server-side authorization is required even when UI controls are hidden.

## States
Admin tong sees permission checkboxes while editing an Admin. Normal Admin sees only actions explicitly granted. Unauthorized Livewire calls return 403.

## Acceptance criteria
- Migration adds `admin_permissions` and promotes the `adminxlap` account.
- Only Admin tong can grant Admin permissions or create another Admin.
- `create_users`, `edit_users`, and `manage_users` are enforced server-side.
- Admin page and modal actions do not bypass authorization.
