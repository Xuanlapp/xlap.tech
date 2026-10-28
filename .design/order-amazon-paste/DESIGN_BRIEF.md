# Order Amazon Paste

- Goal: move Amazon order parsing and export to a standalone Support page with individually assignable product access.
- Preserve parsing, SKU ownership, export history, optional tracking uploads, FBA selection, and multi-order behavior.
- Page contains input, actions, and a horizontally scrollable results table; usable at desktop and mobile widths.
- Existing Order page no longer hosts the modal. Access requires the order-amazon-paste product permission, with admin access following existing middleware.
- Acceptance: authorized users can parse and export; unauthorized users receive 403; old Order page remains intact.

- Validation: 13 tests / 98 assertions, Blade cache, PHP lint, npm build passed. Browser screenshots and spacing review unavailable; Better Design quota exhausted.

- Added per-SKU source/AI preview cards. ADD NAME and ADD YEAR are appended only when present. Users generate with their own CheapKeyAI credential, then explicitly approve; approved AI images replace Design Link only at synchronous Export time alongside tracking uploads. Super admins can edit the shared prompt; regular users see no prompt editor.

- Draft rows, approved images and uploaded links persist in the current user session; temporary tracking files must be selected again after reload. Clearing all also clears the input. Generated images and tracking files become viewable by anyone with the Drive link upon export.

- Card redesign: match the existing Glass/Ornament card header, SKU badge, approval action, two large source/master panels, and per-card loading state. Stack panels on mobile; preserve personalization, generation, approval, errors, export and permissions. Mockup/PSD and image history are not part of this order flow.

- Place the order data table first and the separate image-card section at the bottom. Preserve all image actions and existing responsive layouts.

- Display three matching panels per image card on desktop, two columns on tablet and one on mobile. The third panel is an empty placeholder; no mockup or other behavior is implied.

- Keep STT, SKU, Order, ADD NAME and ADD YEAR in one compact card header row on desktop; allow wrapping only on narrow screens.

- Super admins open the shared prompt editor from a compact Prompt button using the existing modal component. Save closes the modal; validation errors remain visible. Preserve authorization and prompt storage.

- Separate STT, SKU, Order, ADD NAME and ADD YEAR in the card header with visible vertical-bar separators; omit separators for absent optional fields.

- Design Link table cell shows Wait until the approved image is uploaded; then it displays the resulting Drive link. Source image and existing export behavior remain unchanged.

- Place the API key and super-admin Prompt buttons together on the right of the title header; wrap naturally on narrow screens. Preserve existing modal behavior and permissions.

- Create Master sends the source image, shared prompt, and present ADD NAME/ADD YEAR values with instructions to replace corresponding artwork text; review remains required.

- Show the current user CheapKeyAI balance beside the key button, using an existing key from any page; show null when no user key exists and N/A if the balance cannot be fetched. Cache briefly and do not show shared keys.

- Center the shared prompt modal vertically and horizontally in the viewport; preserve scrolling for small screens and the existing modal behavior elsewhere.

- Enlarge the centered prompt modal slightly, with a taller prompt field; preserve small-screen scrolling and other dialogs.

- Hide ADD NAME, ADD YEAR, Choose Pack, Surface 1 and SELECT SIZES from the visible order data table; retain parsed values for prompt generation and export mapping.

- Create Master buttons must load independently per SKU; generating one card must not disable Create Master buttons on other cards. Export may still disable all actions while exporting.

- Super admins select one shared CheapKeyAI image model in a separate Model dialog; the saved choice applies to subsequent Create Master requests for every user, with each user using their own credential. Preserve existing prompt settings and responsive modal behavior.
- Keep the super-admin Model trigger immediately to the left of the CheapKeyAI balance in the order page header.

- Align image panel header rows so the three image surfaces start at the same vertical position; preserve responsive stacking and existing actions.

- Remove the prompt editor character cap; retain required input and super-admin authorization.

- Only super admins may upload separate Glass and Ceramic bounds templates for Order Amazon Paste; show current previews and use the existing modal. Keep uploads independent of Glass/Ceramic template settings. This step does not add an image editor or change export.
- Show a temporary preview immediately after selecting an Order bounds template; disable modal controls during upload/save and show progress text.

- Show only the selected bounds type, with current and newly selected images side by side; preserve upload/save loading locks.

- Match the supplied comparison: square dark preview frames, current image on the left, new image on the right, with an arrow between.

- Show missing Glass and Ceramic templates under the page header; tell non-super-admin users to contact super_admin. Hide the notice when both exist.

- Add per-card Template editor: enabled only after Create Master, default Glass/Ceramic from SKU product name, selectable fallback type, red missing-template warning, drag/zoom/rotate/circle crop, saved result in frame 3, export prefers the saved template image.

- Split order input/results and order images into two separate cards, preserving all content, states, actions, and responsive behavior.

- Match Glass editor layout: large preview on left, template selection and bounds controls on right; preserve adjustable crop controls and mobile scrolling.

- Keep the image visible in frame 3 and add a per-card show/hide button for its template overlay; prefer saved adjusted image, then Create Master.

- Repair malformed card markup: all order-image cards must remain inside the shared section at desktop and mobile widths; preserve template actions and previews.

- Open the existing image-only preview on keyboard or pointer activation of each available source, master, or adjusted image. Preserve template overlay and controls.
- Confirm deletion of a draft or all drafts because it also removes their locally generated images. Preserve source images, Drive uploads, and other users data. New template saves replace the previous draft image.

- Show and allow approval only after the adjusted Template image is saved; reject legacy approvals without a template during export.

- Scope image-generation loading locks to the relevant card; preserve export locks and global draft safety.

- Frame 3 stays empty until a template-adjusted image is saved; do not substitute the master image or show the guide overlay in the empty state.

- Show the selected Glass/Ceramic template guide in frame 3 even before the adjusted image is saved; keep the frame image empty until save.

- Approval uploads the saved template image and selected optional tracking file synchronously to viewable Drive links. Export reuses those links; tracking selected later uploads during export. Failed uploads remain retryable.

- Use one global template visibility control aligned to the right of the Ảnh đơn hàng heading; toggling it updates every order card together.

- Show a scoped spinner for each asynchronous button action; instant preview and visibility controls remain immediate.

- Add STT as the first table column; numbering matches image cards and updates after deletion.

- Show FBM gift boxes as child rows directly after their parent; only SKU and Order Product ID are populated. Include the same rows in export and history; deleting the parent removes the child. No additional image card or approval.

- Hide the gift-box customization column from the order data table; retain its parsed value internally for BOX child-row mapping and export.

- Move Xóa tất cả beside the Dữ liệu đơn hàng heading; confirmation uses a Yes/No modal before deleting draft rows and local images.

- Store generated master images under order-amazon-paste/{user}/{product}/ with compact SKU_user_product_date.png naming; remove order-id subfolders and UUID from master names.

- Add a per-row Bỏ qua checkbox beside Design Link; checked rows may export without a design link, while other rows remain validated normally.

- Disable No and Yes immediately during deletion; keep the progress indicator until the request settles.

- Each image card shows labelled source-text fields for existing ADD NAME and ADD YEAR values only. Defaults: Name and Year; preserve edits per row and session. Require nonempty source text before generation. Hide missing or dash values. Preserve existing layout and actions.

- Per-card name/year modes distinguish replacement from addition; show only the relevant text or position input. Keep defaults and session persistence; hide controls when value is absent.
