# Suncatcher 2: Keyword scrape stepper

- Goal: Move keyword research and competitor scrape selection from the Add Item modal into the Unapproved page as the first page-based progress stepper.
- Classification: Redesign of the entry surface; preserve research, exact-five selection, extension scrape, image selection, validation, save, and Livewire events.
- Preserved behavior: Amazon research extension request, maximum five selected URLs, scrape selected URLs, first scraped image as main image, create item flow.
- Hierarchy: Unapproved page header -> progress stepper -> current step content -> primary action. Keep the existing asset list visible below the task when not loading.
- States: idle, research loading, research results, selection limit reached, scrape loading, scrape complete, extension error, validation error, saved.
- Responsive: one-column stepper/content on 375-390px; multi-column research cards on desktop; no horizontal overflow.
- Acceptance criteria:
  - No keyword research/scrape selection modal is opened from Add Item.
  - Add Item activates the Unapproved tab and opens the page stepper.
  - Step 1 researches keyword and selects exactly five competitors.
  - Step 2 scrapes selected competitors and exposes the existing image/link save flow.
  - Existing Livewire service calls and product-created events remain unchanged.
