<?php

namespace App\Services\Admin;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    public function log(string $action, string $description, ?string $entityType = null, ?int $entityId = null): void
    {
        ActivityLog::query()->create([
            'user_id' => Auth::id(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'description' => $description,
            'created_at' => now(),
        ]);
    }
}
