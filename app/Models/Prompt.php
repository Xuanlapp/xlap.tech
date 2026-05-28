<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prompt extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'prompt_number',
        'name',
        'content',
    ];

    /**
     * User that owns this prompt.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Product page this prompt belongs to.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
