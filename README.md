# 🚗 Rental SaaS — Backend API

A multi-tenant car rental SaaS REST API built with **Laravel 12** and **JWT Authentication**. Supports multiple tenants, branches, roles & permissions, customers, fleet assets, reservations, rentals, and invoicing.

---

## 📋 Requirements

| Dependency | Version |
|---|---|
| PHP | `^8.2` |
| Composer | `^2.x` |
| MySQL | `^8.0` (or MariaDB `^10.6`) |
| Laravel | `^12.0` |

> **Recommended local stack:** [XAMPP](https://www.apachefriends.org/) with PHP 8.2+ and MySQL 8.

---

## ⚙️ Tech Stack

- **Framework:** Laravel 12
- **Auth:** JWT via `php-open-source-saver/jwt-auth`
- **Database:** MySQL (multi-tenant schema, single DB)
- **Architecture:** RESTful API — all responses are JSON

---

## 🚀 Installation & Setup

### 1. Clone the repository

```bash
git clone <repo-url>
cd rental-saas-api
```

### 2. Install PHP dependencies

```bash
composer install
```

### 3. Copy environment file

```bash
cp .env.example .env
```

### 4. Configure your `.env`

Open `.env` and update the following:

```dotenv
APP_NAME="Rental SaaS API"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=rental_saas
DB_USERNAME=root
DB_PASSWORD=          # leave empty for XAMPP default

JWT_SECRET=           # generated in next step
JWT_ALGO=HS256
```

### 5. Generate app key & JWT secret

```bash
php artisan key:generate
php artisan jwt:secret
```

### 6. Create the database

Open **phpMyAdmin** (or MySQL CLI) and create a database named:

```
rental_saas
```

### 7. Run migrations

```bash
php artisan migrate
```

### 8. Seed the database

This seeds countries, currencies, timezones, subscription plans, a demo tenant, branches, roles, permissions, and test users.

```bash
php artisan db:seed
```

### 9. Start the development server

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

The API will be available at: **`http://127.0.0.1:8000/api/v1`**

---

## 🔑 Login Credentials

After seeding, the following accounts are ready to use:

### Super Admin _(all permissions)_

| Field    | Value                   |
|----------|-------------------------|
| Email    | `admin@acmerental.com`  |
| Password | `password`              |
| Role     | Super Admin             |
| Tenant   | Acme Rent-A-Car         |

### Agent _(limited operational permissions)_

| Field    | Value                   |
|----------|-------------------------|
| Email    | `agent@acmerental.com`  |
| Password | `password`              |
| Role     | Agent                   |
| Tenant   | Acme Rent-A-Car         |

> The **Agent** role can: view/create/update customers, view assets, manage reservations and rentals. It cannot access finance or user management.

---

## 📡 API Base URL

```
http://127.0.0.1:8000/api/v1
```

### Key Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/auth/login` | Login and receive JWT token |
| `GET`  | `/auth/me` | Get authenticated user |
| `POST` | `/auth/logout` | Logout |
| `GET`  | `/customers` | List all customers |
| `GET`  | `/assets` | List fleet assets |
| `GET`  | `/reservations` | List reservations |
| `GET`  | `/rentals` | List rentals |
| `GET`  | `/finance/invoices` | List invoices |
| `GET`  | `/users` | List users (Admin only) |

All protected routes require the header:
```
Authorization: Bearer <your_jwt_token>
```

---

## 🗂️ Project Structure

```
rental-saas-api/
├── app/
│   ├── Http/
│   │   ├── Controllers/     # API Controllers
│   │   └── Middleware/      # Auth & Permission middleware
│   └── Models/              # Eloquent models
├── bootstrap/
│   └── app.php              # Middleware alias registration
├── database/
│   ├── migrations/          # All DB migrations
│   └── seeders/             # Seed data (credentials, roles, permissions)
├── routes/
│   └── api.php              # All API routes
└── .env                     # Environment configuration
```

---

## 🔧 Useful Artisan Commands

```bash
# Re-run all migrations fresh + reseed
php artisan migrate:fresh --seed

# List all registered routes
php artisan route:list

# Clear all caches
php artisan optimize:clear

# Run the server (XAMPP PHP)
D:\xampp\php\php.exe artisan serve --host=127.0.0.1 --port=8000
```

---

## 🗄️ Database Configuration (Local / XAMPP)

| Setting  | Value         |
|----------|---------------|
| Host     | `127.0.0.1`   |
| Port     | `3306`        |
| Database | `rental_saas` |
| Username | `root`        |
| Password | _(empty)_     |
