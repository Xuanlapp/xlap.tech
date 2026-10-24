# Suncatcher status polling performance

## Goal
Reduce background Livewire work while keeping active workflow cards and tab counts refreshed.

## Preserved behavior
- Per-card active workflow updates remain independently polled.
- Status counts and list data still refresh periodically while the panel is visible.
- User scoping, tab state, pagination and workflow recovery stay unchanged.

## Change
- Replace the always-on 5-second parent scan/dispatch with a visible 15-second component refresh.
- Remove the parent loop that re-queried each active workflow; active cards retain their own 10-second refresh.

## Acceptance criteria
- Both panels render the visible 15-second refresh target.
- Existing Suncatcher recovery test passes.
- PHP lint, Blade cache and git diff checks pass.
- Visual structure is unchanged; browser performance still requires authenticated production measurement.
