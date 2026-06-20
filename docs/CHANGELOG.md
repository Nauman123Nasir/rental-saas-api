# 📋 Project Changelog — Rental SaaS API

A chronological log of all features, fixes, and improvements built in the backend.

---

## [Phase 9] — Bug Fixes & Polish _(June 2026)_

### ✅ Middleware & Routing
- Fixed middleware alias: `bootstrap/app.php` `'permission'` → `'check.permission'`
- Ensured all protected routes correctly apply both `jwt.verify` and `check.permission` middleware

### ✅ Eager Loading on List Endpoints
- `RentalController@index` — added `with(['customer', 'asset'])` to eager load relations
- `ReservationController@index` — added `with(['customer'])` to eager load relations
- Resolves frontend display bug where customer/asset names showed as blank

### ✅ Documentation
- Created `README.md` with full setup guide, credentials, and API reference
- Created `CHANGELOG.md` and `IMPLEMENTATION.md` in `docs/`

---

## [Phase 8] — Operational Dashboard _(June 2026)_

### ✅ DashboardController
- Endpoint: `GET /api/v1/dashboard`
- Returns aggregated stats scoped to authenticated tenant:
  - `total_assets`, `available_assets`, `active_rentals`, `pending_reservations`
  - `today_revenue` (sum of payments made today)
  - `fleet_status` distribution map
  - `monthly_revenue` — last 6 months payment totals
  - `monthly_bookings` — last 6 months reservation counts
- Protected by `jwt.verify` middleware

---

## [Phase 7] — Finance Module _(June 2026)_

### ✅ Migrations
- `invoices` — tenant-scoped, linked to rental, customer, currency
- `invoice_lines` — line items with unit price, quantity, subtotal, tax
- `payments` — linked to invoice, supports multiple payment methods
- `receipts` — auto-generated on each successful payment

### ✅ Models & Controllers
- `Invoice`, `InvoiceLine`, `Payment`, `Receipt` Eloquent models
- Added `invoices()` HasMany relationship on `Rental` model
- `InvoiceController`: `index`, `show`, `generate` (from rental), `void`
- `PaymentController`: `index`, `show`, `store` (auto-creates receipt on success)

### ✅ Routes
```
GET    /api/v1/finance/invoices
GET    /api/v1/finance/invoices/{id}
POST   /api/v1/finance/invoices/{id}/generate
POST   /api/v1/finance/invoices/{id}/void
GET    /api/v1/finance/payments
POST   /api/v1/finance/payments
GET    /api/v1/finance/payments/{id}
```

---

## [Phase 6] — Rental Operations Module _(June 2026)_

### ✅ Migrations
- `rentals`, `rental_drivers`, `rental_extensions`
- `rental_pickup_inspections`, `rental_return_inspections`
- `rental_fuel_logs`, `rental_odometer_logs`, `rental_charges`

### ✅ RentalController
- `checkout` endpoint — sets asset status to `Rented`, records pickup inspection
- `checkin` endpoint — sets asset status back to `Available`, records return inspection
- CRUD: `index`, `show`, `store`, `update`, `destroy`

---

## [Phase 5] — Reservations Module _(June 2026)_

### ✅ Migrations
- `reservations`, `reservation_notes`, `reservation_attachments`

### ✅ ReservationController
- Full CRUD with tenant isolation
- Status transitions: `pending` → `confirmed` → `cancelled` / `completed`

---

## [Phase 4] — Fleet / Asset Management Module _(June 2026)_

### ✅ Migrations
- `asset_categories` — tenant-scoped categories
- `assets` — full vehicle spec fields (VIN, brand, model, year, rates, status)
- `asset_blocks` — maintenance / unavailability periods

### ✅ AssetController
- Paginated `index` with filters (search, category, status)
- CRUD: `store`, `update`, `destroy`, `show`
- Status transitions tied to rental/reservation lifecycle

---

## [Phase 3] — Customer Management Module _(June 2026)_

### ✅ Migrations
- `customers`, `drivers`, `customer_documents`

### ✅ Models & Traits
- `BelongsToTenant` trait auto-scopes all queries to `tenant_id`
- `Customer` hasMany `Driver`, hasMany `CustomerDocument`
- Nested create/update wrapped in DB transactions

### ✅ CustomerController
- Paginated `index` with search, type, status filters
- `store` — creates customer + drivers + documents atomically
- `update` — syncs nested relations safely

---

## [Phase 2] — Auth, RBAC & Core Infrastructure _(June 2026)_

### ✅ Migrations
- `tenants`, `branches`, `users`
- `subscription_plans`
- `countries`, `currencies`, `timezones`
- `roles`, `permissions`, `role_permissions`, `user_roles`

### ✅ JWT Authentication
- `AuthController`: `login`, `logout`, `me`, `refresh`
- Token stored on client, verified via `jwt.verify` middleware alias
- `php-open-source-saver/jwt-auth` package

### ✅ Permission Middleware
- `CheckPermission` middleware reads `module.action` from route
- Checks: `user → user_roles → roles → role_permissions → permissions`
- Middleware registered as `check.permission` alias in `bootstrap/app.php`

### ✅ Seeders
- `CountrySeeder` — all world countries
- `CurrencySeeder` — all ISO currencies
- `TimezoneSeeder` — all world timezones
- `SubscriptionPlanSeeder` — Basic, Professional, Enterprise plans
- `TenantIdentitySeeder` — demo tenant, branch, roles, permissions, users

---

## [Phase 1] — Project Scaffolding _(June 2026)_

### ✅ Laravel 12 Setup
- Created with `composer create-project laravel/laravel rental-saas-api`
- Configured MySQL single-database multi-tenant schema
- Installed `php-open-source-saver/jwt-auth`
- Established base folder structure for controllers, models, middleware
