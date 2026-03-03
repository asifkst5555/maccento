<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        if (!$user) {
            abort(401);
        }

        $normalizedUserRole = strtolower(trim((string) $user->role));
        $normalizedAllowedRoles = array_map(
            static fn (string $role): string => strtolower(trim($role)),
            $roles
        );

        if (!in_array($normalizedUserRole, $normalizedAllowedRoles, true)) {
            abort(403);
        }

        return $next($request);
    }
}
