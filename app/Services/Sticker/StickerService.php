<?php

namespace App\Services\Sticker;

use App\Models\Product;
use App\Models\ProductDesignAsset;
use App\Models\User;
use App\Repositories\Product\ProductDesignAssetRepository;
use App\Repositories\Product\ProductRepository;
use App\Repositories\Prompt\PromptRepository;
use App\Services\Vertex\VertexImageGenerator;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;
use RuntimeException;

class StickerService
{
    private const MAX_KEYWORD_LENGTH = 255;

    private const MAX_IMAGE_LINK_LENGTH = 1000;

    public function __construct(
        private readonly ProductRepository $products,
        private readonly ProductDesignAssetRepository $assets,
        private readonly PromptRepository $prompts,
        private readonly VertexImageGenerator $generator,
        private readonly PsdMockupTemplateService $psdTemplates,
        private readonly PsdMockupRenderer $psdRenderer,
    ) {}

    public function product(): Product
    {
        return $this->products->findActiveBySlug('sticker');
    }

    /**
     * @return Collection<int, ProductDesignAsset>
     */
    public function assetsForUser(User $user): Collection
    {
        return $this->assets->forUserAndProduct($user->id, $this->product()->id);
    }

    public function createDraftAsset(User $user, string $keyword): ProductDesignAsset
    {
        return $this->assets->createDraft($user->id, $this->product()->id, $this->normalizeKeyword($keyword));
    }

    /**
     * Create one Sticker item with the user-provided keyword and source image URL.
     */
    public function createAsset(User $user, string $keyword, string $imageLink): ProductDesignAsset
    {
        return $this->assets->createWithSource(
            $user->id,
            $this->product()->id,
            $this->normalizeKeyword($keyword),
            $this->normalizeImageLink($imageLink),
        );
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
        $this->assetForUser($user, $assetId)->update(['keyword' => $this->normalizeKeyword($keyword)]);
    }

    public function updateImageLink(User $user, int $assetId, string $imageLink): void
    {
        $this->assetForUser($user, $assetId)->update(['image_link' => $this->normalizeImageLink($imageLink)]);
    }

    /**
     * Update editable source details for one Sticker item.
     */
    public function updateProductDetail(User $user, int $assetId, string $keyword, string $imageLink): ProductDesignAsset
    {
        return $this->assets->updateSourceDetails(
            $this->assetForUser($user, $assetId),
            $this->normalizeKeyword($keyword),
            $this->normalizeImageLink($imageLink),
        );
    }

    /**
     * Generate the master redesign image for one Sticker item.
     */
    public function generateRedesign(User $user, int $assetId): ProductDesignAsset
    {
        $asset = $this->assetForUser($user, $assetId);

        if (! $asset->image_link) {
            throw new RuntimeException('Dong nay chua co image_link.');
        }

        return $this->assets->updateRedesign(
            $asset,
            $this->generator->generate(
                user: $user,
                imageUri: $asset->image_link,
                prompt: $this->promptContent($user, 1),
                folder: 'generated/sticker/redesign',
            ),
        );
    }

    /**
     * Generate the two final Sticker images from the master redesign.
     */
    public function generateFinalImages(User $user, int $assetId): ProductDesignAsset
    {
        $asset = $this->assetForUser($user, $assetId);

        if (! $asset->redesign) {
            throw new RuntimeException('Can tao anh redesign truoc.');
        }

        $mockup1 = $this->generator->generate(
            user: $user,
            imageUri: $asset->redesign,
            prompt: $this->promptContent($user, 2),
            folder: 'generated/sticker/final',
        );

        $mockup2 = $this->generator->generate(
            user: $user,
            imageUri: $asset->redesign,
            prompt: $this->promptContent($user, 3),
            folder: 'generated/sticker/final',
        );

        return $this->assets->updateFinalImages($asset, $mockup1, $mockup2);
    }

    /**
     * Render custom PSD mockups by replacing the PSD layer named Design with the master image.
     */
    public function generatePsdMockups(User $user, int $assetId): ProductDesignAsset
    {
        $asset = $this->assetForUser($user, $assetId);

        if (! $asset->redesign) {
            throw new RuntimeException('Can tao anh master truoc khi render PSD.');
        }

        $template = $this->psdTemplates->activeStickerTemplateForUser($user);

        if (! $template) {
            throw new RuntimeException('Chua chon PSD mockup cho chuc nang nay.');
        }

        return $this->assets->updatePsdMockups(
            $asset,
            $this->psdRenderer->render($template, $asset->redesign, $asset->id),
        );
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

        if (! filter_var($imageLink, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('Link anh khong hop le.');
        }

        return $imageLink;
    }

    private function promptContent(User $user, int $promptNumber): string
    {
        $content = $this->prompts->contentForUserProductAndNumber($user->id, $this->product()->id, $promptNumber);

        if (! $content) {
            throw new RuntimeException("Chua co prompt so {$promptNumber} cho trang Sticker.");
        }

        return $content;
    }
}
