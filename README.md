# Laravel Privilege Manager

A robust, security-hardened, and performance-optimized privilege/permission management system for Laravel applications. Built for production use with enterprise-grade security and performance features.

## Features

✅ **Menu-Based Access Control** - Control access at the menu level with granular actions  
✅ **Security Hardened** - Rate limiting, input validation, logging, and injection prevention  
✅ **Performance Optimized** - Multi-level caching, batch operations, and query optimization  
✅ **Easy Integration** - Drop-in package for existing Laravel apps  
✅ **Flexible Authorization** - Middleware, helpers, contracts, and manual checks  
✅ **Production Ready** - Thoroughly tested and documented  

## Installation

### 1. Install via Composer

```bash
composer require stt196/laravel-privilege-manager
```

### Manual Install from GitHub

If the package is not on Packagist yet, you can install it directly from GitHub using Composer VCS repositories:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/STT196/laravel-privilege-manager.git"
        }
    ],
    "require": {
        "stt196/laravel-privilege-manager": "dev-main"
    }
}
```

Then run:

```bash
composer update
```

### Fresh Laravel Projects

If you're starting from a brand-new Laravel app, run the package installer and then migrate:

```bash
php artisan privilege-manager:install
php artisan migrate
```

Then add the reusable trait to your `App\Models\User` model:

```php
use LaravelPrivilegeManager\Traits\HasPrivileges;

class User extends Authenticatable
{
    use HasPrivileges;
}
```

### 2. Publish Configuration

```bash
php artisan vendor:publish --provider="LaravelPrivilegeManager\Providers\PrivilegeManagerServiceProvider" --tag="privilege-manager-config"
```

This creates `config/privilege-manager.php` where you can customize behavior.

### 3. Update Your User Model

Your User model must implement the `PrivilegeUserContract` interface, or you can use the `HasPrivileges` trait shown above:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LaravelPrivilegeManager\Models\Contracts\PrivilegeUserContract;
use Illuminate\Support\Facades\Cache;

class User extends Model implements PrivilegeUserContract
{
    // ... existing code ...

    /**
     * Get all privileges relationship
     */
    public function privileges()
    {
        return $this->hasMany(
            \LaravelPrivilegeManager\Models\UserPrivilege::class,
            'tbl_user_idtbl_user',
            'idtbl_user'
        );
    }

    /**
     * Check if user has privilege for a menu and action
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
     */
    public function canAccessMenu($menuId): bool
    {
        $privileges = $this->getCachedPrivileges();
        return in_array($menuId, $privileges);
    }

    /**
     * Get all privileges for a specific menu
     */
    public function getMenuPrivileges($menuId)
    {
        $privileges = $this->getCachedFullPrivileges();
        return $privileges->get($menuId);
    }

    /**
     * Get cached full privileges collection
     */
    public function getCachedFullPrivileges()
    {
        return Cache::remember("user_full_privileges_{$this->idtbl_user}", 3600, function () {
            return $this->privileges()
                ->active()
                ->accessible()
                ->pluck(null, 'tbl_menu_list_idtbl_menu_list');
        });
    }

    /**
     * Get cached privileges (menu IDs user can access)
     */
    public function getCachedPrivileges(): array
    {
        return Cache::remember("user_privileges_{$this->idtbl_user}", 3600, function () {
            return $this->privileges()
                ->active()
                ->accessible()
                ->pluck('tbl_menu_list_idtbl_menu_list')
                ->toArray();
        });
    }
}
```

## Quick Start

### Using Middleware (Recommended)

Protect routes by adding the `privilege` middleware:

```php
// routes/web.php

Route::middleware(['auth', 'privilege:7'])->group(function () {
    // Routes that require menu access (ID 7)
    Route::get('/customers', [CustomerController::class, 'index'])->name('customer.index');
});

Route::middleware(['auth', 'privilege:7,add'])->group(function () {
    // Routes that require specific action (add customers)
    Route::post('/customers', [CustomerController::class, 'store'])->name('customer.store');
});

Route::middleware(['auth', 'privilege:7,edit'])->group(function () {
    // Routes that require edit action
    Route::put('/customers/{id}', [CustomerController::class, 'update'])->name('customer.update');
});
```

### Using Helper Functions

Check privileges anywhere in your application:

```php
// In Controllers
if (checkPrivilege(7, 'add')) {
    // User can add customers
}

// Authorize or fail with 403
authorizePrivilege(7, 'edit');

// Get all privileges for a menu
$privileges = getMenuPrivileges(7);
// Returns: ['add' => true, 'edit' => true, 'statuschange' => false, 'remove' => false, 'canAccess' => true]

// Check multiple actions
if (hasAnyPrivilege(7, ['edit', 'remove'])) {
    // Show actions column
}

if (hasAllPrivileges(7, ['edit', 'statuschange'])) {
    // Show advanced options
}

// Get all accessible menus
$menus = getUserAccessibleMenus();
```

### In Blade Views

```blade
{{-- Show/hide buttons based on privileges --}}
@if(checkPrivilege(7, 'add'))
    <button class="btn btn-primary">Add Customer</button>
@endif

@if(checkPrivilege(7, 'edit'))
    <button class="btn btn-warning">Edit</button>
@endif

@if(checkPrivilege(7, 'remove'))
    <button class="btn btn-danger">Delete</button>
@endif

{{-- Check menu access --}}
@if(canAccessMenu(7))
    <li><a href="{{ route('customer.index') }}">Customers</a></li>
@endif

{{-- Check multiple privileges --}}
@if(hasAnyPrivilege(7, ['edit', 'remove']))
    <div class="actions-column">
        {{-- Action buttons --}}
    </div>
@endif
```

### In JavaScript/AJAX

```javascript
// Get privileges from the backend
fetch('/api/user/privileges/7')
    .then(response => response.json())
    .then(privileges => {
        if (privileges.add) {
            document.getElementById('addBtn').style.display = 'block';
        }
        if (privileges.edit) {
            document.getElementById('editBtn').style.display = 'block';
        }
    });
```

## API Reference

### Service Methods

#### `PrivilegeService::check($menuId, $action)`
Check if user has a specific privilege.

```php
$canAdd = PrivilegeService::check(7, 'add');
```

#### `PrivilegeService::canAccess($menuId)`
Check if user can access a menu.

```php
$canAccess = PrivilegeService::canAccess(7);
```

#### `PrivilegeService::getPrivilegeArray($menuId)`
Get all privileges for a menu as an array.

```php
$privileges = PrivilegeService::getPrivilegeArray(7);
// Returns: ['add' => true, 'edit' => true, ...]
```

#### `PrivilegeService::getAccessibleMenus()`
Get all menus user has access to.

```php
$menus = PrivilegeService::getAccessibleMenus();
```

#### `PrivilegeService::authorize($menuId, $action = null)`
Check privilege or abort with 403.

```php
PrivilegeService::authorize(7); // Check menu access
PrivilegeService::authorize(7, 'add'); // Check action
```

#### `PrivilegeService::checkMultiple($menuId, $actions)`
Check if user has ALL specified actions.

```php
$hasAll = PrivilegeService::checkMultiple(7, ['add', 'edit']);
```

#### `PrivilegeService::checkAny($menuId, $actions)`
Check if user has ANY of the specified actions.

```php
$hasAny = PrivilegeService::checkAny(7, ['edit', 'remove']);
```

#### `PrivilegeService::batchCheck($userId, $checks)`
Batch check multiple privileges (more efficient).

```php
$results = PrivilegeService::batchCheck(auth()->id(), [
    ['menuId' => 7, 'action' => 'add'],
    ['menuId' => 8, 'action' => 'edit'],
]);
```

#### `PrivilegeService::clearUserCache($userId = null)`
Clear cache for a user (call after privilege changes).

```php
PrivilegeService::clearUserCache(auth()->id());
```

### Helper Functions

| Function | Purpose | Example |
|----------|---------|---------|
| `checkPrivilege($menuId, $action)` | Check specific privilege | `checkPrivilege(7, 'add')` |
| `canAccessMenu($menuId)` | Check menu access | `canAccessMenu(7)` |
| `authorizePrivilege($menuId, $action)` | Authorize or abort 403 | `authorizePrivilege(7, 'edit')` |
| `getMenuPrivileges($menuId)` | Get all privileges as array | `getMenuPrivileges(7)` |
| `hasAnyPrivilege($menuId, $actions)` | Has ANY privilege | `hasAnyPrivilege(7, ['edit', 'remove'])` |
| `hasAllPrivileges($menuId, $actions)` | Has ALL privileges | `hasAllPrivileges(7, ['edit', 'add'])` |
| `getUserAccessibleMenus()` | Get all accessible menus | `getUserAccessibleMenus()` |
| `batchCheckPrivileges($checks)` | Batch check privileges | `batchCheckPrivileges([...])` |
| `clearUserPrivilegeCache($userId)` | Clear privilege cache | `clearUserPrivilegeCache(auth()->id())` |

## Configuration

Edit `config/privilege-manager.php` to customize:

```php
return [
    'cache' => [
        'enabled' => true,
        'ttl' => 3600, // Cache for 1 hour
    ],
    
    'rate_limit' => [
        'enabled' => true,
        'attempts' => 1000,
        'decay_minutes' => 1,
    ],
    
    'logging' => [
        'log_checks' => true,
        'log_denials' => true,
    ],
    
    'security' => [
        'enable_ip_check' => false,
        'allowed_actions' => ['add', 'edit', 'statuschange', 'remove'],
    ],
    
    'performance' => [
        'enable_batch_operations' => true,
        'preload_privileges' => true,
        'batch_size' => 100,
    ],
];
```

## Security Features

### ✓ Rate Limiting
Prevents abuse of privilege checking endpoints. Configurable per environment.

### ✓ Input Validation
All inputs are validated to prevent injection attacks.

### ✓ Comprehensive Logging
All privilege denials and suspicious activities are logged.

### ✓ Caching Strategy
Multi-level caching prevents database hammering while maintaining accuracy.

### ✓ IP Validation (Optional)
Can optionally check IP to prevent token theft.

## Performance Features

### ✓ Multi-Level Caching
User privileges are cached for 1 hour (configurable).

### ✓ Query Optimization
Minimal queries per check, with efficient relationship loading.

### ✓ Batch Operations
Check multiple privileges in a single operation.

### ✓ Lazy Loading Prevention
Relationships are properly configured to prevent N+1 queries.

## Migration Guide

If you're migrating from another privilege system:

### 1. Update Your User Model
Implement `PrivilegeUserContract` interface as shown in the installation section.

### 2. Update Routes
Replace middleware parameters:

```php
// Old
Route::middleware('privilege:7,add')->...

// New (same syntax)
Route::middleware('privilege:7,add')->...
```

### 3. Update Controllers
Replace manual checks:

```php
// Old
if (!$this->checkUserPrivilege(7, 'add')) {
    abort(403);
}

// New
authorizePrivilege(7, 'add');
```

### 4. Update Views
Helper functions remain compatible:

```blade
{{-- No changes needed! --}}
@if(checkPrivilege(7, 'add'))
    ...
@endif
```

## Troubleshooting

### Privileges Not Working
1. Ensure User model implements `PrivilegeUserContract`
2. Check that `privileges()` relationship is defined
3. Verify database tables exist and have correct names
4. Clear cache: `php artisan cache:clear`

### Cache Issues
Clear privilege cache after making changes:

```php
PrivilegeService::clearUserCache(auth()->id());
// or
php artisan cache:clear
```

### Performance Issues
Enable batch operations in config:

```php
'performance' => [
    'enable_batch_operations' => true,
    'batch_size' => 100,
]
```

### Logging Issues
Configure logging in `config/privilege-manager.php`:

```php
'logging' => [
    'log_checks' => true,
    'log_denials' => true,
]
```

## Testing

Run the included test suite:

```bash
./vendor/bin/phpunit tests/
```

## Contributing

Contributions are welcome! Please ensure:
- Code follows PSR-12 standards
- Tests pass: `./vendor/bin/phpunit`
- New features include tests and documentation

## License

MIT License. See LICENSE file for details.

## Support

For issues, questions, or suggestions, please open an issue on the repository.

---

**Built with ❤️ for Laravel developers**
