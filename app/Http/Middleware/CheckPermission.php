<?php
// ═══════════════════════════════════════════════════════════════════════════
// app/Http/Middleware/CheckPermission.php
// Usage in routes: middleware('permission:customers.create')
// ═══════════════════════════════════════════════════════════════════════════
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Super admin bypasses all permission checks
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        if (! $user->hasPermission($permission)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Forbidden.'], 403);
            }

            return redirect()->route('dashboard')
                ->with('error', 'You do not have permission to perform this action.');
        }

        return $next($request);
    }
}
