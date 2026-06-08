<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductDriveUpload extends Model
{
    protected $fillable = [
        'product_design_asset_id',
        'user_id',
        'product_id',
        'status',
        'file_info',
        'drive_files',
        'drive_folder_id',
        'drive_folder_link',
        'error',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'file_info' => 'array',
            'drive_files' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(ProductDesignAsset::class, 'product_design_asset_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
