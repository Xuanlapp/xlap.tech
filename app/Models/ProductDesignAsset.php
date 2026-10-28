<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use App\Services\Order\SkuOrderItemService;

class ProductDesignAsset extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'sku',
        'sku_pattern',
        'item_number',
        'keyword',
        'image_link',
        'image_sub',
        'data_item_add',
        'ai_provider_key',
        'title',
        'description',
        'bullet_point_1',
        'bullet_point_2',
        'bullet_point_3',
        'bullet_point_4',
        'bullet_point_5',
        'generic_keyword',
        'item_highlight',
        'tags',
        'marketplace_listing_status',
        'marketplace_listing_marketplace',
        'marketplace_listing_attempts',
        'marketplace_listing_started_at',
        'marketplace_listing_completed_at',
        'marketplace_listing_error',
        'marketplace_exported_at',
        'marketplace_export_filename',
        'redesign',
        'redesign_candidates',
        'mockup1',
        'mockup2',
        'mockup3',
        'mockup4',
        'mockup5',
        'mockup6',
        'mockup7',
        'mockup8',
        'mockup9',
        'mockup10',
        'mockup11',
        'is_approved',
        'approved_at',
        'drive_uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'is_approved' => 'boolean',
            'image_sub' => 'array',
            'data_item_add' => 'array',
            'redesign_candidates' => 'array',
            'approved_at' => 'datetime',
            'drive_uploaded_at' => 'datetime',
            'marketplace_listing_attempts' => 'integer',
            'marketplace_listing_started_at' => 'datetime',
            'marketplace_listing_completed_at' => 'datetime',
            'marketplace_exported_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (self $asset): void {
            DB::afterCommit(function () use ($asset): void {
                $freshAsset = $asset->fresh();
                if ($freshAsset) {
                    app(SkuOrderItemService::class)->syncForAsset($freshAsset);
                }
            });
        });
    }

    /**
     * Determine whether the item is ready for approval.
     */
    public function hasApprovableOutput(): bool
    {
        if (filled($this->redesign)) {
            return true;
        }

        return $this->hasCustomMockupOutput();
    }

    public function hasCustomMockupOutput(): bool
    {
        return $this->getCustomMockupCount() > 0;
    }

    public function getCustomMockupCount(): int
    {
        return collect(range(1, 11))->filter(fn (int $slot): bool => filled($this->getAttribute("mockup{$slot}")))->count();
    }

    /**
     * User that owns this design row.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Product page this design row belongs to.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function driveUpload(): HasOne
    {
        return $this->hasOne(ProductDriveUpload::class);
    }

    public function skuOrderItem(): HasOne
    {
        return $this->hasOne(SkuOrderItem::class);
    }

    public function ornamentAmazonTwoWorkflow(): HasOne
    {
        return $this->hasOne(OrnamentAmazonTwoWorkflow::class);
    }
}
