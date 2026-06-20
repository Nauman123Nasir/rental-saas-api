# Business Workflows - Global Asset Rental Management SaaS

This document outlines the core business processes for the MVP.

## 1. Tenant Lifecycle
`Signup -> Creation -> Subscription Activation -> Subdomain/Tenant Context Ready`

## 2. Rental Cycle (The Success Case)

### Step 1: Pre-requisites
- **Setup**: Branch is created.
- **Fleet**: At least one available asset exists.
- **Customer**: Customer is created and drivers are verified.

### Step 2: Reservation
1.  User checks **Availability** for specific dates.
2.  User creates **Reservation**.
3.  Pricing is **Snapshotted**.
4.  Reservation status becomes `Confirmed`.

### Step 3: Checkout (Pickup)
1.  Customer arrives at the branch.
2.  User performs **Inspection** (Initial status).
3.  Reservation is converted to **Rental**.
4.  Asset status changes to `Rented`.

### Step 4: Check-in (Return)
1.  Customer returns the asset.
2.  User performs **Return Inspection**.
3.  System calculates any **Additional Charges** (late fees, damages).
4.  Rental is closed.
5.  Asset status returns to `Available`.

### Step 5: Finance
1.  Invoice is generated from the Rental summary.
2.  Payment is recorded.
3.  Receipt is generated.

## 3. Auth Workflow (JWT)
`User Login -> Credentials Verified -> JWT Returned -> Front-end stores JWT -> Every Request includes JWT -> API Decodes user/tenant context`
