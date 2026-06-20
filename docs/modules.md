# Module Architecture - Global Asset Rental Management SaaS

This document defines the code structure for both the Backend and Frontend.

## 1. Backend (Laravel - `rental-saas-api`)
We follow a **Modular Domain** approach within `app/Modules`.

### Core Structure
- `app/Modules/Auth`: JWT authentication, login, profile management.
- `app/Modules/Common`: Shared services (Tenancy, Globalization, Storage).
- `app/Modules/Customers`: Customer, Driver, and Document logic.
- `app/Modules/Assets`: Fleet management, Categories, and Availability checks.
- `app/Modules/Reservations`: Booking logic, Pricing, and Conflicts.
- `app/Modules/Rentals`: Operations, Inspections, and Status transitions.
- `app/Modules/Finance`: Invoices and Payments.

## 2. Frontend (Angular - `rental-saas-web`)
We use a **Feature-based** folder architecture in `src/app`.

### Core Layers
- `src/app/core`: Singleton services (Auth, JWT Interceptor, API Client).
- `src/app/shared`: Common UI components, Directives (Permission), Pipes.
- `src/app/features`: Business modules with lazy loading.
    - `features/auth`: Login, Forgot Password.
    - `features/customers`: List, Create, Details.
    - `features/assets`: Dashboard, Categories.
    - `features/reservations`: Booking Calendar, Create Reservation.
    - `features/rentals`: Pickup/Return screens.

## 3. Communication Pattern
- Angular Features call **Services** in their own module.
- Services call the **Core API Client**.
- API Client handles JWT headers and error handling.
