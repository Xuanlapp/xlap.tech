<?php

namespace App\Services\Glass;

use App\Models\Product;
use App\Models\ProductDesignAsset;
use App\Models\GlassLocalMockupJob;
use App\Models\PsdMockupTemplate;
use App\Models\User;
use App\Repositories\Product\ProductDesignAssetRepository;
use App\Repositories\Product\ProductRepository;
use App\Repositories\Prompt\PromptRepository;
use App\Models\UserApiCredential;
use App\Services\Ai\ApiKeyImageGenerator;
use App\Services\Ai\CheapKeyAiImageGenerator;
use App\Services\Product\ProductBackgroundRemovalService;
use App\Services\Product\ProductDesignAssetFileCleanupService;
use App\Services\Product\ProductDriveUploadQueueService;
use App\Services\Vertex\VertexImageGenerator;
use App\Services\Google\GoogleDriveService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;
use RuntimeException;

class GlassService
{
    private const MAX_KEYWORD_LENGTH = 255;

    private const MAX_IMAGE_LINK_LENGTH = 1000;

    /** Cache a remote source locally so browser canvas never touches Drive/CORS. */
    public function boundsOriginalUrl(User $user, int $assetId, ?string $sourceUrl = null): ?string
    {
        $asset = $this->assetForUser($user, $assetId);
        $sourceUrl = trim((string) ($sourceUrl ?: $asset->image_link));
        if ($sourceUrl === '') return null;

        // XLAP storage is already same-origin and safe for canvas; do not create
        // another copy when the source is already local.
        $sourcePath = parse_url($sourceUrl, PHP_URL_PATH) ?: '';
        if (str_starts_with((string) $sourcePath, '/storage/')) {
            return $sourceUrl;
        }

        $extension = 'png';
        // One stable cache per asset prevents repeated Drive downloads when the
        // same source is represented by different preview URLs.
        $cachePath = 'generated/glass/original-cache/'.$user->id.'/'.$asset->id.'/original.'.$extension;
        $disk = Storage::disk('public');
        $existing = collect($disk->files('generated/glass/original-cache/'.$user->id.'/'.$asset->id))
            ->first(fn (string $path): bool => preg_match('/\.(png|jpe?g|webp)$/i', $path) === 1);
        if ($existing && ! $disk->exists($cachePath)) {
            $cachePath = $existing;
        }
        if (! $disk->exists($cachePath)) {
            $path = $sourcePath;
            $driveId = null;
            if (preg_match('#/file/d/([^/]+)#', (string) $path, $m)) $driveId = $m[1];
            parse_str((string) parse_url($sourceUrl, PHP_URL_QUERY), $query);
            $driveId = $driveId ?: ($query['id'] ?? null);
            if ($driveId) {
                $download = app(GoogleDriveService::class)->downloadImageFile((string) $driveId);
                $bytes = $download['body'];
            } elseif (str_starts_with((string) $path, '/storage/')) {
                $local = ltrim(substr((string) $path, strlen('/storage/')), '/');
                if (! $disk->exists($local)) return null;
                $bytes = $disk->get($local);
            } else {
                $response = Http::timeout(30)->retry(1, 250)->get($sourceUrl);
                if (! $response->successful() || ! str_starts_with(strtolower($response->header('Content-Type', '')), 'image/')) return null;
                $bytes = $response->body();
            }
            if ($bytes === '' || strlen($bytes) > 52_428_800) return null;
            $disk->put($cachePath, $bytes);
        }
        return '/storage/'.$cachePath.'?v='.$disk->lastModified($cachePath);
    }

    /** Render Bounds on the server when a browser canvas is tainted by an external image. */
    public function saveBoundedCacheImage(User $user, int $assetId, array $transform, bool $asRedesign = true): ProductDesignAsset
    {
        $cachedUrl = $this->boundsOriginalUrl($user, $assetId);
        $path = parse_url((string) $cachedUrl, PHP_URL_PATH) ?: '';
        $relativePath = ltrim(substr($path, strlen('/storage/')), '/');
        $bytes = Storage::disk('public')->get($relativePath);
        $source = @imagecreatefromstring($bytes);
        if (! $source) throw new RuntimeException('Khong doc duoc XLAP Original cache de luu bounds.');

        $canvasWidth = max(1, min(12000, (int) ($transform['canvasWidth'] ?? 1203)));
        $canvasHeight = max(1, min(12000, (int) ($transform['canvasHeight'] ?? 1204)));
        $target = is_array($transform['target'] ?? null) ? $transform['target'] : [];
        $targetX = (float) ($target['x'] ?? 0);
        $targetY = (float) ($target['y'] ?? 0);
        $targetWidth = (float) ($target['width'] ?? $canvasWidth);
        $targetHeight = (float) ($target['height'] ?? $canvasHeight);
        $scale = max(0.01, (float) ($transform['scale'] ?? 1));
        $offsetX = (float) ($transform['offsetX'] ?? 0);
        $offsetY = (float) ($transform['offsetY'] ?? 0);

        $output = imagecreatetruecolor($canvasWidth, $canvasHeight);
        imagealphablending($output, false);
        imagesavealpha($output, true);
        $transparent = imagecolorallocatealpha($output, 0, 0, 0, 127);
        imagefill($output, 0, 0, $transparent);
        imagealphablending($output, true);
        imagecopyresampled($output, $source, (int) round($offsetX), (int) round($offsetY), 0, 0, (int) round(imagesx($source) * $scale), (int) round(imagesy($source) * $scale), imagesx($source), imagesy($source));
        imagedestroy($source);

        $centerX = $targetX + $targetWidth / 2;
        $centerY = $targetY + $targetHeight / 2;
        $radius = min($targetWidth, $targetHeight) / 2;
        for ($y = 0; $y < $canvasHeight; $y++) {
            for ($x = 0; $x < $canvasWidth; $x++) {
                if (($x - $centerX) ** 2 + ($y - $centerY) ** 2 > $radius ** 2) imagesetpixel($output, $x, $y, $transparent);
            }
        }
        ob_start(); imagepng($output, null, 6); $png = ob_get_clean(); imagedestroy($output);
        if (! is_string($png) || $png === '') throw new RuntimeException('Khong the render PNG bounds tu XLAP Original.');
        return $this->saveBoundedImage($user, $assetId, $png, $asRedesign);
    }

    /** Promote the server-cached original to the active Create Master before editing bounds. */
    public function useCachedOriginalForBounds(User $user, int $assetId, ?string $sourceUrl = null): ProductDesignAsset
    {
        $asset = $this->assetForUser($user, $assetId);
        $this->ensureNotApproved($asset);
        $this->ensureMasterEditable($asset);
        $cachedUrl = $this->boundsOriginalUrl($user, $assetId, $sourceUrl);
        if (! $cachedUrl) throw new RuntimeException('Khong tai duoc anh goc ve XLAP Original.');
        $cachedPath = strtok($cachedUrl, '?');
        $source = trim((string) ($sourceUrl ?: $asset->image_link));
        $isSourceImage = $source !== '' && $source === (string) $asset->image_link;
        if ($isSourceImage) {
            $asset->update(['image_link' => $cachedPath]);
        } else {
            $candidates = collect($asset->redesign_candidates ?: [])->map(
                fn ($candidate) => ($candidate['original'] ?? $candidate) === $source
                    ? (is_array($candidate) ? array_merge($candidate, ['original' => $cachedPath, 'src' => $cachedPath]) : $cachedPath)
                    : $candidate
            )->push($cachedPath)->filter()->unique()->values()->all();
            $asset->update(['redesign' => $cachedPath, 'redesign_candidates' => $candidates]);
        }
        return $asset->refresh();
    }

    private const MAX_CUSTOM_PROMPT_LENGTH = 4000;

    private ?Product $glassProduct = null;

    public function __construct(
        private readonly ProductRepository $products,
        private readonly ProductDesignAssetRepository $assets,
        private readonly PromptRepository $prompts,
        private readonly VertexImageGenerator $generator,
        private readonly ApiKeyImageGenerator $apiKeyGenerator,
        private readonly CheapKeyAiImageGenerator $cheapKeyAiGenerator,
        private readonly ProductBackgroundRemovalService $backgroundRemoval,
        private readonly ProductDriveUploadQueueService $driveUploadQueue,
        private readonly ProductDesignAssetFileCleanupService $fileCleanup,
        private readonly PsdMockupTemplateService $psdTemplates,
        private readonly PsdMockupRenderer $psdRenderer,
    ) {}

    public function product(): Product
    {
        return $this->glassProduct ??= $this->products->findActiveBySlug('glass');
    }

    /**
     * @return Collection<int, ProductDesignAsset>
     */
    public function assetsForUser(User $user): Collection
    {
        return $this->assets->forUserAndProduct($user->id, $this->product()->id);
    }

    /**
     * @return LengthAwarePaginator<ProductDesignAsset>
     */
    public function paginatedAssetsForUser(
        User $user,
        int $perPage,
        string $status = 'all',
        string $pageName = 'page',
        ?string $search = null,
    ): LengthAwarePaginator
    {
        return $this->assets->paginateForUserAndProduct(
            $user->id,
            $this->product()->id,
            $perPage,
            $status,
            $pageName,
            $search,
        );
    }

    /**
     * @return array{all: int, unapproved: int, approved: int}
     */
    public function statusCountsForUser(User $user, ?string $search = null): array
    {
        return $this->assets->statusCountsForUserAndProduct($user->id, $this->product()->id, $search);
    }

    /** @return array<string, string> */
    public function providerOptionsForUser(User $user): array
    {
        $configured = config('ai_providers.providers', []);
        $options = [];

        if ($user->vertexApiCredential()->exists()) {
            $options['vertex'] = $configured['vertex']['label'] ?? 'Vertex';
        }

        if (UserApiCredential::query()->where('provider_key', 'v98store')->where('function_key', 'glass')->where('is_active', true)->where(function ($query) use ($user): void {
            $query->where('user_id', $user->id)->orWhereNull('user_id');
        })->exists()) {
            $options['v98store'] = $configured['v98store']['label'] ?? 'v98Store';
        }

                if (UserApiCredential::query()->where('provider_key', 'cheapkeyai')->where('function_key', 'glass')->where('is_active', true)->where(function ($query) use ($user): void {
            $query->where('user_id', $user->id)->orWhereNull('user_id');
        })->exists()) {
            $options['cheapkeyai'] = $configured['cheapkeyai']['label'] ?? 'CheapKeyAI';
        }

        return $options;
    }

    /** @return array{ok: bool, remain_quota?: float|int, message?: string}|null */
    public function cheapKeyAiBalanceForUser(User $user, ?string $providerKey): ?array
    {
        if ($providerKey !== 'cheapkeyai') {
            return null;
        }

        $credential = UserApiCredential::query()
            ->where('provider_key', 'cheapkeyai')
            ->where('function_key', 'glass')
            ->where('is_active', true)
            ->where(function ($query) use ($user): void {
                $query->where('user_id', $user->id)->orWhereNull('user_id');
            })
            ->orderByRaw('CASE WHEN user_id = ? THEN 0 ELSE 1 END', [$user->id])
            ->first();

        if (! $credential) {
            return ['ok' => false, 'message' => 'No key'];
        }

        return (function () use ($credential): array {
            try {
                $key = trim((string) $credential->key_api);
                $endpoint = trim((string) config('services.api_key_providers.cheapkeyai.balance_endpoint'));
                $response = Http::timeout(10)->withToken($key)->get($endpoint);
                $payload = $response->json();

                $data = is_array($payload) ? ($payload['data'] ?? null) : null;
                $userBalance = is_array($data) && is_numeric($data['user_balance'] ?? null) ? (float) $data['user_balance'] : null;
                $keyRemainQuota = is_array($data) && is_numeric($data['key_remain_quota'] ?? null) ? (float) $data['key_remain_quota'] : null;
                $keyUnlimitedQuota = is_array($data) && ($data['key_unlimited_quota'] ?? false) === true;

                if ($response->failed() || ($payload['success'] ?? false) !== true || ! is_array($data) || $userBalance === null || (! $keyUnlimitedQuota && $keyRemainQuota === null)) {
                    return ['ok' => false, 'message' => 'Balance unavailable'];
                }

                $balance = $keyUnlimitedQuota || $keyRemainQuota === null
                    ? $userBalance
                    : $keyRemainQuota / 500000;

                return [
                    'ok' => true,
                    'balance' => $balance,
                    'name' => is_string($data['key_name'] ?? null) ? $data['key_name'] : null,
                    'key_unlimited_quota' => $keyUnlimitedQuota,
                ];
            } catch (Throwable) {
                return ['ok' => false, 'message' => 'Balance unavailable'];
            }
        })();
    }

    /** @return array<string, string> */
    public function imageModelOptionsForProvider(?string $providerKey): array
    {
        $providerKey = trim((string) $providerKey);
        $options = config("ai_providers.providers.{$providerKey}.image_models", []);

        return is_array($options) ? $options : [];
    }

    /** @return array{ok: bool, remain_quota?: float|int, message?: string}|null */
    public function v98StoreBalanceForUser(User $user, ?string $providerKey): ?array
    {
        if ($providerKey !== 'v98store') {
            return null;
        }

        $credential = UserApiCredential::query()
            ->where('provider_key', 'v98store')
            ->where('function_key', 'glass')
            ->where('is_active', true)
            ->where(function ($query) use ($user): void {
                $query->where('user_id', $user->id)->orWhereNull('user_id');
            })
            ->orderByRaw('CASE WHEN user_id = ? THEN 0 ELSE 1 END', [$user->id])
            ->first();

        if (! $credential) {
            return ['ok' => false, 'message' => 'No key'];
        }

        return Cache::remember("v98store-balance:{$credential->id}", now()->addSeconds(15), function () use ($credential): array {
            try {
                $key = trim((string) $credential->key_api);
                $endpoint = trim((string) config('services.api_key_providers.v98store.balance_endpoint'));
                $response = Http::timeout(10)->withToken($key)->get($endpoint);
                $payload = $response->json();

                $data = is_array($payload) ? ($payload['data'] ?? null) : null;
                $userBalance = is_array($data) && is_numeric($data['user_balance'] ?? null) ? (float) $data['user_balance'] : null;
                $keyRemainQuota = is_array($data) && is_numeric($data['key_remain_quota'] ?? null) ? (float) $data['key_remain_quota'] : null;
                $keyUnlimitedQuota = is_array($data) && ($data['key_unlimited_quota'] ?? false) === true;

                if ($response->failed() || ($payload['success'] ?? false) !== true || ! is_array($data) || $userBalance === null || (! $keyUnlimitedQuota && $keyRemainQuota === null)) {
                    return ['ok' => false, 'message' => 'Balance unavailable'];
                }

                $balance = $keyUnlimitedQuota || $keyRemainQuota === null
                    ? $userBalance
                    : $keyRemainQuota / 500000;

                return [
                    'ok' => true,
                    'balance' => $balance,
                    'name' => is_string($data['key_name'] ?? null) ? $data['key_name'] : null,
                    'key_unlimited_quota' => $keyUnlimitedQuota,
                ];
            } catch (Throwable) {
                return ['ok' => false, 'message' => 'Balance unavailable'];
            }
        });
    }

    public function createDraftAsset(User $user, string $keyword): ProductDesignAsset
    {
        return $this->assets->createDraft($user->id, $this->product()->id, $this->normalizeKeyword($keyword));
    }

    public function skuExistsForCurrentProduct(User $user, string $sku): bool
    {
        return $this->assets->skuExistsForUserAndProduct($user->id, $this->product()->id, trim($sku));
    }

    /**
     * Create one Glass item with the user-provided keyword and source image URL.
     */
    public function createAsset(User $user, string $keyword, string $imageLink, ?string $sku = null): ProductDesignAsset
    {
        return $this->assets->createWithSource(
            $user->id,
            $this->product()->id,
            $this->normalizeKeyword($keyword),
            $this->normalizeImageLink($imageLink),
            $sku === null ? null : $this->normalizeSku($sku),
        );
    }


    /**
     * Import one Glass row with optional source image, required master image, and optional mockups.
     *
     * @param array<int, string> $mockups
     */
    public function importAsset(User $user, string $sku, string $keyword, ?string $sourceImage, string $masterImage, array $mockups = []): ProductDesignAsset
    {
        $sku = $this->normalizeSku($sku);
        $keyword = $this->normalizeKeyword($keyword);
        $sourceImage = trim((string) $sourceImage);
        $masterImage = $this->normalizeImageLink($masterImage);
        $mockups = collect($mockups)
            ->map(fn (string $mockup): string => $this->normalizeImageLink($mockup))
            ->take(6)
            ->values()
            ->all();

        $asset = $this->assets->createImportedGlass(
            $user->id,
            $this->product()->id,
            $sku,
            $keyword,
            $sourceImage === '' ? null : $this->normalizeImageLink($sourceImage),
            $masterImage,
            $mockups,
        );

        $this->driveUploadQueue->syncForAsset($asset);

        return $asset;
    }

    public function saveLatestImageLink(User $user, string $imageLink): ProductDesignAsset
    {
        $asset = $this->assets->latestWithoutImageLink($user->id, $this->product()->id);

        if (! $asset) {
            throw new RuntimeException('Khong tim thay dong moi de luu link anh.');
        }

        $asset->update(['image_link' => $this->normalizeImageLink($imageLink)]);

        return $asset->refresh();
    }

    public function assetForUser(User $user, int $assetId): ProductDesignAsset
    {
        return $this->assets->findForUserAndProduct($assetId, $user->id, $this->product()->id);
    }

    public function updateKeyword(User $user, int $assetId, string $keyword): void
    {
        $asset = $this->assetForUser($user, $assetId);
        $this->ensureNotApproved($asset);
        $asset->update(['keyword' => $this->normalizeKeyword($keyword)]);
    }

    public function updateImageLink(User $user, int $assetId, string $imageLink): void
    {
        $asset = $this->assetForUser($user, $assetId);
        $this->ensureSourceDetailsEditable($asset);
        $asset->update(['image_link' => $this->normalizeImageLink($imageLink)]);
    }

    /** Save a client-cropped PNG as the Glass source or Create Master image. */
    public function saveBoundedImage(User $user, int $assetId, string $pngBytes, bool $asRedesign = false): ProductDesignAsset
    {
        $asset = $this->assetForUser($user, $assetId);
        if ($asRedesign) {
            $this->ensureNotApproved($asset);
            $this->ensureMasterEditable($asset);
        } else {
            // Bounds editing replaces the source PNG only; unlike editing the
            // source URL/keyword, it remains allowed after Create Master exists.
            $this->ensureNotApproved($asset);
        }

        if ($pngBytes === '' || strlen($pngBytes) > 40 * 1024 * 1024) {
            throw new InvalidArgumentException('Anh crop khong hop le hoac qua lon.');
        }

        $image = @imagecreatefromstring($pngBytes);
        if (! $image) {
            throw new InvalidArgumentException('Khong doc duoc PNG sau khi crop.');
        }

        $width = imagesx($image);
        $height = imagesy($image);
        if ($width < 1 || $height < 1 || $width > 12000 || $height > 12000) {
            imagedestroy($image);
            throw new InvalidArgumentException('Kich thuoc anh crop khong hop le.');
        }

        imagealphablending($image, false);
        imagesavealpha($image, true);
        ob_start();
        imagepng($image, null, 6);
        $normalizedPng = ob_get_clean();
        imagedestroy($image);

        if (! is_string($normalizedPng) || $normalizedPng === '') {
            throw new RuntimeException('Khong the tao PNG moi.');
        }

        $path = 'generated/glass/bounds/'.$user->id.'/'.$asset->id.'/'.now()->format('YmdHis').'-'.bin2hex(random_bytes(4)).'.png';
        Storage::disk('public')->put($path, $normalizedPng);
        $imageLink = '/storage/'.$path;
        if ($asRedesign) {
            $candidates = collect($asset->redesign_candidates ?: [])->push($imageLink)->filter()->unique()->values()->all();
            $asset->update(['redesign' => $imageLink, 'redesign_candidates' => $candidates]);
        } else {
            $asset->update(['image_link' => $imageLink]);
        }

        return $asset->refresh();
    }

    /**
     * Update editable source details for one Glass item.
     */
    public function updateProductDetail(User $user, int $assetId, string $keyword, string $imageLink): ProductDesignAsset
    {
        $asset = $this->assetForUser($user, $assetId);

        $this->ensureSourceDetailsEditable($asset);

        return $this->assets->updateSourceDetails(
            $asset,
            $this->normalizeKeyword($keyword),
            $this->normalizeImageLink($imageLink),
        );
    }

    /**
     * Generate the master redesign image for one Glass item.
     */
    public function generateRedesign(User $user, int $assetId, ?string $providerKey = null, ?string $imageModel = null): ProductDesignAsset
    {
        $asset = $this->assetForUser($user, $assetId);
        $this->ensureNotApproved($asset);
        $this->ensureMasterEditable($asset);

        if (! $asset->image_link) {
            throw new RuntimeException('Dong nay chua co image_link.');
        }

        $providerKey = $this->normalizeProviderKey($user, $providerKey);

        return $this->assets->updateRedesignWithProvider(
            $asset,
            $this->generateImage(
                user: $user,
                providerKey: $providerKey,
                imageUri: $asset->image_link,
                prompt: $this->promptContent($user, 1),
                folder: 'generated/glass/redesign',
                removeBackground: $this->backgroundRemoval->enabledFor($this->product()),
                imageModel: $imageModel,
            ),
            $providerKey,
        );
    }

    /**
     * Select an existing generated master image as the current Glass redesign.
     */
    public function selectRedesign(User $user, int $assetId, string $redesign): ProductDesignAsset
    {
        $asset = $this->assetForUser($user, $assetId);
        $this->ensureNotApproved($asset);
        $this->ensureMasterEditable($asset);

        return $this->assets->selectRedesign($asset, $this->normalizeImageLink($redesign));
    }

    /**
     * Generate a new Glass master image from the reviewed master image and a custom prompt.
     */
    public function customizeRedesign(User $user, int $assetId, string $imageLink, string $prompt): ProductDesignAsset
    {
        $asset = $this->assetForUser($user, $assetId);
        $this->ensureNotApproved($asset);
        $this->ensureMasterEditable($asset);

        $providerKey = $this->normalizeProviderKey($user, $asset->ai_provider_key);

        return $this->assets->updateRedesignWithProvider(
            $asset,
            $this->generateImage(
                user: $user,
                providerKey: $providerKey,
                imageUri: $this->normalizeImageLink($imageLink),
                prompt: $this->normalizeCustomPrompt($prompt),
                folder: 'generated/glass/redesign',
                removeBackground: $this->backgroundRemoval->enabledFor($this->product()),
                imageModel: null,
            ),
            $providerKey,
        );
    }

    /**
     * Remove a generated master image from the candidate list after it becomes a new item.
     */
    public function removeRedesignCandidate(User $user, int $assetId, string $redesign): ProductDesignAsset
    {
        $asset = $this->assetForUser($user, $assetId);

        return $this->assets->removeRedesignCandidate($asset, $this->normalizeImageLink($redesign));
    }

    /**
     * Generate the two final Glass images from the master redesign.
     */
    public function generateFinalImages(User $user, int $assetId, ?string $providerKey = null, ?string $imageModel = null): ProductDesignAsset
    {
        $asset = $this->assetForUser($user, $assetId);
        $this->ensureNotApproved($asset);

        if (! $asset->redesign) {
            throw new RuntimeException('Can tao anh redesign truoc.');
        }

        $lifestyle1 = $this->generateImage(
            user: $user,
            providerKey: $providerKey,
            imageUri: $asset->redesign,
            prompt: $this->promptContent($user, 2),
            folder: 'generated/glass/final',
            imageModel: $imageModel,
        );

        $lifestyle2 = $this->generateImage(
            user: $user,
            providerKey: $providerKey,
            imageUri: $asset->redesign,
            prompt: $this->promptContent($user, 3),
            folder: 'generated/glass/final',
            imageModel: $imageModel,
        );

        $lifestyle3 = $this->generateImage(
            user: $user,
            providerKey: $providerKey,
            imageUri: $asset->redesign,
            prompt: $this->promptContent($user, 4),
            folder: 'generated/glass/final',
            imageModel: $imageModel,
        );

        return $this->assets->updateLifestyleImages($asset, $lifestyle1, $lifestyle2, $lifestyle3);
    }

    /** Queue custom PSD mockups for the synced local workstation. */
    public function generatePsdMockups(User $user, int $assetId): ProductDesignAsset
    {
        $asset = $this->assetForUser($user, $assetId);
        $this->ensureNotApproved($asset);

        if (! $asset->redesign) {
            throw new RuntimeException('Can tao anh master truoc khi render PSD.');
        }

        $template = $this->psdTemplates->activeGlassTemplateForUser($user);

        if (! $template) {
            throw new RuntimeException('Chua chon PSD mockup cho chuc nang nay.');
        }

        $existing = GlassLocalMockupJob::query()
            ->where('product_design_asset_id', $asset->id)
            ->whereIn('status', ['waiting', 'processing'])
            ->first();

        if ($existing) {
            throw new RuntimeException('Mockup nay dang cho may local xu ly.');
        }

        GlassLocalMockupJob::create([
            'job_uuid' => (string) Str::uuid(),
            'product_id' => $asset->product_id,
            'product_slug' => $this->product()->slug,
            'product_design_asset_id' => $asset->id,
            'psd_mockup_template_id' => $template->id,
            'master_image_uri' => $asset->redesign,
            'status' => 'waiting',
        ]);

        return $asset;
    }

    public function latestLocalMockupJob(ProductDesignAsset $asset): ?GlassLocalMockupJob
    {
        return GlassLocalMockupJob::query()
            ->where('product_design_asset_id', $asset->id)
            ->latest('id')
            ->first();
    }

    /** Render a claimed job on the local synced workstation and publish its image links to the shared database. */
    public function completeLocalMockupJob(GlassLocalMockupJob $job): ProductDesignAsset
    {
        if ($job->status !== 'processing') {
            throw new RuntimeException('Glass mockup job chua duoc worker nhan.');
        }

        $asset = ProductDesignAsset::query()->findOrFail($job->product_design_asset_id);

        if ($job->product_slug !== 'glass' || $job->product_id !== $asset->product_id || $asset->product_id !== $this->product()->id) {
            throw new RuntimeException('Glass mockup job khong khop san pham.');
        }

        $template = PsdMockupTemplate::query()->findOrFail($job->psd_mockup_template_id);

        if ($template->product_id !== $asset->product_id || $template->user_id !== $asset->user_id) {
            throw new RuntimeException('PSD template khong thuoc dung item Glass.');
        }
        $outputs = $this->psdRenderer->render($template, $job->master_image_uri, $asset->id);
        $asset = $this->assets->updatePsdMockups($asset, $outputs);

        $job->update([
            'status' => 'completed',
            'output_urls' => $outputs,
            'completed_at' => now(),
            'error_message' => null,
        ]);

        return $asset;
    }

    /**
     * Toggle approval after the item has at least one Lifestyle or mockup output.
     */
    public function toggleApproval(User $user, int $assetId): ProductDesignAsset
    {
        $asset = $this->assetForUser($user, $assetId);

        if (! $asset->hasApprovableOutput()) {
            throw new RuntimeException('Can co it nhat mot anh mockup hoac lifestyle truoc khi duyet.');
        }

        $asset = $this->assets->setApproval($asset, ! $asset->is_approved);

        $this->driveUploadQueue->syncForAsset($asset);

        return $asset;
    }

    /**
     * Remove generated PSD mockups while keeping the Glass item and Create Master intact.
     */
    public function clearPsdMockups(User $user, int $assetId): ProductDesignAsset
    {
        $asset = $this->assetForUser($user, $assetId);
        $job = $this->latestLocalMockupJob($asset);

        if (in_array($job?->status, ['waiting', 'processing'], true)) {
            throw new RuntimeException('Mockup dang render, khong the xoa luc nay.');
        }

        $paths = collect(range(1, 6))
            ->map(fn (int $slot): mixed => $asset->getAttribute("mockup{$slot}"))
            ->filter(fn (mixed $url): bool => is_string($url) && str_starts_with($url, '/storage/'))
            ->map(fn (string $url): string => ltrim(substr($url, strlen('/storage/')), '/'))
            ->values()
            ->all();

        if ($paths !== []) {
            Storage::disk('public')->delete($paths);
        }

        $updates = [];
        for ($slot = 1; $slot <= 6; $slot++) {
            $updates["mockup{$slot}"] = null;
        }
        $asset->update($updates);

        return $asset->refresh();
    }
    /**
     * Delete one Glass item owned by the user.
     */
    public function deleteAsset(User $user, int $assetId): ProductDesignAsset
    {
        $asset = $this->assetForUser($user, $assetId);

        $this->fileCleanup->deleteLocalFiles($asset, 'glass');
        $this->assets->delete($asset);

        return $asset;
    }

    private function generateImage(
        User $user,
        ?string $providerKey,
        string $imageUri,
        string $prompt,
        string $folder,
        bool $removeBackground = false,
        ?string $imageModel = null,
    ): string {
        $providerKey = $this->normalizeProviderKey($user, $providerKey);

        if ($providerKey === 'vertex') {
            return $this->generator->generate(
                user: $user,
                imageUri: $imageUri,
                prompt: $prompt,
                folder: $folder,
                removeBackground: $removeBackground,
            );
        }

        if ($providerKey === 'cheapkeyai') {
            return $this->cheapKeyAiGenerator->generate(
                user: $user,
                imageUri: $imageUri,
                prompt: $prompt,
                folder: $folder,
                removeBackground: $removeBackground,
                model: $imageModel,
                functionKey: 'glass',
            );
        }

        return $this->apiKeyGenerator->generate(
            user: $user,
            providerKey: $providerKey,
            imageUri: $imageUri,
            prompt: $prompt,
            folder: $folder,
            removeBackground: $removeBackground,
            model: $imageModel,
            functionKey: 'glass',
        );
    }

    private function normalizeProviderKey(User $user, ?string $providerKey): string
    {
        $options = $this->providerOptionsForUser($user);
        $candidate = Str::lower(trim((string) ($providerKey ?: $user->activeAiProviderKey() ?: '')));

        if ($candidate !== '' && array_key_exists($candidate, $options)) {
            return $candidate;
        }

        $fallback = array_key_first($options);

        if (is_string($fallback)) {
            return $fallback;
        }

        throw new RuntimeException('Tai khoan nay chua co Vertex hoac v98Store API key active.');
    }

    private function ensureNotApproved(ProductDesignAsset $asset): void
    {
        if ($asset->is_approved) {
            throw new RuntimeException('Item da duyet. Hay bo duyet truoc khi edit.');
        }
    }

    private function ensureSourceDetailsEditable(ProductDesignAsset $asset): void
    {
        $this->ensureNotApproved($asset);

        if ($asset->redesign) {
            throw new RuntimeException('Item da co Create Master nen khong the edit.');
        }
    }

    private function ensureMasterEditable(ProductDesignAsset $asset): void
    {
        if ($asset->hasCustomMockupOutput()) {
            throw new RuntimeException('Item da co Mockup Tu Chon nen khong the tao lai Create Master.');
        }
    }

    private function normalizeSku(string $sku): string
    {
        $sku = trim($sku);

        if ($sku === '') {
            throw new InvalidArgumentException('Sku khong duoc de trong.');
        }

        if (mb_strlen($sku) > 100) {
            throw new InvalidArgumentException('Sku khong duoc qua 100 ky tu.');
        }

        return $sku;
    }

    private function normalizeKeyword(string $keyword): string
    {
        $keyword = trim($keyword);

        if ($keyword === '') {
            throw new InvalidArgumentException('Keyword khong duoc de trong.');
        }

        if (mb_strlen($keyword) > self::MAX_KEYWORD_LENGTH) {
            throw new InvalidArgumentException('Keyword khong duoc qua '.self::MAX_KEYWORD_LENGTH.' ky tu.');
        }

        return $keyword;
    }

    private function normalizeImageLink(string $imageLink): string
    {
        $imageLink = trim($imageLink);

        if ($imageLink === '') {
            throw new InvalidArgumentException('Link anh khong duoc de trong.');
        }

        if (mb_strlen($imageLink) > self::MAX_IMAGE_LINK_LENGTH) {
            throw new InvalidArgumentException('Link anh khong duoc qua '.self::MAX_IMAGE_LINK_LENGTH.' ky tu.');
        }

        if (! str_starts_with($imageLink, '/storage/') && ! filter_var($imageLink, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('Link anh khong hop le.');
        }

        return $imageLink;
    }

    private function normalizeCustomPrompt(string $prompt): string
    {
        $prompt = trim($prompt);

        if ($prompt === '') {
            throw new InvalidArgumentException('Noi dung custom khong duoc de trong.');
        }

        if (mb_strlen($prompt) > self::MAX_CUSTOM_PROMPT_LENGTH) {
            throw new InvalidArgumentException('Noi dung custom khong duoc qua '.self::MAX_CUSTOM_PROMPT_LENGTH.' ky tu.');
        }

        return $prompt;
    }

    private function promptContent(User $user, int $promptNumber): string
    {
        $content = $this->prompts->contentForUserProductAndNumber($user->id, $this->product()->id, $promptNumber);

        if (! $content) {
            throw new RuntimeException("Chua co prompt so {$promptNumber} cho trang Glass.");
        }

        return $content;
    }
}





