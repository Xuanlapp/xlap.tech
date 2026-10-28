# Order 1 item

- Replace screenshot OCR with pasted Amazon order text and a manual extraction action.
- Show editable Order ID, Ship to, Product name, SKU and customizations; missing fields remain blank.
- Preserve existing order workflows; do not save or create orders. No OCR or AI dependencies.
- Validate input size. Support keyboard, mobile and modal dismissal. Desktop and mobile visual verification required.
- Results are read-only; show each customization line as its own label and value, preserving colons inside parentheses.
- Display US address fields individually including optional address line 2; retain original address, leave fields blank when the format cannot be recognized.
- Show read-only order fields and individual customizations in compact two-column tables; retain long values and original address on mobile.

- Latest table decision: use one wide horizontal read-only table. Remove `Ship to (bản gốc)` and `Product name` from the UI. Keep Order ID, TO_* address fields and SKU, then append each Customization label/value as its own column. Use horizontal overflow and a wider modal so long Amazon data remains readable.
- Validation: focused OrderTextTest, Blade cache and frontend build passed. No database or queue changes.
- Make result text slightly smaller and separate columns with visible vertical borders; keep long content readable through wrapping and horizontal scrolling.
- Label cleanup: render the customization header as `ADD NAME` while preserving the full parsed source line and value.
- Match the extracted SKU exactly against SKU Order Items and display design links in the results table. Users see only their own matches; admins see all matches, with owner names when multiple records match. Missing links display a dash; long links wrap.
- Reorder order results, show Product and Order Product ID, editable quantity, design link and TO_* fields; default FBM, optional FBA-only lookup. Product matching requires an unambiguous catalog match; Glass and Ceramic ignore size, others require an extracted SELECT SIZES value. Company remains blank if not reliably identified.
- Qty is read-only and extracted from the quantity immediately after Order Item ID and before the unit price; missing or unrecognized quantity displays a dash, including after re-parsing.
- Hide the Product column; retain its value internally for the Order Product ID lookup.
- Do not show a separate quantity summary above the table; keep Qty only as a table column.
- Support multiple pasted orders within one modal. Lock the input after extraction; Thêm đơn clears and unlocks only the input. Keep extracted results in a single table with unioned customization columns, row removal and clear-all. FBA applies to all rows; closing the modal clears temporary results. No persistence.
- Export đơn downloads all visible order fields and customization columns as an Excel-compatible XML spreadsheet. Each exported order is recorded in history per authenticated user and order ID; repeat exports increase its count once per export. Missing order IDs block export. No migration or queue changes.
- Multi-item orders: split one pasted Order ID into one result row per SKU, retaining per-item Qty and Customizations; export stores all rows under one history record and increments once per export.
- Place Export đơn in the same wrapping action row as Lấy dữ liệu and Thêm đơn; keep its validation error close to the action.
- Align Export đơn to the right of the action row; preserve wrapping on narrow screens.
- Add a Tracking ID file column after Design Link. Upload each selected JPG/PNG/WebP/PDF (max 10 MB) to Drive only during Export đơn; request public reader permission (`anyone with link`, view-only), include the Drive URL in Excel and history, and block export if upload/permission fails. Row deletion reindexes file inputs safely; repeat export reuses the uploaded link.

- During export, disable modal actions and inputs, prevent Escape from closing the modal, and display an export spinner until the request ends. Keep export disabled during tracking file upload.

- Renamed the order paste modal title and trigger button from Order 1 item to Order Amazon Paste. Route and Livewire component remain unchanged.

- Renamed the Blade view to resources/views/livewire/pages/order/OrderPage.blade.php and updated OrderPage view resolution.
