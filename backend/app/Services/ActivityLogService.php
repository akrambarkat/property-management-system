<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

class ActivityLogService
{
    /**
     * Log an auditable action with before/after values.
     */
    public function log(
        string $action,
        ?Model $model = null,
        ?array $changes = null,
        ?string $description = null,
        ?int $userId = null,
    ): ActivityLog {
        $user = $userId
            ? \App\Models\User::find($userId)
            : (auth()->user() instanceof \App\Models\User ? auth()->user() : null);

        return ActivityLog::create([
            'user_id' => $user?->id ?? 0,
            'action' => $action,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model?->getKey(),
            'description' => $description,
            'ip_address' => request()->ip(),
            'user_agent' => substr((string) request()->userAgent(), 0, 500),
            'old_value' => $changes['old'] ?? null,
            'new_value' => $changes['new'] ?? null,
        ]);
    }

    /**
     * Convenience wrapper to record a settings change.
     */
    public function settingChanged(string $group, array $changedValues): void
    {
        $this->log(
            "settings.updated",
            null,
            ['old' => null, 'new' => $changedValues],
            "تحديث إعدادات مجموعة {$group}",
        );
    }
}
