<?php
// ═══════════════════════════════════════════════════════════════════════════
// app/Traits/LogsActivity.php
// Add this trait to any Model — auto-logs create/update/delete
// ═══════════════════════════════════════════════════════════════════════════
namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait LogsActivity
{
    public static function bootLogsActivity(): void
    {
        static::created(function ($model) {
            self::writeLog('created', $model, null, $model->getAttributes());
        });

        static::updated(function ($model) {
            // Only log fields that actually changed
            $dirty = $model->getDirty();
            unset($dirty['updated_at'], $dirty['updated_by']);

            if (!empty($dirty)) {
                $old = array_intersect_key($model->getOriginal(), $dirty);
                self::writeLog('updated', $model, $old, $dirty);
            }
        });

        static::deleted(function ($model) {
            self::writeLog('deleted', $model, $model->getAttributes(), null);
        });
    }

    private static function writeLog(string $action, $model, ?array $old, ?array $new): void
    {
        try {
            $user   = Auth::user();
            $module = strtolower(class_basename($model));

            // Get a human-readable label for the record
            $label = $model->customer_name         // Customer
                  ?? $model->name                  // User, Agent, Role
                  ?? $model->description           // Transaction
                  ?? $model->payment_type          // PaymentType
                  ?? "#{$model->id}";

            ActivityLog::create([
                'user_id'      => $user?->id,
                'role_name'    => $user?->roles->first()?->name,
                'action'       => $action,
                'module'       => $module,
                'record_id'    => $model->id,
                'record_label' => $label,
                'old_values'   => $old,
                'new_values'   => $new,
                'description'  => ucfirst($action) . " {$module} [{$label}]",
                'ip_address'   => Request::ip(),
                'user_agent'   => substr(Request::userAgent() ?? '', 0, 300),
                'created_at'   => now(),
            ]);
        } catch (\Exception $e) {
            // Never let logging break the main operation
            \Log::error('ActivityLog write failed: ' . $e->getMessage());
        }
    }
}
