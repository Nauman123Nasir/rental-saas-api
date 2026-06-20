# API Specifications - Global Asset Rental Management SaaS

This document defines the REST API structure and security standards for the MVP.

## 1. Standards
- **Version**: `/api/v1`
- **Format**: All requests and responses must be in `application/json`.
- **Authentication**: **JWT (JSON Web Token)**.
    - Token must be provided in the `Authorization: Bearer <token>` header.
- **Tenancy**: The `tenant_id` is derived from the authenticated user's profile and should not be passed in the URL.

## 2. Core Endpoints

### Authentication
- `POST /auth/login`: Authenticate and receive JWT.
- `POST /auth/logout`: Invalidate current token.
- `GET /auth/me`: Get current user profile and permissions.

### Customer Module
- `GET /customers`: List customers (paginated, with filters).
- `POST /customers`: Create a new customer.
- `GET /customers/{id}`: Get customer details (including drivers/documents).
- `PUT /customers/{id}`: Update customer info.

### Asset Module
- `GET /assets`: List all assets.
- `GET /assets/availability`: Check asset availability for specific dates.
- `POST /assets`: Add a new vehicle/equipment to the fleet.

### Booking Engine
- `POST /reservations`: Create a new booking.
- `GET /reservations/{id}`: View reservation status and pricing.

### Operations
- `POST /rentals/checkout`: Convert reservation to active rental (pickup).
- `POST /rentals/checkin`: Close active rental (return).

## 3. Success & Error Handling
- `200 OK`: Successful retrieval.
- `201 Created`: Successful creation.
- `401 Unauthorized`: Missing or invalid JWT.
- `403 Forbidden`: Authenticated user lacks permission for the action.
- `422 Unprocessable Entity`: Validation errors (fields/requirements).
