<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        if (! empty($roles)) {
            $allowedRoles = [];
            foreach ($roles as $role) {
                foreach (explode(',', $role) as $r) {
                    $trimmed = trim($r);
                    if ($trimmed !== '') {
                        $allowedRoles[] = $trimmed;
                    }
                }
            }

            if (! in_array($user->role, $allowedRoles, true)) {
                return response()->json([
                    'message' => 'Forbidden. You do not have permission to access this resource.',
                ], Response::HTTP_FORBIDDEN);
            }
        }

        return $next($request);
    }
}
