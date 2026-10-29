# ScoopsHub — Ice Cream Parlour Management REST API

ScoopsHub is a RESTful API backend for managing an ice cream parlour's inventory and customer records. Built with **PHP 8.3**, **Laravel 13**, **MySQL 8.0**, and **Laravel Sanctum** token-based authentication for the **Delivergate Backend Engineer Intern Practical Exam**.

---

## Deliverables & Documentation Links

- **Entity Relationship (ER) Diagram:**
  - [docs/er-diagram.md](docs/er-diagram.md) (Interactive Mermaid source for GitHub Markdown preview)
  - [docs/er-diagram.png](docs/er-diagram.png) (Rendered visual diagram image)
- **Postman Collection (v2.1):**
  - [docs/ScoopsHub.postman_collection.json](docs/ScoopsHub.postman_collection.json) (31 requests covering Auth, Items, Customers, RBAC restrictions, and validation)
- **Submission Summary:** [docs/ScoopsHub-Submission-Document.md](docs/ScoopsHub-Submission-Document.md)

---

## Features

- **Authentication:** Laravel Sanctum bearer tokens. Login returns a token; subsequent requests include it as `Authorization: Bearer <token>`.
- **Role-based access control:** Three roles — `owner`, `manager`, `cashier` — enforced by a custom middleware alias (`role`). Unauthorized role → `403 Forbidden`.
- **Items CRUD:** Create, read, update (PUT/PATCH), delete, and search by name. Prices are stored as `DECIMAL(10,2)`; stock is a non-negative integer.
- **Customers CRUD:** Create, read, update (PUT/PATCH), delete, and search by name or email. Email is optional but must be a valid format and unique when supplied.
- **Error responses:** All error and validation responses return JSON, including when no `Accept: application/json` header is sent on `/api/*` routes.

---

## Tech Stack & System Requirements

- **PHP:** `^8.3` (`pdo_mysql`, `pdo_sqlite`, `openssl`, `mbstring`, and `fileinfo` are required)
- **Framework:** Laravel 13.x (with Laravel Sanctum 4.x)
- **Database:** MySQL 8.0+ (development) / SQLite `:memory:` (testing)
- **Package Manager:** Composer 2.x+
- **Development Environment:** Compatible with Laragon, Valet, Sail, or standard CLI on Windows / macOS / Linux

---

## Getting Started & Setup Guide

### 1. Clone and Install Dependencies

Clone the repository and enter the directory:

```bash
git clone https://github.com/JKWijerathne/ScoopsHub.git
cd ScoopsHub
```

Install Composer dependencies:

```bash
composer install
```

On Windows, run these commands from PowerShell or Command Prompt after PHP and Composer are available in your PATH.

> If `php` and `composer` are not on your PATH (common on Laragon), use their full paths:
> ```powershell
> C:\laragon\bin\php\php-8.3.36-Win32-vs16-x64\php.exe -r "readfile('https://getcomposer.org/installer');" | php
> C:\laragon\bin\composer\composer.bat install
> ```

### 2. Configure Environment Variables

Create your local `.env` configuration file from the template:

On Windows (Command Prompt / PowerShell):
```bat
copy .env.example .env
```

On macOS / Linux:
```bash
cp .env.example .env
```

Generate the unique application encryption key:

```bash
php artisan key:generate
```

Configure your local MySQL credentials in `.env` (these are safe local placeholders):

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=scoopshub
DB_USERNAME=root
DB_PASSWORD=
```

> **Security Note:** Never commit `.env` or credentials to version control. The repository `.gitignore` ensures `.env` and local database files are never tracked.

### 3. Create Database, Migrate, and Seed

1. In MySQL (via Laragon, MySQL Workbench, or CLI), create an empty database named `scoopshub`:
   ```sql
   CREATE DATABASE scoopshub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. Execute the migrations:
   ```bash
   php artisan migrate
   ```

3. Seed the initial development accounts:
   ```bash
   php artisan db:seed
   ```

### 4. Development Accounts

The seeder populates three accounts for local evaluation and testing:

| Role | Email | Password | Allowed Capabilities |
|---|---|---|---|
| **Owner** | `owner@scoopshub.test` | `Owner123!` | Full control: items CRUD, customers CRUD, and delete operations |
| **Manager** | `manager@scoopshub.test` | `Manager123!` | Create, view, and update items and customers; cannot delete |
| **Cashier** | `cashier@scoopshub.test` | `Cashier123!` | View and update items; create, view, and update customers; cannot create items or delete |

*(Note: These credentials are for local development/testing only.)*

### 5. Start the Application Server

Start Laravel's built-in development server:

```bash
php artisan serve
```

By default, the API will be available at:
`http://127.0.0.1:8000`

---

## Authentication & Bearer Tokens

All protected API endpoints require a Laravel Sanctum Bearer token passed in the `Authorization` header:

```http
Authorization: Bearer <plainTextToken>
Accept: application/json
Content-Type: application/json
```

### 1. Login Endpoint
- **URL:** `POST /api/auth/login`
- **Access:** Public

**Example Request:**
```json
{
  "email": "owner@scoopshub.test",
  "password": "Owner123!"
}
```

**Example Response (`200 OK`):**
```json
{
  "message": "Login successful",
  "token": "1|s0meRAnd0mSancTumTokEnStr1ng",
  "token_type": "Bearer",
  "user": {
    "id": 1,
    "name": "ScoopsHub Owner",
    "email": "owner@scoopshub.test",
    "role": "owner"
  }
}
```

### 2. Authenticated Profile Endpoint
- **URL:** `GET /api/auth/me`
- **Access:** Authenticated (`auth:sanctum`)

**Example Response (`200 OK`):**
```json
{
  "user": {
    "id": 1,
    "name": "ScoopsHub Owner",
    "email": "owner@scoopshub.test",
    "role": "owner"
  }
}
```

*(The legacy endpoint `GET /api/user` is also supported for backward compatibility.)*

### 3. Logout Endpoint
- **URL:** `POST /api/auth/logout`
- **Access:** Authenticated (`auth:sanctum`)

Revokes the current bearer token. Subsequent requests with the revoked token will receive `401 Unauthorized`.

**Example Response (`200 OK`):**
```json
{
  "message": "Logged out successfully"
}
```

---

## Role-Based Access Control (RBAC) Matrix

| Resource & Action | HTTP Method & Path | Owner | Manager | Cashier |
|---|---|:---:|:---:|:---:|
| **List Items** | `GET /api/items` | Allowed | Allowed | Allowed |
| **Search Items** | `GET /api/items?search=...` | Allowed | Allowed | Allowed |
| **View Item** | `GET /api/items/{id}` | Allowed | Allowed | Allowed |
| **Create Item** | `POST /api/items` | Allowed | Allowed | **403 Forbidden** |
| **Update Item (Full)** | `PUT /api/items/{id}` | Allowed | Allowed | Allowed |
| **Update Item (Partial)** | `PATCH /api/items/{id}` | Allowed | Allowed | Allowed |
| **Delete Item** | `DELETE /api/items/{id}` | Allowed | **403 Forbidden** | **403 Forbidden** |
| **List Customers** | `GET /api/customers` | Allowed | Allowed | Allowed |
| **Search Customers** | `GET /api/customers?search=...` | Allowed | Allowed | Allowed |
| **View Customer** | `GET /api/customers/{id}` | Allowed | Allowed | Allowed |
| **Create Customer** | `POST /api/customers` | Allowed | Allowed | Allowed |
| **Update Customer (Full)**| `PUT /api/customers/{id}` | Allowed | Allowed | Allowed |
| **Update Customer (Partial)**| `PATCH /api/customers/{id}` | Allowed | Allowed | Allowed |
| **Delete Customer** | `DELETE /api/customers/{id}` | Allowed | **403 Forbidden** | **403 Forbidden** |

---

## API Endpoints Reference

### 1. Items Endpoints (`/api/items`)

All item endpoints require authentication (`auth:sanctum`).

#### List & Search Items
- **`GET /api/items`**: Returns all items.
- **`GET /api/items?search={term}`**: Filters items whose name contains `{term}` (database-collation-dependent partial match).

**Example Response (`200 OK`):**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Belgian Dark Chocolate Gelato",
      "description": "Rich 70% cocoa artisanal gelato",
      "price": "450.00",
      "stock_quantity": 25,
      "created_at": "2026-10-29T08:00:00.000000Z",
      "updated_at": "2026-10-29T08:00:00.000000Z"
    }
  ]
}
```

#### Create Item
- **`POST /api/items`** (Permitted: Owner, Manager)

**Validation Rules:**
- `name`: Required, string, max 255.
- `description`: Optional, nullable string, max 1000.
- `price`: Required, numeric, min 0, max 99999999.99, up to 2 decimal places (`decimal:0,2`).
- `stock_quantity`: Required, integer, min 0.

**Example Request:**
```json
{
  "name": "Strawberry Sorbet",
  "description": "Dairy-free fresh strawberry sorbet",
  "price": 380.00,
  "stock_quantity": 15
}
```

**Example Response (`201 Created`):**
```json
{
  "message": "Item created successfully",
  "data": {
    "id": 2,
    "name": "Strawberry Sorbet",
    "description": "Dairy-free fresh strawberry sorbet",
    "price": "380.00",
    "stock_quantity": 15,
    "created_at": "2026-10-29T08:15:00.000000Z",
    "updated_at": "2026-10-29T08:15:00.000000Z"
  }
}
```

#### View Item
- **`GET /api/items/{id}`** (Permitted: All roles)
- Returns `200 OK` with item data, or `404 Not Found` if missing.

#### Update Item (Full `PUT` or Partial `PATCH`)
- **`PUT /api/items/{id}`**: Replaces all fields (all required fields must be supplied).
- **`PATCH /api/items/{id}`**: Updates only the provided fields (`sometimes` validation).

**Example Partial Update (`PATCH /api/items/2`):**
```json
{
  "stock_quantity": 50
}
```

**Example Response (`200 OK`):**
```json
{
  "message": "Item updated successfully",
  "data": {
    "id": 2,
    "name": "Strawberry Sorbet",
    "description": "Dairy-free fresh strawberry sorbet",
    "price": "380.00",
    "stock_quantity": 50,
    "created_at": "2026-10-29T08:15:00.000000Z",
    "updated_at": "2026-10-29T08:20:00.000000Z"
  }
}
```

#### Delete Item
- **`DELETE /api/items/{id}`** (Permitted: Owner only)
- Non-owners receive `403 Forbidden`.
- Returns `200 OK` on success or `404 Not Found` if missing.

**Example Response (`200 OK`):**
```json
{
  "message": "Item deleted successfully"
}
```

---

### 2. Customers Endpoints (`/api/customers`)

All customer endpoints require authentication (`auth:sanctum`).

#### List & Search Customers
- **`GET /api/customers`**: Returns all customer profiles.
- **`GET /api/customers?search={term}`**: Searches across customer `name` OR `email`.

**Example Response (`200 OK`):**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Amali Fernando",
      "email": "amali@example.com",
      "phone": "0719876543",
      "created_at": "2026-10-29T08:00:00.000000Z",
      "updated_at": "2026-10-29T08:00:00.000000Z"
    }
  ]
}
```

#### Create Customer
- **`POST /api/customers`** (Permitted: All roles)

**Validation Rules:**
- `name`: Required, string, max 255.
- `email`: Optional, nullable valid email format, max 255, unique across customers when provided.
- `phone`: Optional, nullable string, max 50.

**Example Request:**
```json
{
  "name": "Kamal Perera",
  "email": "kamal.perera@example.com",
  "phone": "0771234567"
}
```

**Example Response (`201 Created`):**
```json
{
  "message": "Customer created successfully",
  "data": {
    "id": 2,
    "name": "Kamal Perera",
    "email": "kamal.perera@example.com",
    "phone": "0771234567",
    "created_at": "2026-10-29T08:30:00.000000Z",
    "updated_at": "2026-10-29T08:30:00.000000Z"
  }
}
```

#### View Customer
- **`GET /api/customers/{id}`** (Permitted: All roles)
- Returns `200 OK` or `404 Not Found`.

#### Update Customer (`PUT` or `PATCH`)
- **`PUT /api/customers/{id}`**: Full replace.
- **`PATCH /api/customers/{id}`**: Partial update.
- Supports updating without false unique-email collisions on the customer's own existing email.

#### Delete Customer
- **`DELETE /api/customers/{id}`** (Permitted: Owner only)
- Non-owners receive `403 Forbidden`.

---

## HTTP Status Codes & Error Responses

The API returns consistent, descriptive JSON error messages:

| Status Code | Reason | Example Response Body |
|---|---|---|
| **`200 OK`** | Request succeeded | `{"message": "Item updated successfully", "data": {...}}` |
| **`201 Created`** | Resource successfully created | `{"message": "Item created successfully", "data": {...}}` |
| **`401 Unauthorized`** | Missing, invalid, or revoked token (automatic token expiry is not configured) | `{"message": "Unauthenticated."}` |
| **`403 Forbidden`** | Authenticated user lacks role permissions | `{"message": "Forbidden. You do not have permission to access this resource."}` |
| **`404 Not Found`** | Resource record does not exist | `{"message": "Item not found"}` |
| **`422 Unprocessable Content`** | Request body failed validation rules | *See example below* |

**Example Validation Error (`422 Unprocessable Content`):**
```json
{
  "message": "The name field is required. (and 2 more errors)",
  "errors": {
    "name": [
      "The name field is required."
    ],
    "price": [
      "The price field must be at least 0."
    ],
    "stock_quantity": [
      "The stock quantity field must be an integer."
    ]
  }
}
```

---

## Running Automated Tests

Tests run against an isolated **in-memory SQLite database** (`:memory:`) configured in `phpunit.xml`. The MySQL development database is not touched.

To execute the complete test suite:

```bash
php artisan test
```

On Windows with Laragon if `php` is not in your PATH:
```powershell
C:\laragon\bin\php\php-8.3.36-Win32-vs16-x64\php.exe artisan test
```

To run individual files:

```bash
php artisan test tests/Feature/ItemApiTest.php
php artisan test tests/Feature/CustomerApiTest.php
php artisan test tests/Feature/Auth/LoginTest.php
php artisan test tests/Feature/Auth/AuthenticationTest.php
php artisan test tests/Feature/Auth/AuthorizationTest.php
```

### Test results (last run)
- **Tests:** 80 passed
- **Assertions:** 411 passed
- **Failures:** 0

---

## Postman Collection Import & Verification

A ready-to-import Postman collection is provided in `docs/ScoopsHub.postman_collection.json`.

### How to use:
1. Open **Postman** and click **Import**.
2. Select `docs/ScoopsHub.postman_collection.json`.
3. In collection settings, confirm `base_url` is set to `http://127.0.0.1:8000`.
4. Run **1. Login as Owner**, **2. Login as Manager**, and **3. Login as Cashier**. Each test script saves its token in a separate collection variable.
5. Run the **Items** and **Customers** folders in order. Create requests save their generated IDs; customer creation uses a unique email for repeatable runs.
6. Explicit cashier item-creation and manager/cashier deletion requests expect `403 Forbidden`; owner deletion requests expect success.
7. **Logout** is the final collection request so it does not revoke a token before later protected requests use it.

The collection was structurally inspected but not executed in Postman/Newman during this audit because neither runner is installed.

---

## Project Structure

```text
ScoopsHub/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/
│   │   │       ├── AuthController.php      # Login, logout, me endpoints
│   │   │       ├── CustomerController.php  # Customer CRUD and search
│   │   │       └── ItemController.php      # Item CRUD and search
│   │   └── Middleware/
│   │       └── EnsureUserHasRole.php       # RBAC role verification middleware
│   └── Models/
│       ├── Customer.php                    # Customer model
│       ├── Item.php                        # Item model with casts
│       └── User.php                        # Authenticatable user with HasApiTokens & roles
├── bootstrap/
│   └── app.php                             # Middleware aliases and JSON exception handling
├── database/
│   ├── factories/                          # User, Item, Customer factories
│   ├── migrations/                         # Migration schema files
│   └── seeders/
│       ├── DatabaseSeeder.php              # Main database seeder
│       └── UserSeeder.php                  # Owner, manager, cashier seeder
├── docs/
│   ├── ScoopsHub-Submission-Document.md    # Submission summary
│   ├── er-diagram.md                       # Mermaid ER diagram and schema specs
│   ├── er-diagram.png                      # Visual schema diagram
│   └── ScoopsHub.postman_collection.json   # Postman v2.1 collection
├── routes/
│   └── api.php                             # API routes, auth groups, and RBAC probes
├── tests/
│   ├── Feature/
│   │   ├── Auth/                           # Login, auth, and role verification tests
│   │   ├── CustomerApiTest.php             # Customer CRUD, validation & permission tests
│   │   └── ItemApiTest.php                 # Item CRUD, validation & permission tests
│   └── TestCase.php                        # Base test case
├── .env.example                            # Safe development configuration template
├── phpunit.xml                             # PHPUnit test configuration (SQLite in-memory)
└── README.md                               
```
