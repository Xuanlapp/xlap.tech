<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductDesignAsset extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'item_number',
        'keyword',
        'image_link',
        'redesign',
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
    ];

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
}
