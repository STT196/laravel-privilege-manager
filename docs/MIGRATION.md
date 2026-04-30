# Migration Guide

Complete guide to migrating from your current privilege system to Laravel Privilege Manager.

## Overview

If you're currently using a legacy privilege system, this guide shows how to migrate to Laravel Privilege Manager with minimal changes to your existing code.

For fresh Laravel projects, you can install the package and publish its migrations:

```bash
composer require stt196/laravel-privilege-manager
php artisan privilege-manager:install
php artisan migrate
```

## Migration Steps

### Step 1: Install the Package

```bash
composer require stt196/laravel-privilege-manager
php artisan vendor:publish --provider="LaravelPrivilegeManager\Providers\PrivilegeManagerServiceProvider" --tag="privilege-manager-config"
php artisan vendor:publish --provider="LaravelPrivilegeManager\Providers\PrivilegeManagerServiceProvider" --tag="privilege-manager-migrations"
```

### Step 2: Update User Model

Implement the `PrivilegeUserContract` interface. See [INSTALLATION.md](INSTALLATION.md) for complete example.

### Step 3: Update Routes

The middleware syntax remains the same:

```php
// Before (legacy)
Route::middleware('privilege:7')->get('/customers', ...);
Route::middleware('privilege:7,add')->post('/customers', ...);

// After (Laravel Privilege Manager)
// No changes needed! Same syntax works!
Route::middleware('privilege:7')->get('/customers', ...);
Route::middleware('privilege:7,add')->post('/customers', ...);
```

### Step 4: Update Controllers

Replace manual privilege checks with new service:

**Before (Legacy):**
```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index()
    {
        // Legacy manual check
        if (!auth()->user()->hasPrivilegeForMenu(7)) {
            abort(403, 'Unauthorized');
        }
        
        return view('customer.index');
    }

    public function store(Request $request)
    {
        // Legacy check
        if (!auth()->user()->hasPrivilegeForAction(7, 'add')) {
            abort(403);
        }
        
        // ... create customer
    }
}
```

**After (Laravel Privilege Manager):**
```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CustomerController extends Controller
{
    const MENU_ID = 7; // Define as constant

    public function index()
    {
        // Option 1: Simple authorization
        authorizePrivilege(self::MENU_ID);
        
        return view('customer.index');
    }

    public function store(Request $request)
    {
        // Option 2: Check with custom response
        if (!checkPrivilege(self::MENU_ID, 'add')) {
            return response()->json(['message' => 'No permission'], 403);
        }
        
        // ... create customer
    }
}
```

**Or even better, use middleware:**
```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function __construct()
    {
        $this->middleware('privilege:7')->only(['index', 'show']);
        $this->middleware('privilege:7,add')->only(['create', 'store']);
        $this->middleware('privilege:7,edit')->only(['edit', 'update']);
        $this->middleware('privilege:7,remove')->only(['destroy']);
    }

    public function index()
    {
        // No manual checks needed!
        return view('customer.index');
    }

    public function store(Request $request)
    {
        // Already authorized by middleware
        // ... create customer
    }
}
```

### Step 5: Update Views

Helper functions remain compatible:

**Before:**
```blade
@if(checkUserPrivilege(7, 'add'))
    <button>Add Customer</button>
@endif
```

**After (No changes needed!):**
```blade
@if(checkPrivilege(7, 'add'))
    <button>Add Customer</button>
@endif
```

### Step 6: Test Everything

Run your test suite:

```bash
php artisan test
```

Test manually:

```bash
php artisan tinker
>>> auth()->loginUsingId(1);
>>> checkPrivilege(7, 'add');
true
>>> canAccessMenu(8);
false
```

## Feature Comparison

| Feature | Legacy | New Package | Notes |
|---------|--------|-------------|-------|
| Route middleware | ✓ | ✓ | Same syntax |
| Helper functions | ✓ | ✓ | Improved |
| Manual checks | ✓ | ✓ | Better service |
| Caching | Limited | ✓ | Multi-level |
| Rate limiting | ✗ | ✓ | NEW |
| Logging | ✗ | ✓ | NEW |
| Batch operations | ✗ | ✓ | NEW |
| Input validation | Limited | ✓ | Improved |
| Error handling | Basic | ✓ | Comprehensive |
| Documentation | Limited | ✓ | Complete |

## Parallel Running

Run both systems side-by-side during transition:

```php
// In middleware or controller
$legacy = auth()->user()->hasPrivilegeForMenu(7);
$new = PrivilegeService::canAccess(7);

if ($legacy !== $new) {
    Log::warning('Privilege mismatch', [
        'user_id' => auth()->id(),
        'menu_id' => 7,
        'legacy' => $legacy,
        'new' => $new,
    ]);
}
```

## Rollback Plan

If issues occur, keep the old system:

```php
// In helper functions
if (config('app.use_legacy_privileges')) {
    return auth()->user()->hasPrivilegeForAction($menuId, $action);
}

return PrivilegeService::check($menuId, $action);
```

Then set in `.env`:
```env
USE_LEGACY_PRIVILEGES=true
```

## Common Migration Issues

### Issue: "Class not found: PrivilegeUserContract"

**Solution:**
```bash
composer dump-autoload
php artisan config:clear
```

### Issue: "Method not found on User model"

**Solution:** Ensure User model implements `PrivilegeUserContract`:
```php
class User extends Authenticatable implements PrivilegeUserContract
{
    // Implement required methods...
}
```

### Issue: Privileges not working after migration

**Solution:** Clear cache:
```bash
php artisan cache:clear
```

Then verify user has privileges in database:
```bash
php artisan tinker
>>> User::find(1)->privileges()->count()
```

### Issue: Routes still use old middleware

**Solution:** Update route files:

```php
// Old route files
Route::middleware('privilege:7')->get('/customers', ...);

// Are now
Route::middleware('privilege:7')->get('/customers', ...);
// (Same thing, no changes needed!)
```

## Data Migration

No database changes needed! The package uses the same table structure:

```sql
-- Existing table works as-is
tbl_user_privilege
├─ idtbl_user_privilege (Primary Key)
├─ tbl_user_idtbl_user (Foreign Key to Users)
├─ tbl_menu_list_idtbl_menu_list (Foreign Key to Menus)
├─ access_status
├─ add
├─ edit
├─ statuschange
├─ remove
└─ status
```

## Post-Migration Checklist

- [ ] Package installed and configured
- [ ] User model implements `PrivilegeUserContract`
- [ ] Routes updated (if needed)
- [ ] Controllers updated
- [ ] Views tested
- [ ] Tests passing
- [ ] Cache clearing works
- [ ] Logging configured
- [ ] Rate limiting tested
- [ ] Production deployment

## Performance Improvements After Migration

You should see improvements in:

1. **Query Count** - Reduced from N queries to 1-2 per request
2. **Response Time** - Faster due to caching
3. **Database Load** - Less hammering from repeated checks
4. **Security** - Input validation and logging

## Timeline

Typical migration timeline:

- **Phase 1: Setup** (1-2 hours)
  - Install package
  - Update User model
  - Run tests

- **Phase 2: Migration** (2-4 hours)
  - Update routes
  - Update controllers
  - Update views

- **Phase 3: Testing** (2-4 hours)
  - Manual testing
  - Automated tests
  - Performance testing

- **Phase 4: Deployment** (1-2 hours)
  - Deploy to staging
  - Deploy to production
  - Monitor logs

**Total: 6-12 hours for a typical application**

## Support

For migration issues:
1. Check [TROUBLESHOOTING.md](TROUBLESHOOTING.md)
2. Review [INSTALLATION.md](INSTALLATION.md)
3. Check package tests for examples
4. Open an issue with details

---

**Your existing code works! The package is backward compatible.**
