# Offorest Product Data Model

This document is the current database source of truth for Offorest.

## Current tables

Offorest keeps the product workflow intentionally small:

```text
users
products
product_user
vertex_api_credentials
prompts
product_design_assets
```

Laravel system tables such as `sessions`, `jobs`, `failed_jobs`, `cache`, and `migrations` remain framework-owned tables.

## users

The base Laravel user table also stores Offorest account flags.

- `is_admin`: allows access to admin pages such as `/offorest/admin/users`.

## products

Stores the product/page list shown in the app navigation.

- `id`: primary key.
- `name`: display name, for example Redesign or Mockup.
- `slug`: stable unique key used by routes and permissions.
- `description`: optional admin/help description.
- `is_active`: controls whether the product can be shown/used.
- `created_at`, `updated_at`: timestamps.

Seeded products: `redesign`, `mockup`, `sticker`, `poster`.

## product_user

Pivot table for product access. A user can access many products, and a product can be assigned to many users.

- `id`: primary key.
- `user_id`: user receiving access.
- `product_id`: product being assigned.
- `created_at`, `updated_at`: timestamps.

The table has a unique key on `user_id + product_id`.

## vertex_api_credentials

Stores one Vertex API credential per user.

- `id`: primary key.
- `user_id`: owner. This is unique, so one user has one credential.
- `project_id`: Google Cloud project ID.
- `location`: Vertex AI location, for example `global`.
- `client_email`: service account email.
- `private_key`: encrypted private key.
- `credentials_json`: encrypted full service account JSON.
- `is_active`: whether this credential should be used.
- `created_at`, `updated_at`: timestamps.

## prompts

Stores per-user, per-product prompt slots. Each product page can have prompt buttons 1, 2, 3, 4 without hardcoding columns.

- `id`: primary key.
- `user_id`: owner.
- `product_id`: product/page this prompt belongs to.
- `prompt_number`: prompt slot number, usually 1-4.
- `name`: optional prompt label.
- `content`: full prompt text.
- `created_at`, `updated_at`: timestamps.

The table has a unique key on `user_id + product_id + prompt_number`.

## product_design_assets

Stores product row data: keyword, source image link, redesign output, and mockup output links.

- `id`: primary key.
- `user_id`: owner.
- `product_id`: product/page this row belongs to.
- `item_number`: per-user, per-product display number. It starts at 1 for each `user_id + product_id` group and increases automatically when a new row is created.
- `keyword`: keyword from sheet/import/manual entry.
- `image_link`: source image link, stored as `VARCHAR(1000)` because source URLs can be long.
- `redesign`: redesign output link/text.
- `mockup1` through `mockup11`: mockup output links/text.
- `created_at`, `updated_at`: timestamps.

For the Sticker page workflow:

- `image_link`: source image shown in column 1.
- `redesign`: image generated from `image_link` and prompt number 1, shown in column 2.
- `mockup1`: image generated from `redesign` and prompt number 2, shown in column 3.
- `mockup2`: image generated from `redesign` and prompt number 3, shown in column 4.

Sticker data entry uses progressive disclosure:

1. The user enters `keyword` inline on the list page.
2. After submitting the keyword, the page opens the `image_link` input.
3. After saving `image_link`, the source image is shown and the generation controls unlock.

Image preview supports direct image/CDN URLs and normalizes common shared links such as Google Drive and Dropbox before rendering. The browser loads images through a signed `/image-preview` route, so providers that block normal hotlinking are handled more consistently. If the source URL is a product page instead of an actual image, or the provider blocks server-side fetching, the UI shows a fallback link that opens the original URL.

`id` remains the database primary key and is unique across every user. `item_number` is the visible STT for the user, so different users can both have item number 1 without conflict.

## Relationships

```text
User hasOne VertexApiCredential
User belongsToMany Product
User hasMany Prompt
User hasMany ProductDesignAsset

Product belongsToMany User
Product hasMany Prompt
Product hasMany ProductDesignAsset
```

## App structure

```text
app/Models/Product.php
app/Models/VertexApiCredential.php
app/Models/Prompt.php
app/Models/ProductDesignAsset.php
app/Actions/CreateUserWithProductAccess.php
app/Actions/ToggleUserProductAccess.php
app/Repositories/ProductRepository.php
app/Repositories/ProductDesignAssetRepository.php
app/Repositories/PromptRepository.php
app/Repositories/UserRepository.php
app/Services/StickerService.php
app/Services/UserAccessService.php
app/Support/ProductRegistry.php
app/Http/Middleware/EnsureUserIsAdmin.php
app/Livewire/Pages/Admin/ListUser.php
app/Livewire/Pages/Sticker/ListSticker.php
app/Livewire/Pages/Sticker/ProductDesignCard.php
resources/views/livewire/pages/{product}/{page-name}.blade.php
resources/views/livewire/pages/admin/list-user.blade.php
```
