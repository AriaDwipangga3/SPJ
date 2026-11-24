<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Pemakaian di routes/api.php:
     *   Route::middleware(['auth:sanctum', 'role:admin'])->group(...)
     *   Route::middleware(['auth:sanctum', 'role:user'])->group(...)
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (! $request->user() || $request->user()->role !== $role) {
            abort(403, 'Akses ditolak. Fitur ini khusus untuk role: ' . $role);
        }

        return $next($request);
    }
}
