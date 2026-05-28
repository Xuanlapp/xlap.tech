<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductDesignAsset;
use App\Models\User;
use App\Repositories\ProductDesignAssetRepository;
use App\Repositories\ProductRepository;
use App\Repositories\PromptRepository;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;

class StickerService
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly ProductDesignAssetRepository $assets,
        private readonly PromptRepository $prompts,
        private readonly VertexImageGenerator $generator,
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
        return $this->assets->createDraft($user->id, $this->product()->id, $keyword);
    }

    public function saveLatestImageLink(User $user, string $imageLink): ProductDesignAsset
    {
        $asset = $this->assets->latestWithoutImageLink($user->id, $this->product()->id);

        if (! $asset) {
            throw new RuntimeException('Không tìm thấy dòng mới để lưu link ảnh.');
        }

        $asset->update(['image_link' => $imageLink]);

        return $asset;
    }

    public function assetForUser(User $user, int $assetId): ProductDesignAsset
    {
        return $this->assets->findForUserAndProduct($assetId, $user->id, $this->product()->id);
    }

    public function updateKeyword(User $user, int $assetId, string $keyword): void
    {
        $this->assetForUser($user, $assetId)->update(['keyword' => $keyword]);
    }

    public function updateImageLink(User $user, int $assetId, string $imageLink): void
    {
        $this->assetForUser($user, $assetId)->update(['image_link' => $imageLink]);
    }

    /**
     * Update editable source details for one Sticker item.
     */
    public function updateProductDetail(User $user, int $assetId, string $keyword, string $imageLink): ProductDesignAsset
    {
        $asset = $this->assetForUser($user, $assetId);

        $asset->update([
            'keyword' => $keyword,
            'image_link' => $imageLink,
        ]);

        return $asset;
    }

    public function generateRedesign(User $user, int $assetId): void
    {
        $asset = $this->assetForUser($user, $assetId);

        if (! $asset->image_link) {
            throw new RuntimeException('Dòng này chưa có image_link.');
        }

        $asset->update([
            'redesign' => $this->generator->generate(
                user: $user,
                imageUri: $asset->image_link,
                prompt: $this->promptContent($user, 1),
                folder: 'generated/sticker/redesign',
            ),
        ]);
    }

    public function generateFinalImages(User $user, int $assetId): void
    {
        $asset = $this->assetForUser($user, $assetId);

        if (! $asset->redesign) {
            throw new RuntimeException('Cần tạo ảnh redesign trước.');
        }

        $asset->update([
            'mockup1' => $this->generator->generate(
                user: $user,
                imageUri: $asset->redesign,
                prompt: $this->promptContent($user, 2),
                folder: 'generated/sticker/final',
            ),
            'mockup2' => $this->generator->generate(
                user: $user,
                imageUri: $asset->redesign,
                prompt: $this->promptContent($user, 3),
                folder: 'generated/sticker/final',
            ),
        ]);
    }

    private function promptContent(User $user, int $promptNumber): string
    {
        $content = $this->prompts->contentForUserProductAndNumber($user->id, $this->product()->id, $promptNumber);

        if (! $content) {
            throw new RuntimeException("Chưa có prompt số {$promptNumber} cho trang Sticker.");
        }

        return $content;
    }
}
