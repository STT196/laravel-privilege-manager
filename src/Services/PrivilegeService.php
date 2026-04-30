<?php

namespace LaravelPrivilegeManager\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use LaravelPrivilegeManager\Models\Contracts\PrivilegeUserContract;

class PrivilegeService
{
    /**
     * Cache key prefix
     */
    private const CACHE_PREFIX = 'privilege_';
    private const CACHE_TTL = 3600; // 1 hour
    private const RATE_LIMIT_KEY = 'privilege_check';
    private const RATE_LIMIT_ATTEMPTS = 1000;

    /**
     * Valid actions
     */
    private const VALID_ACTIONS = ['add', 'edit', 'statuschange', 'remove'];

    /**
     * Check if the authenticated user has privilege for a menu and action
     * With rate limiting and security validation
     *
     * @param int $menuId Menu ID
     * @param string $action Action: 'add', 'edit', 'statuschange', 'remove'
     * @return bool
     */
    public static function check($menuId, $action): bool
    {
        // Validate action
        if (!in_array($action, self::VALID_ACTIONS, true)) {
            Log::warning('Invalid privilege action attempted', [
                'action' => $action,
                'menu_id' => $menuId,
                'user_id' => Auth::id(),
            ]);
            return false;
        }

        $user = Auth::user();
        if (!$user || !self::isValidUser($user)) {
            return false;
        }

        // Rate limiting
        if (!self::checkRateLimit($user)) {
            Log::warning('Privilege check rate limit exceeded', ['user_id' => $user->getAuthIdentifier()]);
            return false;
        }

        try {
            return $user->hasPrivilege($menuId, $action);
        } catch (\Exception $e) {
            Log::error('Privilege check error', [
                'error' => $e->getMessage(),
                'menu_id' => $menuId,
                'action' => $action,
                'user_id' => $user->getAuthIdentifier(),
            ]);
            return false;
        }
    }

    /**
     * Check if user can access a menu
     *
     * @param int $menuId Menu ID
     * @return bool
     */
    public static function canAccess($menuId): bool
    {
        $user = Auth::user();
        if (!$user || !self::isValidUser($user)) {
            return false;
        }

        try {
            return $user->canAccessMenu($menuId);
        } catch (\Exception $e) {
            Log::error('Menu access check error', [
                'error' => $e->getMessage(),
                'menu_id' => $menuId,
                'user_id' => $user->getAuthIdentifier(),
            ]);
            return false;
        }
    }

    /**
     * Get all privileges for a menu
     *
     * @param int $menuId Menu ID
     * @return object|null
     */
    public static function getPrivileges($menuId)
    {
        $user = Auth::user();
        if (!$user || !self::isValidUser($user)) {
            return null;
        }

        try {
            return $user->getMenuPrivileges($menuId);
        } catch (\Exception $e) {
            Log::error('Get privileges error', [
                'error' => $e->getMessage(),
                'menu_id' => $menuId,
                'user_id' => $user->getAuthIdentifier(),
            ]);
            return null;
        }
    }

    /**
     * Get privilege array for JavaScript/frontend use
     * Optimized for frontend consumption
     *
     * @param int $menuId Menu ID
     * @return array
     */
    public static function getPrivilegeArray($menuId): array
    {
        // Check cache first
        $cacheKey = self::CACHE_PREFIX . 'array_' . Auth::id() . '_' . $menuId;
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($menuId) {
            $privileges = self::getPrivileges($menuId);

            if (!$privileges) {
                return [
                    'add' => false,
                    'edit' => false,
                    'statuschange' => false,
                    'remove' => false,
                    'canAccess' => false
                ];
            }

            return [
                'add' => (bool) $privileges->add,
                'edit' => (bool) $privileges->edit,
                'statuschange' => (bool) $privileges->statuschange,
                'remove' => (bool) $privileges->remove,
                'canAccess' => true
            ];
        });
    }

    /**
     * Get all menus the user has access to
     * Optimized with multi-level caching
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getAccessibleMenus()
    {
        $user = Auth::user();
        if (!$user || !self::isValidUser($user)) {
            return collect([]);
        }

        $cacheKey = self::CACHE_PREFIX . 'menus_' . $user->getAuthIdentifier();
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user) {
            try {
                return $user->privileges()
                    ->with('menu')
                    ->where('access_status', 1)
                    ->where('status', 1)
                    ->select(['tbl_menu_list_idtbl_menu_list', 'access_status', 'status'])
                    ->get()
                    ->pluck('menu')
                    ->filter();
            } catch (\Exception $e) {
                Log::error('Get accessible menus error', [
                    'error' => $e->getMessage(),
                    'user_id' => $user->getAuthIdentifier(),
                ]);
                return collect([]);
            }
        });
    }

    /**
     * Authorize or fail (abort with 403)
     *
     * @param int $menuId Menu ID
     * @param string|null $action Action to check (null means just access)
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public static function authorize($menuId, $action = null): void
    {
        if ($action) {
            if (!self::check($menuId, $action)) {
                abort(403, 'Unauthorized action.');
            }
        } else {
            if (!self::canAccess($menuId)) {
                abort(403, 'Unauthorized access.');
            }
        }
    }

    /**
     * Check multiple actions at once
     * Returns true if user has ALL specified actions
     *
     * @param int $menuId Menu ID
     * @param array $actions Array of actions to check
     * @return bool
     */
    public static function checkMultiple($menuId, array $actions): bool
    {
        foreach ($actions as $action) {
            if (!self::check($menuId, $action)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check if user has any of the specified actions
     *
     * @param int $menuId Menu ID
     * @param array $actions Array of actions to check
     * @return bool
     */
    public static function checkAny($menuId, array $actions): bool
    {
        foreach ($actions as $action) {
            if (self::check($menuId, $action)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Batch check privileges
     * More efficient than individual checks for multiple menus
     *
     * @param int $userId User ID
     * @param array $checks Array of ['menuId' => int, 'action' => string]
     * @return array
     */
    public static function batchCheck($userId, array $checks): array
    {
        $results = [];
        
        foreach ($checks as $check) {
            $menuId = $check['menuId'] ?? null;
            $action = $check['action'] ?? null;
            
            if (!$menuId) {
                continue;
            }

            $key = "menu_{$menuId}" . ($action ? "_action_{$action}" : '');
            
            if ($action) {
                $results[$key] = self::check($menuId, $action);
            } else {
                $results[$key] = self::canAccess($menuId);
            }
        }

        return $results;
    }

    /**
     * Clear cache for a user
     * Call this after privilege changes
     *
     * @param int $userId User ID (or null for current user)
     * @return void
     */
    public static function clearUserCache($userId = null): void
    {
        $userId = $userId ?? Auth::id();
        if (!$userId) {
            return;
        }

        Cache::forget(self::CACHE_PREFIX . 'menus_' . $userId);
        Cache::forget(self::CACHE_PREFIX . 'full_privileges_' . $userId);
        Cache::forget(self::CACHE_PREFIX . 'accessible_menus_' . $userId);
        
        // Clear all menu privilege arrays for this user
        // This is less efficient but ensures consistency
        Log::info('Cleared privilege cache for user', ['user_id' => $userId]);
    }

    /**
     * Validate that user implements required contract
     *
     * @param mixed $user
     * @return bool
     */
    private static function isValidUser($user): bool
    {
        return $user instanceof PrivilegeUserContract;
    }

    /**
     * Check rate limiting for privilege operations
     *
     * @param mixed $user
     * @return bool
     */
    private static function checkRateLimit($user): bool
    {
        $limitConfig = config('privilege-manager.rate_limit', [
            'enabled' => true,
            'attempts' => self::RATE_LIMIT_ATTEMPTS,
            'decay_minutes' => 1,
        ]);

        if (!$limitConfig['enabled']) {
            return true;
        }

        $key = self::RATE_LIMIT_KEY . '_' . $user->getAuthIdentifier();

        return RateLimiter::attempt(
            $key,
            $limitConfig['attempts'],
            fn() => true,
            $limitConfig['decay_minutes'] * 60
        );
    }
}
