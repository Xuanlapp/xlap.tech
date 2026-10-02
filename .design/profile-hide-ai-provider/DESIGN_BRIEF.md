# Profile AI Provider Removal

## Goal

Remove the AI Provider card from the user Profile page.

## Preserved Behavior

- Keep profile information, password, and other profile cards unchanged.
- Leave the Livewire provider component available for any future/admin use.

## Acceptance Criteria

- Profile no longer renders the AI Provider selector or its Save button.
- The remaining Profile sections render without an empty gap or duplicate card.
