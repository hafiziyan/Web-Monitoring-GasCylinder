<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLogService
{
    /**
     * Catat aktivitas penting. IP & User-Agent diambil otomatis dari request aktif.
     */
    public static function log(
        ?User $user,
        string $action,
        string $module,
        string $description,
        ?Model $auditable = null,
        array $oldValues = [],
        array $newValues = [],
        ?Request $request = null,
    ): AuditLog {
        $request ??= self::currentRequest();

        return AuditLog::create([
            'user_id' => $user?->id,
            'action' => $action,
            'module' => $module,
            'description' => $description,
            'auditable_type' => $auditable ? $auditable->getMorphClass() : null,
            'auditable_id' => $auditable?->getKey(),
            'old_values' => $oldValues === [] ? null : $oldValues,
            'new_values' => $newValues === [] ? null : $newValues,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }

    private static function currentRequest(): ?Request
    {
        try {
            return app()->has('request') ? request() : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
