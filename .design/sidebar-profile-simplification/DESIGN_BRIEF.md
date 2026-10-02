# Sidebar Profile Simplification

## Goal

Remove the name, email, and online-status indicator from the fixed sidebar profile footer.

## Scope

- Repair the desktop and mobile sidebar footers in `resources/views/livewire/layout/navigation.blade.php`.
- Preserve the user avatar, navigation, account dropdown, authorization, and routes.

## Acceptance Criteria

- Only the avatar remains in each sidebar footer.
- No name, email, or green status dot is rendered there.
- The compact footer remains aligned and usable on desktop and mobile.
