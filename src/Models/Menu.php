<?php

namespace LaravelPrivilegeManager\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Menu Model
 * Represents menu items in the system
 * 
 * @property int $idtbl_menu_list Primary key
 * @property string $menuname Menu name
 * @property string $menuurl Menu URL
 * @property int $displayorder Display order
 * @property int $status Record status (1=active, 2=inactive, 3=deleted)
 */
class Menu extends Model
{
    protected $primaryKey = 'idtbl_menu_list';
    public $timestamps = false;

    protected $fillable = [
        'menuname',
        'menuurl',
        'displayorder',
        'status',
    ];

    protected $casts = [
        'displayorder' => 'integer',
        'status' => 'integer',
    ];

    public function getTable()
    {
        return config('privilege-manager.database.menus_table', 'tbl_menu_list');
    }

    /**
     * Get all privileges for this menu
     */
    public function privileges()
    {
        return $this->hasMany(UserPrivilege::class, 'tbl_menu_list_idtbl_menu_list', 'idtbl_menu_list');
    }

    /**
     * Scope: Get only active menus
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
