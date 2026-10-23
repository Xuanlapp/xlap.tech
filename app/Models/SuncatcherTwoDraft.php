<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuncatcherTwoDraft extends Model
{
    protected $table = 'product_data_search';
    protected $fillable = ['user_id', 'draft_key', 'workflow_step', 'status', 'payload'];

    protected function casts(): array
    {
        return ['payload' => 'array', 'workflow_step' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
