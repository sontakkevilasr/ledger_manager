<?php
// ═══════════════════════════════════════════════════════════════════════════
// app/Http/Middleware/CheckRole.php
// Usage in routes: middleware('role:super_admin,manager')
// ═══════════════════════════════════════════════════════════════════════════
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        foreach ($roles as $role) {
            if ($user->hasRole(trim($role))) {
                return $next($request);
            }
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return redirect()->route('dashboard')
            ->with('error', 'Access denied for your role.');
    }
}
