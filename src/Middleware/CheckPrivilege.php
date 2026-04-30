<?php

namespace LaravelPrivilegeManager\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use LaravelPrivilegeManager\Services\PrivilegeService;
use Symfony\Component\HttpFoundation\Response;

class CheckPrivilege
{
    /**
     * Handle an incoming request with privilege checking
     * Enhanced with security validations and detailed logging
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  mixed  $menuId  Menu ID to check access
     * @param  string|null  $action  Specific action to check (add, edit, statuschange, remove)
     */
    public function handle(Request $request, Closure $next, $menuId, $action = null): Response
    {
        // Validate input parameters
        if (!$this->isValidMenuId($menuId)) {
            Log::warning('Invalid menu ID in privilege middleware', [
                'menu_id' => $menuId,
                'ip' => $request->ip(),
                'url' => $request->path(),
            ]);
            return $this->unauthorized($request, 'Invalid menu ID.');
        }

        // Check if user is authenticated
        if (!auth()->check()) {
            Log::info('Unauthenticated access attempt', [
                'menu_id' => $menuId,
                'ip' => $request->ip(),
                'url' => $request->path(),
            ]);
            return redirect()->route('login')->with('error', 'Please login to continue.');
        }

        // Log privilege check attempt
        Log::debug('Privilege check', [
            'user_id' => auth()->id(),
            'menu_id' => $menuId,
            'action' => $action,
            'method' => $request->getMethod(),
        ]);

        // Check if action is specified
        if ($action) {
            // Check specific privilege action
            if (!PrivilegeService::check($menuId, $action)) {
                Log::warning('Privilege action denied', [
                    'user_id' => auth()->id(),
                    'menu_id' => $menuId,
                    'action' => $action,
                    'ip' => $request->ip(),
                    'url' => $request->path(),
                ]);

                return $this->unauthorized(
                    $request,
                    "You do not have permission to {$action}."
                );
            }
        } else {
            // Just check menu access
            if (!PrivilegeService::canAccess($menuId)) {
                Log::warning('Menu access denied', [
                    'user_id' => auth()->id(),
                    'menu_id' => $menuId,
                    'ip' => $request->ip(),
                    'url' => $request->path(),
                ]);

                return $this->unauthorized($request, 'You do not have permission to access this page.');
            }
        }

        return $next($request);
    }

    /**
     * Return unauthorized response based on request type
     *
     * @param Request $request
     * @param string $message
     * @param int $statusCode
     * @return Response
     */
    private function unauthorized(Request $request, $message = 'Unauthorized', $statusCode = 403): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message
            ], $statusCode);
        }

        return redirect()->back()->with('error', $message);
    }

    /**
     * Validate menu ID to prevent injection attacks
     *
     * @param mixed $menuId
     * @return bool
     */
    private function isValidMenuId($menuId): bool
    {
        return is_numeric($menuId) && (int)$menuId > 0;
    }
}
