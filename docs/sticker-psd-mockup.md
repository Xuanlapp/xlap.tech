# Sticker PSD Custom Mockup

**Last updated:** 2026-05-29

Tai lieu nay la source of truth cho chuc nang **3. Mockup Tu Chon** tren trang Sticker.

Muc tieu cua chuc nang:

1. User upload/chon 1 file PSD cho chuc nang Mockup Tu Chon.
2. User tao anh master o o `2. Create Master`.
3. Khi bam `Generate + Update`, app thay anh master vao layer `Design` trong PSD.
4. Renderer xuat ra cac anh PNG tu folder `MOCKUP 1`, `MOCKUP 2`, ...
5. UI chi hien dung so mockup render duoc, hien nho trong o so 4 va scroll duoc.

## User Rule

- Mot user co the luu nhieu file PSD.
- Moi chuc nang chi co 1 PSD active tai mot thoi diem.
- Rieng Sticker custom mockup dung `function_key = sticker_custom_mockup`.
- Khi upload PSD moi cho cung user/product/function, PSD moi duoc active va PSD cu bi tat active.

## Main Files

```text
app/Livewire/Pages/Sticker/ProductDesignCard.php
resources/views/livewire/pages/sticker/product-design-card.blade.php

app/Livewire/Modals/Sticker/PsdMockupTemplate.php
resources/views/livewire/modals/sticker/psd-mockup-template.blade.php

app/Services/Sticker/StickerService.php
app/Services/Sticker/PsdMockupTemplateService.php
app/Services/Sticker/PsdMockupRenderer.php

app/Repositories/Product/ProductDesignAssetRepository.php
app/Repositories/Product/PsdMockupTemplateRepository.php

app/Models/ProductDesignAsset.php
app/Models/PsdMockupTemplate.php

database/migrations/2026_05_26_000020_create_offorest_product_schema.php
database/migrations/2026_05_28_000010_create_psd_mockup_templates_table.php

scripts/psd-renderer/render.js
config/services.php
.env.example
```

## Environment

Can cau hinh renderer command:

```env
PSD_MOCKUP_RENDERER_COMMAND="node scripts/psd-renderer/render.js"
```

Renderer flags:

```env
OFFOREST_ENABLE_CUSTOM_EFFECTS=false
OFFOREST_REMOVE_EDGE_WHITE=false
OFFOREST_TRIM_MOCKUP_DESIGN=false
OFFOREST_MOCKUP_DESIGN_SCALE=0.72
```

Y nghia:

- `PSD_MOCKUP_RENDERER_COMMAND`: lenh Laravel goi de render PSD. Mac dinh la Node script local.
- `OFFOREST_ENABLE_CUSTOM_EFFECTS=false`: chi apply custom layer effects cho layer Design vua replace. Khong apply toan PSD de tranh sai cac layer khac.
- `OFFOREST_REMOVE_EDGE_WHITE=false`: PSD renderer khong tu tach nen; anh master can duoc xu ly truoc o flow Sticker neu can.
- `OFFOREST_TRIM_MOCKUP_DESIGN=false`: khong crop/trim canvas anh master. Mac dinh false de tranh anh bi phong to hon Design trong PSD.
- `OFFOREST_MOCKUP_DESIGN_SCALE=0.72`: scale an toan khi fit master vao target rect cua Design goc. Giam so nay neu sticker van tran ra vung co dinh; tang len toi da `1` neu PSD can anh lon hon.

Neu gap loi:

```text
Chua cau hinh PSD renderer. Can set PSD_MOCKUP_RENDERER_COMMAND de render layer Design.
```

thi kiem tra `.env`, `config/services.php`, va chay lai:

```bash
php artisan optimize:clear
```

## PSD Requirement

File PSD hop le can co:

```text
Layer ten Design hoac Desgin
Folder MOCKUP 1
Folder MOCKUP 2
Folder MOCKUP 3
...
```

Ghi chu:

- `Design`/`Desgin` la layer se duoc thay bang anh master.
- `MOCKUP *` la cac group/folder output. Renderer se xuat moi group thanh 1 PNG.
- Ten folder dung prefix `MOCKUP` va so thu tu. Vi du: `MOCKUP 1`, `MOCKUP 2`.
- Neu PSD co layer nen, logo, texture, overlay nam ngoai group `MOCKUP *`, renderer van phai render full PSD stack va chi toggle group mockup dang xuat.

## User Flow

```text
Sticker card
  -> user co source image
  -> bam Create Master
  -> asset.redesign co anh master
  -> user chon/upload PSD
  -> bam Generate + Update o 3. Mockup Tu Chon
  -> ProductDesignCard::generatePsdMockups()
  -> StickerService::generatePsdMockups()
  -> PsdMockupTemplateService lay active PSD
  -> PsdMockupRenderer goi Node script
  -> scripts/psd-renderer/render.js render PNG
  -> ProductDesignAssetRepository::updatePsdMockups()
  -> UI show mockup1..mockup11
```

## Laravel Side

### ProductDesignCard

`ProductDesignCard` la child Livewire component cho tung item sticker.

Action lien quan:

```php
public function generatePsdMockups(): void
```

Action nay:

1. Goi `StickerService::generatePsdMockups(auth()->user(), $this->assetId)`.
2. Neu thanh cong, dispatch toast `Da render PSD mockup.`
3. Neu loi, dispatch toast voi message loi.

Trong `render()`, component nap:

```php
'asset' => $asset,
'activePsdTemplate' => app(PsdMockupTemplateService::class)->activeStickerTemplateForUser(auth()->user()),
```

Va append preview URL cho `image_link`, `redesign`, `mockup1` den `mockup11`.

### StickerService

`StickerService::generatePsdMockups()`:

1. Kiem tra asset thuoc user.
2. Bat buoc asset phai co `redesign`.
3. Lay active PSD template cua user cho Sticker custom mockup.
4. Goi `PsdMockupRenderer::render($template, $asset->redesign, $asset->id)`.
5. Append ket qua vao slot trong dau tien tu `mockup1` tro di.

Ly do dung `redesign`: o `2. Create Master` la anh dau vao chuan de dua vao PSD layer Design.

### PsdMockupRenderer

`PsdMockupRenderer` chay command trong `.env` bang Symfony Process:

```php
$process = Process::fromShellCommandline($command);
$process->setWorkingDirectory(base_path());
$process->setInput(json_encode($payload, JSON_THROW_ON_ERROR));
$process->setTimeout(300);
$process->run();
```

Payload gui qua stdin:

```php
[
    'psd_path' => Storage::disk('public')->path($template->storage_path),
    'master_image' => $this->absoluteInputPath($masterImageUri),
    'design_layer' => 'Design',
    'folder_prefix' => 'MOCKUP',
    'output_directory' => storage_path("app/public/generated/sticker/mockups/{$assetId}"),
]
```

Output directory:

```text
storage/app/public/generated/sticker/mockups/{assetId}
```

Public URL:

```text
/storage/generated/sticker/mockups/{assetId}/{file}.png
```

Truoc khi render, service xoa cac file `.png` cu trong folder output cua asset de tranh hien lai mockup cu.

## Node Renderer

File:

```text
scripts/psd-renderer/render.js
```

Dependencies chinh:

```js
import { initializeCanvas, readPsd } from 'ag-psd'
import { createCanvas, ImageData, loadImage } from '@napi-rs/canvas'
```

Renderer khong mo Photoshop. No doc PSD bang `ag-psd`, thay pixel cua layer Design bang canvas moi, sau do tu render ra PNG.

### Read PSD

PSD duoc doc voi layer image data:

```js
const psd = readPsd(psdBuffer, {
    useImageData: true,
    useRawThumbnail: false,
    skipLayerImageData: false,
    skipCompositeImageData: false,
})
```

Muc tieu la giu lai:

```text
layer.canvas / layer.imageData
layer.effects
layer.blendMode
layer.opacity
layer.fillOpacity
layer.mask
layer.placedLayer
layer.children
```

### Replace Design Layer

Renderer duyet toan bo layer va tim leaf layer ten:

```text
Design
Desgin
```

Sau do load anh master va ve vao canvas moi dung kich thuoc layer:

```text
targetWidth  = placedLayer.width  hoac bounds.width
targetHeight = placedLayer.height hoac bounds.height
```

Layer Design chi bi thay:

```js
designLayer.canvas = layerCanvas
designLayer[OFFOREST_REPLACED_DESIGN_LAYER] = true
```

Khong xoa:

```text
designLayer.effects
designLayer.opacity
designLayer.blendMode
designLayer.mask
designLayer.placedLayer
```

Day la ly do renderer co the giu effect cua PSD goc.

### Important Size Rule

Mac dinh khong trim canvas design:

```env
OFFOREST_TRIM_MOCKUP_DESIGN=false
```

Anh master phai duoc fit vao layer Design ma khong lam meo ti le:

```js
const scale = Math.min(targetWidth / imageWidth, targetHeight / imageHeight)
ctx.drawImage(image, left, top, imageWidth * scale, imageHeight * scale)
```

Khong stretch truc tiep bang `ctx.drawImage(image, 0, 0, targetWidth, targetHeight)` cho sticker mockup, vi neu ti le anh master khac ti le layer Design thi anh se bi bop/det/meo.

Truoc khi replace, renderer phai doc alpha bounds cua layer `Design` goc trong PSD. Day la vung pixel that cua Design cu. Anh master moi chi duoc fit trong vung nay, khong fit vao toan bo canvas/placed layer neu canvas co padding trong suot.

Quy tac size hien tai:

```text
target canvas = kich thuoc layer/placedLayer de giu transform PSD
target rect   = alpha bounds cua Design goc ben trong target canvas
fit rect      = target rect * OFFOREST_MOCKUP_DESIGN_SCALE, can giua
master image  = trim transparent bounds, fit dong ti le vao fit rect
```

Dieu nay giu ca 2 yeu cau:

- Khong stretch lam meo anh.
- Khong phong to hon vung `Design` goc trong PSD.

Ly do khong stretch: Photoshop smart object/mockup thuong mong layer Design giu dung ti le hinh, roi transform layer vao tung vi tri mockup. Neu can fill kin ca width va height trong khi ti le khac nhau thi bat buoc se meo; voi sticker, uu tien giu ti le dung hon fill kin tuyet doi.

Chi bat `OFFOREST_TRIM_MOCKUP_DESIGN=true` khi that su muon crop transparent bounds cua anh master.

### Render Full PSD Stack

Renderer phai render full PSD stack, khong chi render children cua group `MOCKUP *`.

Dung logic:

```text
for each MOCKUP group:
  hide all MOCKUP groups
  show current MOCKUP group
  render psd.children full stack
  export PNG
  restore visibility
```

Ly do:

- PSD co the co background chung nam ngoai folder mockup.
- PSD co the co logo/text/overlay chung nam ngoai folder mockup.
- PSD co the co adjustment/mask/effect phu thuoc vao layer ben ngoai group.

Neu chi render children cua tung group, mot so mockup se mat Design, mat nen, mat logo, hoac mat effect.

### Transform And Perspective

Renderer can doc transform tu `placedLayer`:

```text
placedLayer.nonAffineTransform
placedLayer.transform
```

Neu transform la affine, draw bang canvas transform.
Neu la non-affine/perspective, fallback ve quad/tam giac de anh nam dung vi tri hon.

Dieu nay quan trong cho PSD co smart object/placed layer bi nghieng, xoay, perspective.

### Effects

Renderer giu effect bang cach:

1. Chi thay `designLayer.canvas`.
2. Giu `designLayer.effects`.
3. Khi render layer Design da replace, doc effects va ve lai bang canvas.

Mac dinh:

```text
OFFOREST_ENABLE_CUSTOM_EFFECTS=false
```

Khi false:

- Chi apply custom effects cho layer Design/Desgin vua replace.
- Cac layer khac khong bi apply lai custom effects de tranh render sai PSD.

Khi true:

- Apply custom effects cho nhieu layer hon.
- Chi nen dung de debug hoac PSD can custom pass toan bo.

Effects dang duoc xu ly:

```text
dropShadow
innerShadow
outerGlow
innerGlow
bevel
solidFill
stroke
```

Renderer co padding cho shadow/glow de tranh effect bi cat ngang.

Thu tu ve co ban:

```text
dropShadow
outerGlow
source design
solidFill
innerShadow
innerGlow
bevel
stroke
```

### Mask, Opacity, Blend Mode, Clipping

Renderer can tiep tuc ton trong:

```text
layer.mask
layer.opacity
layer.fillOpacity
layer.blendMode
clipping layer
```

Khi sua renderer, khong duoc bo qua cac phan nay vi PSD mockup thuong phu thuoc vao mask/effect/blend.

## Output Mapping

Database co cac cot:

```text
mockup1
mockup2
mockup3
...
mockup11
```

Trong Sticker flow hien tai:

- `mockup1` den `mockup11`: cac output duoc tao theo thu tu. Output nao tao truoc ghi vao slot trong dau tien.
- PSD custom mockup khong reserve slot rieng; neu chay truoc thi bat dau tu `mockup1`.

Mapping:

```text
MOCKUP 1 -> first empty mockup slot
MOCKUP 2 -> mockup3
MOCKUP 3 -> mockup4
...
MOCKUP 10 -> mockup11
```

UI o `3. Mockup Tu Chon` chi dem va hien cac slot co du lieu that:

```php
$psdMockups = collect(range(1, 11))
    ->map(...)
    ->filter(fn ($mockup) => filled($mockup['original']));
```

Neu PSD chi render duoc 6 anh, UI hien `6 MOCKUP`, khong hien placeholder cho cac slot trong.

Khi render PSD, repository append output vao slot trong dau tien tu `mockup1` den `mockup11`. Khong clear output cu neu user khong yeu cau reset/replace.

Preview URL cho local `/storage/...` can co cache-bust theo `filemtime`, vi renderer co the ghi lai cung ten file PNG trong folder:

```text
/storage/generated/sticker/mockups/{assetId}/MOCKUP 1.png?v={filemtime}
```

Khong cache-bust thi trinh duyet co the van hien anh render cu, lam user tuong renderer dang lay anh master dau tien thay vi anh dang show o `2. Create Master`.

## UI Rules For 3. Mockup Tu Chon

File:

```text
resources/views/livewire/pages/sticker/product-design-card.blade.php
```

O `3. Mockup Tu Chon` phai nam cung hang va cung kich thuoc voi cac o:

```text
1. Source Image
2. Create Master
3. Mockup Tu Chon
```

Main preview box dung:

```html
class="relative aspect-[4/4.45] overflow-hidden rounded-xl ..."
```

Danh sach thumbnail nam trong khung va scroll noi bo:

```html
class="flex h-full min-h-0 flex-col"
class="min-h-0 flex-1 overflow-y-auto pr-1"
```

Khong dung `mt-3` cho main mockup box vi se lam o 4 bi lech xuong so voi cac o khac.

Thumbnail layout:

```text
2 columns
aspect 4/3
object-cover
```

PSD selector nam ben duoi khung mockup, tuong tu footer/action cua cac o khac.

## Common Errors

### Missing renderer command

Message:

```text
Chua cau hinh PSD renderer. Can set PSD_MOCKUP_RENDERER_COMMAND de render layer Design.
```

Fix:

1. Them vao `.env`:

```env
PSD_MOCKUP_RENDERER_COMMAND="node scripts/psd-renderer/render.js"
```

2. Clear config:

```bash
php artisan optimize:clear
```

### Missing Design layer

Message:

```text
Khong tim thay layer Design hoac Desgin trong PSD.
```

Fix:

- Mo PSD va dat ten layer can thay la `Design`.
- Neu PSD bi go sai, renderer cung chap nhan `Desgin`.

### Missing MOCKUP folders

Message:

```text
Khong tim thay folder MOCKUP * trong PSD.
```

Fix:

- PSD can co folder/group ten `MOCKUP 1`, `MOCKUP 2`, ...

### Design appears too large

Likely cause:

- Dang trim transparent bounds cua anh master.

Fix:

```env
OFFOREST_TRIM_MOCKUP_DESIGN=false
```

### Some mockups lose Design

Likely causes:

- Renderer chi render children cua group `MOCKUP *`, khong render full PSD stack.
- Visibility cua cac group `MOCKUP *` khong duoc toggle dung.
- Layer Design nam ngoai/ben trong group theo cau truc PSD dac biet.

Fix:

- Giu logic full PSD stack.
- Moi lan export chi bat 1 group `MOCKUP *`.

### Design loses shadow/glow/effect

Likely causes:

- Da thay layer Design nhung khong apply lai `layer.effects`.
- Effect bi cat do canvas khong co padding.
- `OFFOREST_ENABLE_CUSTOM_EFFECTS` bi dung sai muc dich.

Fix:

- Chi thay `designLayer.canvas`, khong xoa metadata.
- Giu custom effect pass cho replaced Design layer.
- Dam bao shadow/glow co padding.

## Verification

Sau khi sua code renderer:

```bash
node --check scripts/psd-renderer/render.js
php artisan test
npm run build
```

Sau khi chi sua Blade UI:

```bash
php artisan test
npm run build
```

Neu `npm run build` hoac Blade cache gap loi file view tren Windows, co the chay:

```bash
php artisan view:clear
```

roi build lai.

## Current Status

Tinh den 2026-05-29:

- Da co upload/chon PSD rieng cho Sticker custom mockup.
- Da co rule user co nhieu PSD nhung moi function chi 1 PSD active.
- Da co Node renderer doc PSD bang `ag-psd`.
- Da thay layer `Design`/`Desgin` bang anh master.
- Da render full PSD stack va toggle `MOCKUP *`.
- Da support transform/perspective co ban.
- Da giu effect cua layer Design bang custom canvas effect pass.
- Da output PNG vao `storage/app/public/generated/sticker/mockups/{assetId}`.
- UI chi hien so mockup render duoc, hien nho trong o 4 va scroll noi bo.
- O `3. Mockup Tu Chon` da can bang kich thuoc voi cac o 1, 2.
