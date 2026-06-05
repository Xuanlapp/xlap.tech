<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VertexApiCredential extends Model
{
    protected $fillable = [
        'user_id',
        'function_key',
        'project_id',
        'location',
        'client_email',
        'private_key',
        'credentials_json',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'private_key' => 'encrypted',
            'credentials_json' => 'encrypted:array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * User that owns this Vertex API credential.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
