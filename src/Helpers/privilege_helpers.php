<?php

use LaravelPrivilegeManager\Services\PrivilegeService;

/**
 * Laravel Privilege Manager - Global Helper Functions
 * 
 * These helper functions provide convenient access to the privilege system
 * from anywhere in your Laravel application
 */

if (!function_exists('checkPrivilege')) {
    /**
     * Check if user has privilege for a menu and action
     *
     * @param int $menuId Menu ID
     * @param string $action Action: 'add', 'edit', 'statuschange', 'remove'
     * @return bool
     */
    function checkPrivilege($menuId, $action)
    {
        return PrivilegeService::check($menuId, $action);
    }
}

if (!function_exists('canAccessMenu')) {
    /**
     * Check if user can access a menu
     *
     * @param int $menuId Menu ID
     * @return bool
     */
    function canAccessMenu($menuId)
    {
        return PrivilegeService::canAccess($menuId);
    }
}

if (!function_exists('getMenuPrivileges')) {
    /**
     * Get all privileges for a menu as an array
     *
     * @param int $menuId Menu ID
     * @return array
     */
    function getMenuPrivileges($menuId)
    {
        return PrivilegeService::getPrivilegeArray($menuId);
    }
}

if (!function_exists('authorizePrivilege')) {
    /**
     * Authorize privilege or abort with 403
     *
     * @param int $menuId Menu ID
     * @param string|null $action Action to check
     * @return void
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    function authorizePrivilege($menuId, $action = null)
    {
        PrivilegeService::authorize($menuId, $action);
    }
}

if (!function_exists('hasAnyPrivilege')) {
    /**
     * Check if user has any of the specified actions
     *
     * @param int $menuId Menu ID
     * @param array $actions Array of actions
     * @return bool
     */
    function hasAnyPrivilege($menuId, array $actions)
    {
        return PrivilegeService::checkAny($menuId, $actions);
    }
}

if (!function_exists('hasAllPrivileges')) {
    /**
     * Check if user has all specified actions
     *
     * @param int $menuId Menu ID
     * @param array $actions Array of actions
     * @return bool
     */
    function hasAllPrivileges($menuId, array $actions)
    {
        return PrivilegeService::checkMultiple($menuId, $actions);
    }
}

if (!function_exists('getUserAccessibleMenus')) {
    /**
     * Get all menus the user has access to
     *
     * @return \Illuminate\Support\Collection
     */
    function getUserAccessibleMenus()
    {
        return PrivilegeService::getAccessibleMenus();
    }
}

if (!function_exists('batchCheckPrivileges')) {
    /**
     * Batch check multiple privileges at once (more efficient)
     *
     * @param array $checks Array of ['menuId' => int, 'action' => string]
     * @return array
     */
    function batchCheckPrivileges(array $checks)
    {
        return PrivilegeService::batchCheck(auth()->id(), $checks);
    }
}

if (!function_exists('clearUserPrivilegeCache')) {
    /**
     * Clear privilege cache for current or specified user
     * Call this after privilege changes
     *
     * @param int|null $userId User ID (null = current user)
     * @return void
     */
    function clearUserPrivilegeCache($userId = null)
    {
        PrivilegeService::clearUserCache($userId);
    }
}

if (!function_exists('saveUserPrivilege')) {
    /**
     * Save or update a privilege record for a user and menu.
     *
     * @param mixed $user User model instance or user ID
     * @param int $menuId Menu ID
     * @param array $attributes Privilege attributes
     * @return \LaravelPrivilegeManager\Models\UserPrivilege
     */
    function saveUserPrivilege($user, $menuId, array $attributes = [])
    {
        return PrivilegeService::savePrivilege($user, $menuId, $attributes);
    }
}
