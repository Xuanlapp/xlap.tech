# Order image OCR trial

- Goal: paste or select an order screenshot; locally extract Order ID, Ship to and product text for manual review.
- Preserve order import and existing database behavior. Do not save extracted data automatically.
- Tesseract OCR runs on the server, English data required. Image upload max 10 MB; show errors and editable raw text.
- Support keyboard and mobile file selection; native dialog and Escape close. Results are fallible and must be checked.
- Desktop/mobile screenshot and real OCR sample review required; server and local Tesseract availability must be confirmed.

- Follow-up: keep dialog open during Livewire updates; preview pasted/uploaded images, start OCR automatically, and show loading in a results card. No image URL input. Preserve error and editable results.
- Visual verification remains pending; local Tesseract is absent.
