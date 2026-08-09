<?php

namespace LaravelPrivilegeManager\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use LaravelPrivilegeManager\Models\UserPrivilege;

trait HasPrivileges
{
    /**
     * Get all privileges for the user.
     */
    public function privileges()
    {
        return $this->hasMany(UserPrivilege::class, 'tbl_user_idtbl_user', $this->getKeyName());
    }

    /**
     * Check if user has privilege for a specific menu and action.
     */
    public function hasPrivilege($menuId, $action): bool
    {
        $privilege = $this->getCachedFullPrivileges()->get($menuId);

        if (!$privilege) {
            return false;
        }

        return (bool) ($privilege->$action ?? false);
    }

    /**
     * Check if user has any access to a menu.
     */
    public function canAccessMenu($menuId): bool
    {
        return in_array($menuId, $this->getCachedPrivileges(), true);
    }

    /**
     * Get all privileges for a specific menu.
     */
    public function getMenuPrivileges($menuId)
    {
        return $this->getCachedFullPrivileges()->get($menuId);
    }

    /**
     * Get cached full privileges collection.
     */
    public function getCachedFullPrivileges(): Collection
    {
        return Cache::remember($this->privilegeCacheKey('full'), config('privilege-manager.cache.ttl', 3600), function () {
            return $this->privileges()
                ->active()
                ->accessible()
                ->get()
                ->keyBy('tbl_menu_list_idtbl_menu_list');
        });
    }

    /**
     * Get cached privilege IDs.
     */
    public function getCachedPrivileges(): array
    {
        return Cache::remember($this->privilegeCacheKey('menus'), config('privilege-manager.cache.ttl', 3600), function () {
            return $this->privileges()
                ->active()
                ->accessible()
                ->pluck('tbl_menu_list_idtbl_menu_list')
                ->all();
        });
    }

    /**
     * Clear privilege cache for the user.
     * Also clears the service-level in-memory store for 'request' driver mode.
     */
    public function clearPrivilegeCache(): void
    {
        Cache::forget($this->privilegeCacheKey('full'));
        Cache::forget($this->privilegeCacheKey('menus'));

        // Notify PrivilegeService to also clear its in-memory cache
        if (class_exists(\LaravelPrivilegeManager\Services\PrivilegeService::class)) {
            \LaravelPrivilegeManager\Services\PrivilegeService::clearUserCache($this->getKey());
        }
    }

    /**
     * Build cache key.
     */
    protected function privilegeCacheKey(string $suffix): string
    {
        return 'privilege_' . $suffix . '_' . $this->getKey();
    }
}
