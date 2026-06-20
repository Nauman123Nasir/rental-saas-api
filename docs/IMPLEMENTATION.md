# 🏗️ Implementation Notes — Rental SaaS API

Technical reference for architecture decisions, database design, patterns, and implementation details.

---

## Project Stack

| Setting | Value |
|---|---|
| Framework | Laravel 12 |
| PHP | 8.2+ |
| Auth | JWT (`php-open-source-saver/jwt-auth`) |
| Database | MySQL 8 (single DB, multi-tenant schema) |
| Response Format | JSON only (REST API) |
| Middleware | `jwt.verify`, `check.permission` |

---

## Multi-Tenant Architecture

**Strategy:** Single database, tenant-scoped rows (no separate schemas or databases per tenant).

Every tenant-owned table has a `tenant_id` foreign key. All queries are automatically filtered via the `BelongsToTenant` trait:

```php
// app/Traits/BelongsToTenant.php
trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (auth()->check()) {
                $builder->where('tenant_id', auth()->user()->tenant_id);
            }
        });

        static::creating(function ($model) {
            if (auth()->check() && empty($model->tenant_id)) {
                $model->tenant_id = auth()->user()->tenant_id;
            }
        });
    }
}
```

Apply to any model:
```php
class Customer extends Model {
    use BelongsToTenant;
}
```

---

## Authentication Flow

```
Client                    API
  │                         │
  ├─ POST /auth/login ──────►│  Validates credentials
  │                         │  Returns JWT token
  │◄── { token, user } ─────┤
  │                         │
  ├─ GET /customers ────────►│  Authorization: Bearer <token>
  │  (with JWT header)      │  jwt.verify middleware validates token
  │                         │  Resolves user + tenant from token
  │◄── { data: [...] } ─────┤  Returns tenant-scoped data
```

**JWT Configuration (`.env`):**
```
JWT_SECRET=<your_secret>
JWT_ALGO=HS256
```

**Token lifetime:** Default 60 minutes (configurable in `config/jwt.php`).

---

## Middleware Stack

Registered in `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'jwt.verify'       => \App\Http\Middleware\JwtMiddleware::class,
        'check.permission' => \App\Http\Middleware\CheckPermission::class,
    ]);
})
```

**Route protection pattern:**
```php
Route::middleware(['jwt.verify', 'check.permission:customers,view'])
    ->get('/customers', [CustomerController::class, 'index']);

Route::middleware(['jwt.verify', 'check.permission:customers,create'])
    ->post('/customers', [CustomerController::class, 'store']);
```

---

## Permission System

**Database tables:**
- `permissions` — `module`, `action` (e.g., `customers`, `view`)
- `roles` — tenant-scoped, `is_system` flag for Super Admin
- `role_permissions` — pivot
- `user_roles` — pivot

**CheckPermission middleware logic:**
```php
// Resolves user → roles → permissions
$userPermissions = auth()->user()
    ->roles()
    ->with('permissions')
    ->get()
    ->flatMap(fn($role) => $role->permissions)
    ->pluck('action', 'module')
    ->toArray();

// Checks if user has module.action OR module.*
if (!isset($userPermissions[$module]) || 
    !in_array($userPermissions[$module], [$action, '*'])) {
    return response()->json(['error' => 'Forbidden'], 403);
}
```

**Seeded roles:**
- `Super Admin` — all `module.*` permissions
- `Agent` — `customers.view/create/update`, `assets.view`, `reservations.*`, `rentals.view/create/update`

---

## Database Schema Overview

```
tenants ──< branches ──< users ──< user_roles >── roles ──< role_permissions >── permissions
    │
    ├──< customers ──< drivers
    │              └──< customer_documents
    │
    ├──< asset_categories
    ├──< assets ──< asset_blocks
    │
    ├──< reservations ──< reservation_notes
    │                 └──< reservation_attachments
    │
    ├──< rentals ──< rental_drivers
    │           ├──< rental_extensions
    │           ├──< rental_pickup_inspections
    │           ├──< rental_return_inspections
    │           ├──< rental_fuel_logs
    │           ├──< rental_odometer_logs
    │           ├──< rental_charges
    │           └──< invoices ──< invoice_lines
    │                        └──< payments ──< receipts
    │
    └── (subscription_plan_id) >── subscription_plans
```

---

## Controller Response Pattern

All controllers return consistent JSON:

```php
// Success list
return response()->json([
    'success' => true,
    'data'    => CustomerResource::collection($customers),
    'meta'    => [
        'current_page' => $customers->currentPage(),
        'last_page'    => $customers->lastPage(),
        'total'        => $customers->total(),
    ],
]);

// Success single
return response()->json(['success' => true, 'data' => $customer]);

// Error
return response()->json(['success' => false, 'message' => 'Not found'], 404);
```

---

## Eager Loading Policy

To avoid N+1 queries on list endpoints, always eager load relations that templates display:

```php
// RentalController@index
$rentals = Rental::with(['customer', 'asset'])
    ->where('tenant_id', auth()->user()->tenant_id)
    ->paginate(10);

// ReservationController@index
$reservations = Reservation::with(['customer'])
    ->paginate(10);

// InvoiceController@index
$invoices = Invoice::with(['customer', 'rental'])
    ->paginate(10);
```

---

## Seeder Order (Important)

Seeders must run in dependency order:

```php
// DatabaseSeeder.php
$this->call([
    CountrySeeder::class,          // no deps
    CurrencySeeder::class,         // no deps
    TimezoneSeeder::class,         // no deps
    SubscriptionPlanSeeder::class, // no deps
    TenantIdentitySeeder::class,   // depends on all above
]);
```

`TenantIdentitySeeder` creates: tenant → branch → permissions → roles → role_permissions → users → user_roles.

---

## Asset Status Lifecycle

```
Available
    │
    ├─ [reservation created] ──► Reserved
    │       │
    │       └─ [rental checkout] ──► Rented
    │               │
    │               └─ [rental checkin] ──► Available
    │
    └─ [blocked for maintenance] ──► Maintenance
            │
            └─ [block removed] ──► Available
```

Status changes are triggered in `RentalController` and `AssetBlockController`.

---

## Invoice Generation Flow

```
Rental completed (checkin)
    │
    └─► InvoiceController@generate(rental_id)
            │
            ├─ Creates Invoice record
            ├─ Creates InvoiceLine records from rental_charges
            ├─ Sets Invoice status = 'Issued'
            └─ Returns invoice with lines

Customer pays ──► PaymentController@store
    │
    ├─ Creates Payment record
    ├─ Updates Invoice balance_due
    ├─ If balance_due == 0: sets Invoice status = 'Paid'
    └─ Auto-creates Receipt
```

---

## Known Limitations / Future Work

| Item | Notes |
|------|-------|
| File storage | Customer documents stored as path strings; no actual file upload endpoint yet |
| Email notifications | `MAIL_MAILER=log` — emails only logged, not sent |
| Queue workers | `QUEUE_CONNECTION=database` — queued jobs need `php artisan queue:work` running |
| API versioning | All routes under `/api/v1` — future versions would add `/api/v2` |
| Rate limiting | No API rate limiting implemented yet |
| Refresh tokens | JWT refresh endpoint exists but frontend doesn't auto-refresh on expiry |

---

## 📄 Software Requirements Specification (SRS)

All original project requirements, roadmap, volumes, and MVP planning documents are stored in the project repository under:
- [`docs/SRS_Chatgpt/`](file:///D:/rental-saas-api/docs/SRS_Chatgpt)

These include:
- **`All-volumes-complete-story.docx`**: Complete functional specifications and user story backlog.
- **`Our MVP_Plan.docx`**: Core MVP requirements & pricing details.
- **`MVP-ROADMAP.docx`**: Iteration/phase roadmap.
- **`Vol1.docx` through `vol14-RoadMap.docx`**: Detailed feature volumes (categories, fleet, rentals, booking, invoicing, etc.).
- **`credentials.txt`**: Seeded login credentials.
