# Database Design - Global Asset Rental Management SaaS

This document outlines the core logical schema for the MVP, based on Volume 9 of the SRS.

## 1. Multi-Tenant Strategy
The system uses a **Shared Database, Shared Schema** model. Every business-related table **MUST** include a `tenant_id` column for data isolation.

## 2. Core Tables (Phase 1)

### Tenants & Subscriptions
- **tenants**: `id`, `uuid`, `name`, `status`, `subscription_plan_id`, `currency_id`, `timezone_id`
- **subscription_plans**: `id`, `name`, `monthly_price`, `annual_price`, `features_json`

### Globalization Support
- **countries**: `id`, `name`, `iso2`, `iso3`, `phone_code`
- **currencies**: `id`, `code`, `symbol`, `name`, `decimal_places`
- **timezones**: `id`, `name`, `utc_offset`

### Organization Structure
- **branches**: `id`, `tenant_id`, `name`, `code`, `country_id`, `timezone_id`, `currency_id`, `address`, `city`, `state`, `postal_code`

### Identity (JWT Implementation)
- **users**: `id`, `tenant_id`, `branch_id`, `name`, `email`, `password`, `status`
- **roles**: `id`, `tenant_id`, `name`, `description`
- **permissions**: `id`, `module`, `action`
- **role_permissions**: `role_id`, `permission_id`

## 3. Business Modules (Phase 3-4)

### Customer Domain
- **customers**: `id`, `tenant_id`, `customer_code`, `type` (Individual/Business), `first_name`, `last_name`, `email`, `phone`, `status`, `credit_limit`
- **customer_documents**: `id`, `customer_id`, `document_type`, `document_number`, `expiry_date`, `file_path`
- **drivers**: `id`, `customer_id`, `first_name`, `last_name`, `license_number`, `license_expiry`

### Asset/Fleet Domain
- **asset_categories**: `id`, `tenant_id`, `name` (e.g., Cars, SUV, Equipment)
- **assets**: `id`, `tenant_id`, `branch_id`, `category_id`, `asset_code`, `name`, `brand`, `model`, `status` (Available, Reserved, Rented, Maintenance), `daily_rate`, `hourly_rate`
- **asset_blocks**: `id`, `asset_id`, `block_type`, `start_datetime`, `end_datetime`

## 4. Operational Tables (Phase 5-7)

### Reservations
- **reservations**: `id`, `tenant_id`, `customer_id`, `pickup_branch_id`, `return_branch_id`, `pickup_datetime_utc`, `return_datetime_utc`, `status`, `total_amount`
- **reservation_pricing**: `id`, `reservation_id`, `pricing_type`, `unit_rate`, `total`

### Rentals
- **rentals**: `id`, `tenant_id`, `reservation_id`, `asset_id`, `pickup_datetime`, `return_datetime`, `status`

### Finance
- **invoices**: `id`, `tenant_id`, `customer_id`, `amount`, `status`
- **payments**: `id`, `tenant_id`, `invoice_id`, `amount`, `payment_method`
