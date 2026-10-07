<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    /**
     * Rejects every request from an already-authenticated but suspended
     * user, even if their Sanctum token is still otherwise valid. Guests
     * (no resolved user) pass through untouched.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && $user->isSuspended()) {
            abort(403, 'Ce compte a ete suspendu.');
        }

        return $next($request);
    }
}
