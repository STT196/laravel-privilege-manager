# Security & Performance Documentation

## Security Features

### 1. Rate Limiting

Prevents abuse of privilege checking endpoints.

```php
// config/privilege-manager.php
'rate_limit' => [
    'enabled' => true,
    'attempts' => 1000,  // Per user per minute
    'decay_minutes' => 1,
]
```

**How it works:**
- Each user can check privileges up to 1000 times per minute
- Additional checks are rate limited and logged as warnings
- Configurable per environment

**Disable for development:**
```env
PRIVILEGE_RATE_LIMIT_ENABLED=false
```

### 2. Input Validation

All inputs are strictly validated to prevent injection attacks.

```php
// Automatic validation
CheckPrivilege middleware:
- Validates menuId is numeric and > 0
- Validates action is in allowed list ['add', 'edit', 'statuschange', 'remove']
- Logs invalid attempts
```

### 3. Comprehensive Logging

All security events are logged.

```php
// Log locations
storage/logs/laravel.log

// What gets logged:
- Failed privilege checks
- Invalid input attempts
- Rate limit violations
- Cache operations (optional)
```

**Enable detailed logging:**
```env
PRIVILEGE_LOG_CHECKS=true
PRIVILEGE_LOG_DENIALS=true
PRIVILEGE_LOG_CACHE=true
```

### 4. Multi-Level Caching

Caching prevents database hammering while maintaining consistency.

```php
// Cache strategy
1. User full privileges (keyed by user_id and menu_id)
   TTL: 1 hour (configurable)
   
2. User accessible menus (keyed by user_id)
   TTL: 1 hour (configurable)
   
3. Privilege arrays for frontend
   TTL: 1 hour (configurable)
```

**Clear cache after privilege changes:**
```php
// Option 1: Clear specific user
PrivilegeService::clearUserCache(auth()->id());

// Option 2: Clear all cache
php artisan cache:clear
```

### 5. Authentication Checks

All privilege checks require authentication.

```php
// Unauthenticated users
- Always return false for privilege checks
- Are redirected to login page
- Cannot access protected routes
```

### 6. Database Query Protection

Parameterized queries prevent SQL injection.

```php
// All database queries use Laravel's query builder
// Automatically parameterized, no string interpolation
```

### 7. Optional IP Validation

Validate that privilege checks come from expected IP addresses.

```php
// config/privilege-manager.php
'security' => [
    'enable_ip_check' => true,  // Enable to check IP
]
```

## Performance Optimization

### 1. Multi-Level Caching

**Cache Hierarchy:**

```
Request
  ↓
Check if cached
  ├─ YES → Return from cache
  └─ NO → Query database
              ↓
         Cache result
              ↓
         Return
```

**Cache Keys:**
```
privilege_array_{user_id}_{menu_id}
privilege_menus_{user_id}
privilege_full_privileges_{user_id}
```

### 2. Batch Operations

Check multiple privileges efficiently:

```php
// Old way (4 database calls)
$canAdd = checkPrivilege(7, 'add');
$canEdit = checkPrivilege(7, 'edit');
$canRemove = checkPrivilege(7, 'remove');
$canAccess = canAccessMenu(8);

// New way (optimized)
$results = batchCheckPrivileges([
    ['menuId' => 7, 'action' => 'add'],
    ['menuId' => 7, 'action' => 'edit'],
    ['menuId' => 7, 'action' => 'remove'],
    ['menuId' => 8],
]);

// Results:
// [
//     'menu_7_action_add' => true,
//     'menu_7_action_edit' => true,
//     'menu_7_action_remove' => false,
//     'menu_8' => true,
// ]
```

### 3. Query Optimization

**Before:**
```sql
SELECT * FROM tbl_user_privilege 
WHERE tbl_user_idtbl_user = 1;
-- Selects all columns

SELECT * FROM tbl_menu_list 
WHERE idtbl_menu_list = 7;
-- Separate query for each menu
```

**After:**
```sql
SELECT tbl_menu_list_idtbl_menu_list, access_status, status, add, edit, statuschange, remove
FROM tbl_user_privilege 
WHERE tbl_user_idtbl_user = 1
  AND status = 1
  AND access_status = 1;
-- Only necessary columns, single query
```

### 4. Relationship Loading

Optimize N+1 query problems:

```php
// Before: N+1 queries
$menus = PrivilegeService::getAccessibleMenus();
foreach ($menus as $menu) {
    echo $menu->menuname; // Extra query per menu
}

// After: Single query with relationship
$menus = PrivilegeService::getAccessibleMenus();
// Already loaded via ->with('menu')
foreach ($menus as $menu) {
    echo $menu->menuname; // No extra queries
}
```

### 5. Cache TTL Configuration

Adjust cache duration based on your needs:

```php
// config/privilege-manager.php

// Short cache (5 minutes) - Frequently changing privileges
'cache' => [
    'ttl' => 300,
]

// Medium cache (1 hour) - Default, recommended
'cache' => [
    'ttl' => 3600,
]

// Long cache (4 hours) - Stable privileges
'cache' => [
    'ttl' => 14400,
]
```

### 6. Preload Privileges

Automatically load privileges on authentication to reduce queries:

```php
// config/privilege-manager.php
'performance' => [
    'preload_privileges' => true,  // Load on login
]
```

**Implementation in EventServiceProvider:**
```php
// app/Providers/EventServiceProvider.php
use Illuminate\Auth\Events\Login;

protected $listen = [
    Login::class => [
        'App\\Listeners\\PreloadPrivileges@handle',
    ],
];
```

**Listener:**
```php
// app/Listeners/PreloadPrivileges.php
public function handle(Login $event)
{
    // Trigger cache population
    $event->user->getCachedFullPrivileges();
}
```

## Performance Benchmarks

Typical query times on a well-configured system:

| Operation | Time | Queries |
|-----------|------|---------|
| Single privilege check (cached) | < 1ms | 0 |
| Single privilege check (first) | 2-5ms | 1 |
| Batch check 5 items (cached) | < 2ms | 0 |
| Batch check 5 items (first) | 5-10ms | 1 |
| Get accessible menus (cached) | < 1ms | 0 |
| Get accessible menus (first) | 10-20ms | 1 |

## Monitoring & Debugging

### Enable Query Logging

```php
// In a controller or middleware
DB::enableQueryLog();

// ... your code ...

$queries = DB::getQueryLog();
foreach ($queries as $query) {
    echo $query['query'];
    echo $query['time'];
}
```

### Monitor Cache Usage

```php
// Check cache hit rate
Cache::getStore()->flush(); // Clear all
checkPrivilege(7, 'add'); // Miss
checkPrivilege(7, 'add'); // Hit
```

### Enable Privilege Logging

```env
# .env
PRIVILEGE_LOG_CHECKS=true
PRIVILEGE_LOG_DENIALS=true
PRIVILEGE_LOG_CACHE=true

# Then check logs:
tail -f storage/logs/laravel.log
```

## Best Practices

### 1. Cache Invalidation

**After changing privileges:**
```php
PrivilegeService::clearUserCache($userId);
```

**In your privilege management controller:**
```php
public function update(Request $request, UserPrivilege $privilege)
{
    $privilege->update($request->validated());
    
    // Clear cache
    PrivilegeService::clearUserCache($privilege->tbl_user_idtbl_user);
    
    return response()->json(['message' => 'Privilege updated']);
}
```

### 2. Batch Operations for Reports

When generating reports with multiple privilege checks:

```php
// Bad: Many queries
foreach ($users as $user) {
    auth()->loginAs($user);
    if (checkPrivilege(7, 'add')) { ... }
}

// Good: Single batch operation
$privilegeChecks = [
    ['menuId' => 7, 'action' => 'add'],
    ['menuId' => 7, 'action' => 'edit'],
];
$results = batchCheckPrivileges($privilegeChecks);
```

### 3. Use Middleware for Route Protection

```php
// Best practice: Use middleware on routes
Route::middleware('privilege:7,add')->post('/customers', [CustomerController::class, 'store']);

// Avoid: Manual checks in controller
public function store() {
    if (!checkPrivilege(7, 'add')) abort(403);
    // ...
}
```

### 4. Minimize Cache Variations

Keep cache keys consistent:

```php
// Avoid: Different keys for same check
checkPrivilege(7, 'add');
PrivilegeService::check(7, 'add');
hasAnyPrivilege(7, ['add']);

// Better: Use same helper consistently
if (checkPrivilege(7, 'add')) { ... }
```

### 5. Monitor Rate Limiting

```php
// Log rate limit violations
// Check storage/logs/laravel.log for:
// "Privilege check rate limit exceeded"
```

## Troubleshooting Performance

### Issue: Slow privilege checks

**Solution 1:** Enable caching
```env
PRIVILEGE_CACHE_ENABLED=true
```

**Solution 2:** Use batch operations
```php
batchCheckPrivileges([...])
```

**Solution 3:** Reduce cache TTL to ensure fresh data
```php
'cache' => [
    'ttl' => 300, // 5 minutes
]
```

### Issue: Too many database queries

**Solution:** Check if caching is working
```php
// In a Tinker session
DB::enableQueryLog();
checkPrivilege(7, 'add');
checkPrivilege(7, 'add');
count(DB::getQueryLog()); // Should be 1, not 2
```

### Issue: Cache not clearing

**Solution:** Verify cache driver
```env
CACHE_DRIVER=redis  # or file, database
```

Then manually clear:
```bash
php artisan cache:clear
```

---

**Security first, performance second. The package optimizes both.**
