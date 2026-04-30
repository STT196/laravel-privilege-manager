# Installation & Setup Guide

Complete step-by-step guide to install and configure Laravel Privilege Manager.

## Prerequisites

- PHP 8.1 or higher
- Laravel 10.x or 11.x
- Composer
- Existing Laravel project with authentication

## Installation Steps

### Step 1: Install Package via Composer

```bash
composer require thisa/laravel-privilege-manager
```

### Step 2: Publish Configuration

```bash
php artisan vendor:publish --provider="LaravelPrivilegeManager\Providers\PrivilegeManagerServiceProvider" --tag="privilege-manager-config"
```

This creates `config/privilege-manager.php` in your project.

### Step 3: Update User Model

Your User model must implement the `PrivilegeUserContract` interface. Here's a complete example:

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\ActivityLog\Traits\LogsActivity;
use Spatie\ActivityLog\LogOptions;
use LaravelPrivilegeManager\Models\Contracts\PrivilegeUserContract;
use LaravelPrivilegeManager\Models\UserPrivilege;
use Illuminate\Support\Facades\Cache;

class User extends Authenticatable implements PrivilegeUserContract
{
    use HasApiTokens, HasFactory, Notifiable, LogsActivity;

    protected $table = 'tbl_user';
    protected $primaryKey = 'idtbl_user';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'imagepath',
        'status',
        'tbl_user_type_idtbl_user_type',
        'insertdatetime',
        'updatedatetime',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // ... existing relationships ...

    /**
     * Get all privileges relationship
     * Required by PrivilegeUserContract
     */
    public function privileges()
    {
        return $this->hasMany(UserPrivilege::class, 'tbl_user_idtbl_user', 'idtbl_user');
    }

    /**
     * Check if user has privilege for a menu and action
     * Required by PrivilegeUserContract
     */
    public function hasPrivilege($menuId, $action): bool
    {
        $privileges = $this->getCachedFullPrivileges();
        $privilege = $privileges->get($menuId);

        if (!$privilege) {
            return false;
        }

        return (bool) $privilege->$action;
    }

    /**
     * Check if user can access a menu
     * Required by PrivilegeUserContract
     */
    public function canAccessMenu($menuId): bool
    {
        $privileges = $this->getCachedPrivileges();
        return in_array($menuId, $privileges);
    }

    /**
     * Get all privileges for a specific menu
     * Required by PrivilegeUserContract
     */
    public function getMenuPrivileges($menuId)
    {
        $privileges = $this->getCachedFullPrivileges();
        return $privileges->get($menuId);
    }

    /**
     * Get cached full privileges collection
     * Required by PrivilegeUserContract
     */
    public function getCachedFullPrivileges()
    {
        return Cache::remember("user_full_privileges_{$this->idtbl_user}", 3600, function () {
            return $this->privileges()
                ->where('status', 1)
                ->where('access_status', 1)
                ->pluck(null, 'tbl_menu_list_idtbl_menu_list');
        });
    }

    /**
     * Get cached privileges (menu IDs user can access)
     * Required by PrivilegeUserContract
     */
    public function getCachedPrivileges(): array
    {
        return Cache::remember("user_privileges_{$this->idtbl_user}", 3600, function () {
            return $this->privileges()
                ->where('status', 1)
                ->where('access_status', 1)
                ->pluck('tbl_menu_list_idtbl_menu_list')
                ->toArray();
        });
    }

    /**
     * Activity Log configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('user')
            ->logOnly([
                'idtbl_user',
                'name',
                'username',
                'email',
                'password',
                'status',
                'tbl_user_type_idtbl_user_type',
            ])
            ->logOnlyDirty();
    }
}
```

### Step 4: Register Middleware (Optional)

The middleware is automatically registered by the service provider. However, if you want to customize it, you can add it manually to `app/Http/Kernel.php`:

```php
// app/Http/Kernel.php

protected $routeMiddleware = [
    // ... other middleware ...
    'privilege' => \LaravelPrivilegeManager\Middleware\CheckPrivilege::class,
];
```

### Step 5: Verify Installation

Test that everything is working:

```bash
php artisan tinker
```

Then in the Tinker REPL:

```php
>>> auth()->loginUsingId(1); // Login as user 1
>>> checkPrivilege(7, 'add'); // Test a privilege check
```

If it returns `true` or `false`, installation is successful!

## Configuration Options

Edit `config/privilege-manager.php`:

```php
return [
    // Table names
    'tables' => [
        'user_privileges' => 'tbl_user_privilege',
        'menus' => 'tbl_menu_list',
    ],

    // Cache settings
    'cache' => [
        'enabled' => env('PRIVILEGE_CACHE_ENABLED', true),
        'ttl' => env('PRIVILEGE_CACHE_TTL', 3600), // 1 hour
        'prefix' => 'privilege_',
    ],

    // Rate limiting
    'rate_limit' => [
        'enabled' => env('PRIVILEGE_RATE_LIMIT_ENABLED', true),
        'attempts' => env('PRIVILEGE_RATE_LIMIT_ATTEMPTS', 1000),
        'decay_minutes' => env('PRIVILEGE_RATE_LIMIT_DECAY', 1),
    ],

    // Logging
    'logging' => [
        'log_checks' => env('PRIVILEGE_LOG_CHECKS', true),
        'log_denials' => env('PRIVILEGE_LOG_DENIALS', true),
        'log_cache_operations' => env('PRIVILEGE_LOG_CACHE', false),
    ],

    // Models
    'models' => [
        'user' => 'App\\Models\\User',
        'user_privilege' => 'LaravelPrivilegeManager\\Models\\UserPrivilege',
        'menu' => 'LaravelPrivilegeManager\\Models\\Menu',
    ],

    // Security
    'security' => [
        'enable_ip_check' => env('PRIVILEGE_CHECK_IP', false),
        'enable_signature_validation' => env('PRIVILEGE_SIGNATURE_VALIDATION', false),
        'allowed_actions' => ['add', 'edit', 'statuschange', 'remove'],
    ],

    // Performance
    'performance' => [
        'enable_batch_operations' => env('PRIVILEGE_BATCH_OPS', true),
        'preload_privileges' => env('PRIVILEGE_PRELOAD', true),
        'batch_size' => env('PRIVILEGE_BATCH_SIZE', 100),
    ],
];
```

### Environment Variables

Add these to your `.env` file to customize behavior:

```env
# Cache settings
PRIVILEGE_CACHE_ENABLED=true
PRIVILEGE_CACHE_TTL=3600

# Rate limiting (prevent abuse)
PRIVILEGE_RATE_LIMIT_ENABLED=true
PRIVILEGE_RATE_LIMIT_ATTEMPTS=1000
PRIVILEGE_RATE_LIMIT_DECAY=1

# Logging
PRIVILEGE_LOG_CHECKS=true
PRIVILEGE_LOG_DENIALS=true
PRIVILEGE_LOG_CACHE=false

# Security (optional)
PRIVILEGE_CHECK_IP=false
PRIVILEGE_SIGNATURE_VALIDATION=false

# Performance
PRIVILEGE_BATCH_OPS=true
PRIVILEGE_PRELOAD=true
PRIVILEGE_BATCH_SIZE=100
```

## Database Requirements

The package assumes your database has these tables:

### `tbl_user_privilege` Table

```sql
CREATE TABLE `tbl_user_privilege` (
    `idtbl_user_privilege` INT PRIMARY KEY AUTO_INCREMENT,
    `tbl_user_idtbl_user` INT NOT NULL,
    `tbl_menu_list_idtbl_menu_list` INT NOT NULL,
    `access_status` TINYINT DEFAULT 1,
    `add` TINYINT DEFAULT 0,
    `edit` TINYINT DEFAULT 0,
    `statuschange` TINYINT DEFAULT 0,
    `remove` TINYINT DEFAULT 0,
    `status` TINYINT DEFAULT 1,
    FOREIGN KEY (`tbl_user_idtbl_user`) REFERENCES `tbl_user`(`idtbl_user`),
    FOREIGN KEY (`tbl_menu_list_idtbl_menu_list`) REFERENCES `tbl_menu_list`(`idtbl_menu_list`)
);
```

### `tbl_menu_list` Table

```sql
CREATE TABLE `tbl_menu_list` (
    `idtbl_menu_list` INT PRIMARY KEY AUTO_INCREMENT,
    `menuname` VARCHAR(255) NOT NULL,
    `menuurl` VARCHAR(255),
    `displayorder` INT DEFAULT 0,
    `status` TINYINT DEFAULT 1
);
```

## Common Issues & Solutions

### Issue: "Class does not implement PrivilegeUserContract"

**Solution:** Ensure your User model implements the interface:

```php
class User extends Authenticatable implements PrivilegeUserContract
{
    // ...
}
```

### Issue: "Privileges always return false"

**Solution:** Verify that:
1. User has a record in `tbl_user_privilege` table
2. `access_status = 1` in the privilege record
3. `status = 1` in the privilege record
4. User ID in the privilege record matches the authenticated user

### Issue: "Class not found: LaravelPrivilegeManager\..."

**Solution:** Run composer autoload:

```bash
composer dump-autoload
```

### Issue: Cache not working

**Solution:** Ensure cache driver is not set to "null":

```php
// .env
CACHE_DRIVER=redis  // or file, database, etc.
```

### Issue: Rate limiting blocking legitimate requests

**Solution:** Adjust rate limit in config:

```php
'rate_limit' => [
    'attempts' => 5000,  // Increase from 1000
]
```

## Next Steps

1. Update your routes to use the middleware: [See Usage Guide](USAGE.md)
2. Add privilege checks to your controllers
3. Update your Blade views to show/hide elements based on privileges
4. Review the [API Reference](API.md) for advanced features

## Support

For issues or questions:
1. Check the [Troubleshooting Guide](docs/TROUBLESHOOTING.md)
2. Review existing documentation
3. Open an issue on the repository

---

**Installation complete! You're ready to start using Laravel Privilege Manager.**
