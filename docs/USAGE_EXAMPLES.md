# Usage Examples

Real-world examples of using Laravel Privilege Manager in your application.

## Controller Examples

### Basic CRUD Controller

```php
<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use LaravelPrivilegeManager\Services\PrivilegeService;

class CustomerController extends Controller
{
    const MENU_ID = 7;

    public function __construct()
    {
        // Method 1: Middleware in constructor
        $this->middleware('privilege:' . self::MENU_ID)->only(['index', 'show']);
        $this->middleware('privilege:' . self::MENU_ID . ',add')->only(['create', 'store']);
        $this->middleware('privilege:' . self::MENU_ID . ',edit')->only(['edit', 'update']);
        $this->middleware('privilege:' . self::MENU_ID . ',remove')->only(['destroy']);
    }

    public function index()
    {
        // Privileges already validated by middleware
        $customers = Customer::where('status', 1)->get();
        
        // Get privilege info for view
        $privileges = getMenuPrivileges(self::MENU_ID);
        
        return view('customer.index', compact('customers', 'privileges'));
    }

    public function create()
    {
        return view('customer.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:customers',
            'phone' => 'required|string',
        ]);

        $customer = Customer::create($validated);

        return response()->json([
            'message' => 'Customer created successfully',
            'id' => $customer->id
        ], 201);
    }

    public function edit(Customer $customer)
    {
        return view('customer.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'email' => 'email|unique:customers,email,' . $customer->id,
            'phone' => 'string',
        ]);

        $customer->update($validated);

        // Clear cache after privilege-requiring update
        PrivilegeService::clearUserCache(auth()->id());

        return response()->json(['message' => 'Customer updated']);
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();
        return response()->json(['message' => 'Customer deleted']);
    }
}
```

### Advanced Controller with Multiple Permissions

```php
<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    const MENU_GENERATE_REPORT = 15;
    const MENU_VIEW_REPORT = 16;
    const MENU_DELETE_REPORT = 17;

    public function generateReport(Request $request)
    {
        // Method 2: Check specific action
        authorizePrivilege(self::MENU_GENERATE_REPORT, 'add');

        $type = $request->input('type');
        $filters = $request->input('filters', []);

        $report = Report::generateByType($type, $filters);

        return response()->json([
            'id' => $report->id,
            'download_url' => route('report.download', $report)
        ]);
    }

    public function view(Report $report)
    {
        // Check if user can view any type of report
        if (!hasAnyPrivilege(self::MENU_VIEW_REPORT, ['edit', 'statuschange'])) {
            abort(403, 'Cannot view reports');
        }

        return view('report.view', compact('report'));
    }

    public function deleteMultiple(Request $request)
    {
        // Check if user has ALL permissions needed
        if (!hasAllPrivileges(self::MENU_DELETE_REPORT, ['edit', 'remove'])) {
            abort(403, 'Cannot delete reports');
        }

        $ids = $request->input('ids', []);
        Report::whereIn('id', $ids)->delete();

        return response()->json(['message' => 'Reports deleted']);
    }
}
```

## Blade View Examples

### Simple Permission Checks

```blade
<!-- resources/views/customer/index.blade.php -->
<div class="card">
    <div class="card-header">
        <h3>Customers</h3>
        
        @if(checkPrivilege(7, 'add'))
            <a href="{{ route('customer.create') }}" class="btn btn-primary">
                Add Customer
            </a>
        @endif
    </div>

    <div class="card-body">
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    
                    @if(hasAnyPrivilege(7, ['edit', 'remove', 'statuschange']))
                        <th>Actions</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach($customers as $customer)
                    <tr>
                        <td>{{ $customer->name }}</td>
                        <td>{{ $customer->email }}</td>
                        <td>{{ $customer->phone }}</td>
                        
                        @if(hasAnyPrivilege(7, ['edit', 'remove', 'statuschange']))
                            <td>
                                <div class="btn-group">
                                    @if(checkPrivilege(7, 'edit'))
                                        <a href="{{ route('customer.edit', $customer) }}" 
                                           class="btn btn-sm btn-warning">
                                            Edit
                                        </a>
                                    @endif

                                    @if(checkPrivilege(7, 'statuschange'))
                                        <button class="btn btn-sm btn-info"
                                                onclick="toggleStatus({{ $customer->id }})">
                                            {{ $customer->status == 1 ? 'Disable' : 'Enable' }}
                                        </button>
                                    @endif

                                    @if(checkPrivilege(7, 'remove'))
                                        <button class="btn btn-sm btn-danger"
                                                onclick="deleteCustomer({{ $customer->id }})">
                                            Delete
                                        </button>
                                    @endif
                                </div>
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<script>
function toggleStatus(customerId) {
    // Implementation
}

function deleteCustomer(customerId) {
    // Implementation
}
</script>
```

### Navigation Menu Based on Privileges

```blade
<!-- resources/views/layouts/sidebar.blade.php -->
<div class="sidebar">
    <nav>
        @php
            $menus = getUserAccessibleMenus();
        @endphp

        @foreach($menus as $menu)
            <a href="{{ $menu->menuurl }}" 
               class="menu-item{{ request()->is($menu->menuurl . '*') ? ' active' : '' }}">
                {{ $menu->menuname }}
            </a>
        @endforeach
    </nav>
</div>
```

## JavaScript/AJAX Examples

### Frontend Permission Checks

```javascript
// resources/js/privilege-manager.js

class PrivilegeManager {
    constructor() {
        this.privileges = {};
        this.loadPrivileges();
    }

    async loadPrivileges() {
        try {
            const response = await fetch('/api/user/privileges');
            const data = await response.json();
            this.privileges = data;
        } catch (error) {
            console.error('Failed to load privileges:', error);
        }
    }

    can(action, menuId) {
        const key = `menu_${menuId}_action_${action}`;
        return this.privileges[key] || false;
    }

    canAccess(menuId) {
        const key = `menu_${menuId}`;
        return this.privileges[key] || false;
    }

    showIfCan(selector, action, menuId) {
        const element = document.querySelector(selector);
        if (element) {
            element.style.display = this.can(action, menuId) ? 'block' : 'none';
        }
    }

    disableIfCannot(selector, action, menuId) {
        const element = document.querySelector(selector);
        if (element) {
            element.disabled = !this.can(action, menuId);
        }
    }
}

// Initialize on page load
const pm = new PrivilegeManager();

// Usage
pm.showIfCan('#addBtn', 'add', 7);
pm.disableIfCannot('#editBtn', 'edit', 7);
pm.disableIfCannot('#deleteBtn', 'remove', 7);
```

### DataTables with Permission-Based Actions

```javascript
// Initialize DataTable with privilege-aware actions
$('#customersTable').DataTable({
    ajax: '/api/customers/data',
    columns: [
        { data: 'id' },
        { data: 'name' },
        { data: 'email' },
        { data: 'phone' },
        {
            data: null,
            render: function(data, type, row) {
                let actions = '<div class="btn-group">';

                if (pm.can('edit', 7)) {
                    actions += `<a href="/customers/${row.id}/edit" class="btn btn-sm btn-warning">Edit</a>`;
                }

                if (pm.can('statuschange', 7)) {
                    const status = row.status === 1 ? 'Disable' : 'Enable';
                    actions += `<button class="btn btn-sm btn-info" onclick="toggleStatus(${row.id})">${status}</button>`;
                }

                if (pm.can('remove', 7)) {
                    actions += `<button class="btn btn-sm btn-danger" onclick="deleteCustomer(${row.id})">Delete</button>`;
                }

                actions += '</div>';
                return actions;
            }
        }
    ],
    drawCallback: function() {
        KTMenu.createInstances();
    }
});
```

## API Examples

### JSON API Endpoint Returning Privileges

```php
<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

class PrivilegeController extends ApiController
{
    public function getUserPrivileges(Request $request)
    {
        // Get all privileges for authenticated user
        $privileges = batchCheckPrivileges([
            ['menuId' => 1, 'action' => 'add'],
            ['menuId' => 1, 'action' => 'edit'],
            ['menuId' => 1, 'action' => 'remove'],
            ['menuId' => 7, 'action' => 'add'],
            ['menuId' => 7, 'action' => 'edit'],
            ['menuId' => 7, 'action' => 'remove'],
            ['menuId' => 8, 'action' => 'add'],
            ['menuId' => 8, 'action' => 'edit'],
        ]);

        return response()->json($privileges);
    }

    public function checkPermission(Request $request)
    {
        $menuId = $request->input('menu_id');
        $action = $request->input('action');

        $granted = checkPrivilege($menuId, $action);

        return response()->json([
            'menu_id' => $menuId,
            'action' => $action,
            'granted' => $granted,
            'user_id' => auth()->id(),
        ]);
    }
}
```

## Event Listener Examples

### Clear Cache After Privilege Update

```php
<?php

namespace App\Listeners;

use App\Events\UserPrivilegeUpdated;
use LaravelPrivilegeManager\Services\PrivilegeService;

class ClearPrivilegeCache
{
    public function handle(UserPrivilegeUpdated $event)
    {
        PrivilegeService::clearUserCache($event->userId);
    }
}
```

### Log Privilege Changes

```php
<?php

namespace App\Listeners;

use App\Events\UserPrivilegeUpdated;
use Illuminate\Support\Facades\Log;

class LogPrivilegeChange
{
    public function handle(UserPrivilegeUpdated $event)
    {
        Log::info('User privilege updated', [
            'user_id' => $event->userId,
            'menu_id' => $event->menuId,
            'action' => $event->action,
            'granted' => $event->granted,
            'updated_by' => auth()->id(),
        ]);
    }
}
```

## Testing Examples

### Unit Tests

```php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use LaravelPrivilegeManager\Services\PrivilegeService;

class PrivilegeServiceTest extends TestCase
{
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_check_privilege_returns_false_for_unauthenticated_user()
    {
        $result = PrivilegeService::check(7, 'add');
        $this->assertFalse($result);
    }

    public function test_check_privilege_returns_true_for_granted_privilege()
    {
        $this->actingAs($this->user);
        
        // Grant privilege
        $this->user->privileges()->create([
            'tbl_menu_list_idtbl_menu_list' => 7,
            'access_status' => 1,
            'add' => 1,
            'status' => 1,
        ]);

        $result = PrivilegeService::check(7, 'add');
        $this->assertTrue($result);
    }

    public function test_batch_check_optimizes_queries()
    {
        $this->actingAs($this->user);

        \DB::enableQueryLog();

        batchCheckPrivileges([
            ['menuId' => 7, 'action' => 'add'],
            ['menuId' => 7, 'action' => 'edit'],
            ['menuId' => 7, 'action' => 'remove'],
        ]);

        $queryCount = count(\DB::getQueryLog());
        $this->assertLessThan(3, $queryCount); // Should use caching
    }
}
```

### Feature Tests

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;

class PrivilegeMiddlewareTest extends TestCase
{
    public function test_unauthenticated_user_redirected_to_login()
    {
        $response = $this->get('/customers');
        $response->assertRedirect('/login');
    }

    public function test_user_without_privilege_gets_403()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)->get('/customers');
        $response->assertStatus(403);
    }

    public function test_user_with_privilege_can_access()
    {
        $user = User::factory()->create();
        $user->privileges()->create([
            'tbl_menu_list_idtbl_menu_list' => 7,
            'access_status' => 1,
            'status' => 1,
        ]);

        $response = $this->actingAs($user)->get('/customers');
        $response->assertStatus(200);
    }
}
```

---

**Ready to implement? Start with the [Installation Guide](INSTALLATION.md)**
