<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PsdMockupTemplate extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'function_key',
        'name',
        'original_filename',
        'storage_path',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * User that owns this PSD template.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Product page this PSD template belongs to.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
