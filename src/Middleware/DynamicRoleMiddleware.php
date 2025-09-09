<?php

namespace Anwar\DynamicRoles\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class DynamicRoleMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = Auth::user();

        // If no user is authenticated, deny access
        if (!$user) {
            return $this->unauthorizedResponse($request);
        }

        // Check if user has any of the required roles
        if (!empty($roles)) {
            $hasRole = false;
            
            foreach ($roles as $role) {
                if ($user->hasRole($role)) {
                    $hasRole = true;
                    break;
                }
            }

            if (!$hasRole) {
                return $this->forbiddenResponse($request, $roles);
            }
        }

        return $next($request);
    }

    /**
     * Return unauthorized response.
     */
    protected function unauthorizedResponse(Request $request): Response
    {
        if ($request->expectsJson()) {
            return new JsonResponse([
                'message' => 'Unauthenticated.',
                'error' => 'authentication_required',
                'status' => 401
            ], 401);
        }

        return redirect()->guest(route('login'));
    }

    /**
     * Return forbidden response.
     */
    protected function forbiddenResponse(Request $request, array $roles): Response
    {
        $message = "You don't have any of the required roles: " . implode(', ', $roles);

        if ($request->expectsJson()) {
            return new JsonResponse([
                'message' => $message,
                'error' => 'insufficient_roles',
                'required_roles' => $roles,
                'status' => 403
            ], 403);
        }

        // For web requests, you might want to redirect to an error page
        abort(403, $message);
    }
}
