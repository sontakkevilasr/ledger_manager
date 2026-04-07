<?php
// ═══════════════════════════════════════════════════════════════════════════
// app/Services/ActivityLogger.php
// Manual logging helper for reports, exports, logins, etc.
// Usage: ActivityLogger::log('exported', 'reports', description: 'Customer ledger PDF')
// ═══════════════════════════════════════════════════════════════════════════
namespace App\Services;

use App\Models\ActivityLog;
use App\Models\LoginLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
    public static function log(
        string  $action,
        string  $module,
        int     $recordId   = null,
        string  $label      = null,
        string  $description = null,
        array   $old        = null,
        array   $new        = null,
    ): void {
        try {
            $user = Auth::user();

            ActivityLog::create([
                'user_id'      => $user?->id,
                'role_name'    => $user?->roles->first()?->name,
                'action'       => $action,
                'module'       => $module,
                'record_id'    => $recordId,
                'record_label' => $label,
                'old_values'   => $old,
                'new_values'   => $new,
                'description'  => $description,
                'ip_address'   => Request::ip(),
                'user_agent'   => substr(Request::userAgent() ?? '', 0, 300),
                'created_at'   => now(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Manual ActivityLog failed: ' . $e->getMessage());
        }
    }

    // ── Auth events ───────────────────────────────────────────
    public static function loginSuccess(int $userId, string $email): void
    {
        LoginLog::create([
            'user_id'         => $userId,
            'email_attempted' => $email,
            'status'          => 'success',
            'ip_address'      => Request::ip(),
            'user_agent'      => substr(Request::userAgent() ?? '', 0, 300),
            'created_at'      => now(),
        ]);
    }

    public static function loginFailed(string $email): void
    {
        LoginLog::create([
            'user_id'         => null,
            'email_attempted' => $email,
            'status'          => 'failed',
            'ip_address'      => Request::ip(),
            'user_agent'      => substr(Request::userAgent() ?? '', 0, 300),
            'created_at'      => now(),
        ]);
    }

    public static function logout(int $userId, string $email): void
    {
        LoginLog::create([
            'user_id'         => $userId,
            'email_attempted' => $email,
            'status'          => 'logout',
            'ip_address'      => Request::ip(),
            'user_agent'      => substr(Request::userAgent() ?? '', 0, 300),
            'created_at'      => now(),
        ]);
    }
}
