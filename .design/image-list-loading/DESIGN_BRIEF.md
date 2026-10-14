# Image List Loading

## Goal
Reduce image requests made while opening and reloading asset lists.

## Scope
Repair the shared image-preview component's prefetch distance without changing image URLs, originals, modal behavior, labels, or export data.

## States
Near-viewport images load as the user scrolls; offscreen cards retain the existing blank image surface and link fallback. Desktop and mobile use the same near-viewport distance.

## Verification
Blade cache and build; compare network Img transferred bytes and first-screen render at desktop and mobile after deploy. This is not a thumbnail conversion: large images already visible may remain expensive.
