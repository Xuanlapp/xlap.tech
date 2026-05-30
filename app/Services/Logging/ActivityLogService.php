<?php

namespace App\Services\Logging;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class ActivityLogService
{
    /**
     * Record a user, admin, or system activity in a normalized format.
     *
     * @param  array<string, mixed>  $properties
     */
    public function record(
        string $event,
        ?string $description = null,
        ?Model $subject = null,
        array $properties = [],
        ?User $actor = null,
        string $actorType = 'user',
    ): ActivityLog {
        $actor ??= auth()->user();
        $actorType = $actor ? ((bool) $actor->is_admin ? 'admin' : $actorType) : $actorType;

        return ActivityLog::create([
            'user_id' => $actor?->id,
            'actor_type' => $actor ? $actorType : 'system',
            'event' => $event,
            'description' => $description,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'properties' => $properties,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'occurred_at' => now(),
        ]);
    }
}
