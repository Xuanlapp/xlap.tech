<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'access_token', 'refresh_token', 'expires_at', 'scope', 'is_active'])]
class GoogleDriveConnection extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * User who connected this Google Drive account.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
