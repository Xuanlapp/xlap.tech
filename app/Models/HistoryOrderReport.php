<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistoryOrderReport extends Model
{
    protected $table = 'history_order_report';

    protected $fillable = [
        'user_id',
        'order_id',
        'sku',
        'size',
        'quantity',
        'images_link',
        'report_data',
        'ordered_at',
    ];

    protected function casts(): array
    {
        return [
            'images_link' => 'array',
            'report_data' => 'array',
            'ordered_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
