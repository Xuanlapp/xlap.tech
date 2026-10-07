# Glass Preview Modal Speed

- Goal: Open Glass image Preview without waiting for unrelated listing metadata.
- Route: `offorest.products.glass` and ReviewImage modal.
- Classification: Repair.
- Preserve: image gallery navigation, bounds visibility protection, Create Master and Mockup actions, and full listing metadata for Amazon/Etsy preview flows.
- Acceptance: Glass opens with only the lightweight approval lookup; non-Glass listing previews keep their existing metadata loading.
