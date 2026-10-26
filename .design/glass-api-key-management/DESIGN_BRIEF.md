# API Key Management

- Goal: Let operators add or replace provider credentials from every AI-enabled workspace toolbar.
- Scope: Ceramic, Decal, Glass, Sticker, Ornament Etsy, Ornament Amazon 2, Suncatcher, and Suncatcher 2.
- Current problem: Some toolbars hide key management once a provider is configured, and menus omit providers whose key already exists. Ornament Amazon 2 had a provider selector but no key-management action.
- Preserve: Provider selectors, balances, provider-specific modal validation, function keys, current credential storage, and Suncatcher-specific controls.
- Hierarchy: Keep the provider selector and key-management action together in each toolbar.
- States: Always expose v98 and CheapKeyAI; label each action Add or Change based on configured provider keys.
- Responsive: Keep toolbar wrapping on narrow screens.
- Acceptance: Users can open the existing credential modal and save a replacement without deleting their current key first.
