<?php

namespace LaravelPrivilegeManager\Models\Contracts;

interface PrivilegeUserContract
{
    /**
     * Check if user has privilege for a specific menu and action
     * 
     * @param int $menuId Menu ID
     * @param string $action Action type: 'add', 'edit', 'statuschange', 'remove'
     * @return bool
     */
    public function hasPrivilege($menuId, $action): bool;

    /**
     * Check if user has any access to a menu
     * 
     * @param int $menuId Menu ID
     * @return bool
     */
    public function canAccessMenu($menuId): bool;

    /**
     * Get all privileges for a specific menu
     * 
     * @param int $menuId Menu ID
     * @return object|null
     */
    public function getMenuPrivileges($menuId);

    /**
     * Get cached full privileges collection
     *
     * @return \Illuminate\Support\Collection
     */
    public function getCachedFullPrivileges();

    /**
     * Get cached privileges (menu IDs user can access)
     *
     * @return array
     */
    public function getCachedPrivileges(): array;

    /**
     * Get privileges relationship
     *
     * @return mixed
     */
    public function privileges();
}
