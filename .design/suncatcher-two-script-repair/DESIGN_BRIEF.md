# Suncatcher 2 script and saved API repair

- Classification: repair; preserve existing Blade/Livewire layout and tokens.
- Root problems: unclosed script conditional; restored drafts omitted script; product-specific key discovery disagreed with generation credential lookup.
- Preserve: competitor selection/scraping, loading fieldset lock, draft-only generation, existing item workflows, encrypted keys and user isolation.
- Output: Generated Script after competitor data; escaped section content; no item creation on Generate Script.
- Provider toolbar: saved provider/model selection; no Change API action; initial Add API available only without saved provider options.
- Affected product pages: Suncatcher 2, Suncatcher, Sticker, Decal, Glass, Ceramic, Ornament Etsy, Ornament Amazon 2.
- States: empty draft, generated draft, restored draft, generation failure, loading, provider selection.
- Responsive: preserve existing mobile single-column and desktop two-column script panel.
- Acceptance: rendered Livewire tests pass with and without script; restored script visible; no createAsset call; another user's or inactive credential not selected.
- Validation: 3 tests / 27 assertions; PHP lint; compiled Blade PHP lint; view:cache; npm run build passed.
- Visual review unverified: no authenticated desktop/mobile screenshots captured. Better Design search/review/comprehension unavailable (HTTP 402 quota exhausted). No visual PASS claimed.

## 2026-10-23 Curved Tabs
- Scoped redesign: replace script card grid only; preserve generated content and draft state.
- Gain: less vertical scanning; trade-off: only one section readable at a time.
- Rounded-top tab strip connects to one panel. Horizontal strip scrolls on mobile; panel wraps prose.
- Keyboard arrows/Home/End, accessible labels and selected state; first section selected by default.
- Acceptance: all sections present, no item creation, existing generation lock retained.
- Tests and build pass; authenticated visual verification outstanding, Better Design quota exhausted.
