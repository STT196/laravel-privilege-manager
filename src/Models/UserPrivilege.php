<?php

namespace LaravelPrivilegeManager\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * UserPrivilege Model
 * Represents the relationship between users and menu privileges
 * 
 * @property int $idtbl_user_privilege Primary key
 * @property int $tbl_user_idtbl_user User ID
 * @property int $tbl_menu_list_idtbl_menu_list Menu ID
 * @property int $access_status Access status (0=no access, 1=has access)
 * @property int $add Add privilege (0=cannot add, 1=can add)
 * @property int $edit Edit privilege (0=cannot edit, 1=can edit)
 * @property int $statuschange Status change privilege (0=cannot change, 1=can change)
 * @property int $remove Remove privilege (0=cannot remove, 1=can remove)
 * @property int $status Record status (1=active, 2=inactive, 3=deleted)
 */
class UserPrivilege extends Model
{
    protected $primaryKey = 'idtbl_user_privilege';
    public $timestamps = false;

    /**
     * Initialize the model by setting the primary key from config
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->primaryKey = config('privilege-manager.database.privileges_primary_key', 'idtbl_user_privilege');
    }

    protected $fillable = [
        'tbl_user_idtbl_user',
        'tbl_menu_list_idtbl_menu_list',
        'access_status',
        'add',
        'edit',
        'statuschange',
        'remove',
        'status',
        'approvestatus',
        'checkstatus',
        'updatedatetime',
    ];

    protected $casts = [
        'access_status' => 'boolean',
        'add' => 'boolean',
        'edit' => 'boolean',
        'statuschange' => 'boolean',
        'remove' => 'boolean',
        'approvestatus' => 'boolean',
        'checkstatus' => 'boolean',
        'status' => 'integer',
    ];

    public function getTable()
    {
        return config('privilege-manager.database.privileges_table', 'tbl_user_privilege');
    }

    /**
     * Get the menu this privilege belongs to
     */
    public function menu()
    {
        return $this->belongsTo(Menu::class, 'tbl_menu_list_idtbl_menu_list', 'idtbl_menu_list');
    }

    /**
     * Get the user this privilege belongs to
     */
    public function user()
    {
        return $this->belongsTo(
            config('privilege-manager.models.user', 'App\\Models\\User'),
            'tbl_user_idtbl_user',
            config('privilege-manager.database.users_primary_key', 'id')
        );
    }

    /**
     * Scope: Get only active privileges
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Scope: Get only accessible privileges
     */
    public function scopeAccessible($query)
    {
        return $query->where('access_status', 1);
    }

    /**
     * Scope: Get privileges with specific action
     */
    public function scopeWithAction($query, $action)
    {
        return $query->where($action, 1);
    }
}
