# Primary Key Configuration Guide

This guide explains how to configure the `idtbl_user_privilege` primary key and other database identifiers for your privilege system.

## Overview

By default, Laravel Privilege Manager uses:
- **Privilege table primary key:** `idtbl_user_privilege`
- **Menu table primary key:** `idtbl_menu_list`
- **User table primary key:** `id` (Laravel standard) or `idtbl_user` (legacy)

If your legacy system uses different primary key names, you can customize them in the configuration.

## Configuration

### Option 1: Environment Variables (Recommended)

Update your `.env` file to specify custom primary key names:

```env
# User table
PRIVILEGE_USERS_TABLE=users
PRIVILEGE_USERS_PRIMARY_KEY=id

# Menu table
PRIVILEGE_MENUS_TABLE=tbl_menu_list
PRIVILEGE_MENUS_PRIMARY_KEY=idtbl_menu_list

# Privilege table
PRIVILEGE_PRIVILEGES_TABLE=tbl_user_privilege
PRIVILEGE_PRIVILEGES_PRIMARY_KEY=idtbl_user_privilege
```

### Option 2: Config File

Publish and modify the config file:

```bash
php artisan vendor:publish --provider="LaravelPrivilegeManager\Providers\PrivilegeManagerServiceProvider" --tag="privilege-manager-config"
```

Then update `config/privilege-manager.php`:

```php
'database' => [
    'users_table' => env('PRIVILEGE_USERS_TABLE', 'users'),
    'users_primary_key' => env('PRIVILEGE_USERS_PRIMARY_KEY', 'id'),
    'menus_table' => env('PRIVILEGE_MENUS_TABLE', 'tbl_menu_list'),
    'menus_primary_key' => env('PRIVILEGE_MENUS_PRIMARY_KEY', 'idtbl_menu_list'),
    'privileges_table' => env('PRIVILEGE_PRIVILEGES_TABLE', 'tbl_user_privilege'),
    'privileges_primary_key' => env('PRIVILEGE_PRIVILEGES_PRIMARY_KEY', 'idtbl_user_privilege'),
],
```

## Using Custom Primary Keys in Models

### UserPrivilege Model

The package automatically reads the primary key from config. No code changes needed if you use environment variables:

```php
// In config/privilege-manager.php or .env
'privileges_primary_key' => 'idtbl_user_privilege'

// The model will automatically use this
class UserPrivilege extends Model
{
    // Primary key is set dynamically from config
    protected $primaryKey;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->primaryKey = config('privilege-manager.database.privileges_primary_key', 'idtbl_user_privilege');
    }
}
```

### Menu Model

Similarly for the Menu model:

```php
class Menu extends Model
{
    protected $primaryKey;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->primaryKey = config('privilege-manager.database.menus_primary_key', 'idtbl_menu_list');
    }
}
```

### User Model

If your User model uses `idtbl_user` instead of `id`:

```php
// app/Models/User.php

class User extends Model
{
    protected $primaryKey = 'idtbl_user'; // Set to your custom primary key
    
    use HasPrivileges; // Add the privilege manager trait
    
    // ... rest of model
}
```

## Legacy eRav Sales System Configuration

If you're migrating from the eRav Sales System (as documented in the package), use these settings:

### .env

```env
PRIVILEGE_USERS_TABLE=tbl_user
PRIVILEGE_USERS_PRIMARY_KEY=idtbl_user
PRIVILEGE_MENUS_TABLE=tbl_menu_list
PRIVILEGE_MENUS_PRIMARY_KEY=idtbl_menu_list
PRIVILEGE_PRIVILEGES_TABLE=tbl_user_privilege
PRIVILEGE_PRIVILEGES_PRIMARY_KEY=idtbl_user_privilege
```

### config/privilege-manager.php

```php
'database' => [
    'users_table' => env('PRIVILEGE_USERS_TABLE', 'tbl_user'),
    'users_primary_key' => env('PRIVILEGE_USERS_PRIMARY_KEY', 'idtbl_user'),
    'menus_table' => env('PRIVILEGE_MENUS_TABLE', 'tbl_menu_list'),
    'menus_primary_key' => env('PRIVILEGE_MENUS_PRIMARY_KEY', 'idtbl_menu_list'),
    'privileges_table' => env('PRIVILEGE_PRIVILEGES_TABLE', 'tbl_user_privilege'),
    'privileges_primary_key' => env('PRIVILEGE_PRIVILEGES_PRIMARY_KEY', 'idtbl_user_privilege'),
],
```

### app/Models/User.php

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use LaravelPrivilegeManager\Traits\HasPrivileges;

class User extends Authenticatable
{
    protected $table = 'tbl_user';
    protected $primaryKey = 'idtbl_user';
    public $timestamps = false;
    
    use HasPrivileges;
    
    // ... rest of model
}
```

## Verifying Configuration

After setting custom primary keys, verify they're correctly loaded:

### In a Controller

```php
use LaravelPrivilegeManager\Models\UserPrivilege;
use LaravelPrivilegeManager\Models\Menu;

// Check UserPrivilege primary key
$privilege = new UserPrivilege();
echo $privilege->getKeyName(); // Should output: idtbl_user_privilege

// Check Menu primary key
$menu = new Menu();
echo $menu->getKeyName(); // Should output: idtbl_menu_list
```

### Via Artisan Tinker

```bash
php artisan tinker

>>> $privilege = new \LaravelPrivilegeManager\Models\UserPrivilege();
>>> $privilege->getKeyName()
=> "idtbl_user_privilege"

>>> $menu = new \LaravelPrivilegeManager\Models\Menu();
>>> $menu->getKeyName()
=> "idtbl_menu_list"
```

## Foreign Key Relationships

When configuring primary keys, ensure your foreign keys match:

### Migration Example

If using `idtbl_user_privilege` as primary key and `tbl_user_idtbl_user` as foreign key:

```php
Schema::create('tbl_user_privilege', function (Blueprint $table) {
    $table->increments('idtbl_user_privilege');
    
    // Foreign key to users table
    $table->unsignedBigInteger('tbl_user_idtbl_user');
    $table->foreign('tbl_user_idtbl_user')
        ->references('idtbl_user')
        ->on('tbl_user')
        ->onDelete('cascade');
    
    // Foreign key to menus table
    $table->unsignedBigInteger('tbl_menu_list_idtbl_menu_list');
    $table->foreign('tbl_menu_list_idtbl_menu_list')
        ->references('idtbl_menu_list')
        ->on('tbl_menu_list')
        ->onDelete('cascade');
    
    // ... other columns
});
```

### Relationships in Models

```php
// UserPrivilege model
public function user()
{
    return $this->belongsTo(
        User::class,
        'tbl_user_idtbl_user',
        config('privilege-manager.database.users_primary_key', 'idtbl_user')
    );
}

public function menu()
{
    return $this->belongsTo(
        Menu::class,
        'tbl_menu_list_idtbl_menu_list',
        config('privilege-manager.database.menus_primary_key', 'idtbl_menu_list')
    );
}
```

## Troubleshooting

### "Primary key not found" Error

**Cause:** Config key not set correctly.

**Solution:** Verify your `.env` or `config/privilege-manager.php` has the correct `*_primary_key` entries.

### Foreign Key Constraint Errors

**Cause:** Primary key in config doesn't match actual database column name.

**Solution:** 
1. Check your database schema
2. Update config to match the actual column name
3. Run migrations if needed

### Model Methods Return Wrong Results

**Cause:** Primary key mismatch between config and actual model.

**Solution:** 
1. Use Tinker to verify `$model->getKeyName()`
2. Check `config('privilege-manager.database')` values
3. Ensure table names in config match actual database tables

## Quick Reference

| Component | Config Key | Default | Legacy Value |
|-----------|-----------|---------|--------------|
| Users table | `database.users_table` | `users` | `tbl_user` |
| Users primary key | `database.users_primary_key` | `id` | `idtbl_user` |
| Menus table | `database.menus_table` | `tbl_menu_list` | `tbl_menu_list` |
| Menus primary key | `database.menus_primary_key` | `idtbl_menu_list` | `idtbl_menu_list` |
| Privileges table | `database.privileges_table` | `tbl_user_privilege` | `tbl_user_privilege` |
| Privileges primary key | `database.privileges_primary_key` | `idtbl_user_privilege` | `idtbl_user_privilege` |

## See Also

- [Installation Guide](INSTALLATION.md) - Full setup instructions
- [Usage Examples](USAGE_EXAMPLES.md) - Real-world code samples
- [Migration Guide](MIGRATION.md) - Migrating from legacy systems
