<?php

namespace Anwar\DynamicRoles\Middleware;

use Closure;
use Illuminate\Http\Request;
use Anwar\DynamicRoles\Services\UrlPermissionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class DynamicPermissionMiddleware
{
    protected UrlPermissionService $urlPermissionService;

    public function __construct(UrlPermissionService $urlPermissionService)
    {
        $this->urlPermissionService = $urlPermissionService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        $user = Auth::user();
        
        // If no user is authenticated, deny access
        if (!$user) {
            return $this->unauthorizedResponse($request);
        }

        $url = $request->path();
        $method = $request->method();

        // Check if user has permission for this URL
        $hasPermission = $this->urlPermissionService->checkUrlPermission($user, $url, $method);

        if (!$hasPermission) {
            return $this->forbiddenResponse($request, $url, $method);
        }

        // If specific permissions are provided as middleware parameters, check those too
        if (!empty($permissions)) {
            foreach ($permissions as $permission) {
                if (!$user->hasPermissionTo($permission)) {
                    return $this->forbiddenResponse($request, $url, $method, $permission);
                }
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
    protected function forbiddenResponse(
        Request $request, 
        string $url, 
        string $method, 
        ?string $permission = null
    ): Response {
        $message = $permission 
            ? "You don't have the required permission: {$permission}"
            : "You don't have permission to access {$method} {$url}";

        if ($request->expectsJson()) {
            return new JsonResponse([
                'message' => $message,
                'error' => 'insufficient_permissions',
                'required_permission' => $permission,
                'url' => $url,
                'method' => $method,
                'status' => 403
            ], 403);
        }

        // For web requests, you might want to redirect to an error page
        abort(403, $message);
    }
}
