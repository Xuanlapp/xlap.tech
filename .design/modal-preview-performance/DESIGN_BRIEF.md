# Review Image Modal Performance

- Classification: repair. Keep layout and interactions unchanged.
- Problem: modal response inlines a full bounds guide image and analyses guide configuration even outside the bounds workflow.
- Change: serve the existing guide URL instead of embedding PNG bytes; only load config for open Glass/Ceramic redesign actions.
- Preserve: initial modal image, deferred listing info, bounds editor and fallback, dark mode, keyboard behavior.
- Verify: PHP/Blade/build checks; authenticated before/after latency and visual captures pending.
- Glass/Ceramic/Decal/Sticker/Etsy image card clicks dispatch directly in the browser; local `/storage/` URLs stay same-origin instead of being rewritten to a remote domain.
- Gallery arrows in view-only image previews change Alpine state instantly; editing/generation modes retain Livewire navigation to preserve server-side target state. Full-size Drive files may still load slowly after the navigation itself.
