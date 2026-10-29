# ScoopsHub

ScoopsHub is a REST API for managing an ice cream parlour's items and customers. It was developed using Laravel, MySQL and Laravel Sanctum for the Delivergate Backend Engineer Intern Practical Exam.

## Features

- Token-based authentication with Laravel Sanctum
- Role-based access control for owners, managers and cashiers
- Item management, including stock quantities and prices
- Customer management, with optional email and phone details
- Search items by name and customers by name or email
- Input validation and consistent JSON responses
- Feature tests for authentication, permissions and CRUD operations

## Tech Stack

- **Backend:** PHP 8.3+, Laravel 13
- **Database:** MySQL 8.0+
- **Authentication:** Laravel Sanctum
- **Testing:** PHPUnit / Laravel testing tools
- **Additional driver:** SQLite for automated tests

## Getting Started

### Prerequisites

Make sure you have the following installed:

- PHP 8.3 or later, with `pdo_mysql` and `pdo_sqlite` enabled
- Composer 2.x or later
- MySQL 8.0 or later

### 1. Get the project ready

Clone the repository and open its directory:

```bash
git clone <repository-url>
cd ScoopsHub
```

Install the PHP dependencies:

```bash
composer install
```

### 2. Configure the environment

Copy `.env.example` to `.env`. On Windows, you can use:

```bat
copy .env.example .env
```

On macOS or Linux, use:

```bash
cp .env.example .env
```

Generate the application key:

```bash
php artisan key:generate
```

Update the database settings in `.env` to match your local MySQL configuration:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=scoopshub
DB_USERNAME=root
DB_PASSWORD=
```

Create a MySQL database named `scoopshub` before running the migrations. If your MySQL installation uses a password, set `DB_PASSWORD` accordingly.

**Note:** These are example local-development settings. Use your own database credentials and never commit your `.env` file.

### 3. Run migrations and seed the database

Create the tables:

```bash
php artisan migrate
```

Create the development accounts:

```bash
php artisan db:seed
```

The seeder creates the following accounts for local testing:

| Role | Email | Password |
|---|---|---|
| Owner | `owner@scoopshub.test` | `Owner123!` |
| Manager | `manager@scoopshub.test` | `Manager123!` |
| Cashier | `cashier@scoopshub.test` | `Cashier123!` |

These credentials are for development only. Do not use them in a production environment.

### 4. Start the server

Run:

```bash
php artisan serve
```

By default, the API is available at `http://127.0.0.1:8000`.

Keep the terminal running while you test the endpoints. If PHP is not available on your system PATH, run Artisan using the full path to your PHP executable.

## Authentication

Login is required before accessing protected API endpoints. Send the login request with JSON data.

### Login

`POST /api/auth/login`

Request:

```json
{
  "email": "owner@scoopshub.test",
  "password": "Owner123!"
}
```

A successful login returns a Sanctum bearer token and the authenticated user's details.

Example response:

```json
{
  "message": "Login successful",
  "token": "1|your-token",
  "token_type": "Bearer",
  "user": {
    "id": 1,
    "name": "ScoopsHub Owner",
    "email": "owner@scoopshub.test",
    "role": "owner"
  }
}
```

Use the actual token returned by your login request. The token shown above is only an example.

### Authenticated profile

`GET /api/auth/me`

Returns the authenticated user's profile. The legacy `GET /api/user` endpoint is also available.

### Logout

`POST /api/auth/logout`

Revokes the current access token.

### Headers for protected endpoints

Include these headers in requests that require authentication:

```http
Authorization: Bearer <your-token>
Accept: application/json
Content-Type: application/json
```

For requests without a JSON body, `Content-Type` is generally unnecessary.

## Roles and Permissions

The API uses role-based middleware to restrict operations.

| Operation | Owner | Manager | Cashier |
|---|:---:|:---:|:---:|
| View items | Yes | Yes | Yes |
| Create items | Yes | Yes | No |
| Update items | Yes | Yes | Yes |
| Delete items | Yes | No | No |
| View customers | Yes | Yes | Yes |
| Create customers | Yes | Yes | Yes |
| Update customers | Yes | Yes | Yes |
| Delete customers | Yes | No | No |

Requests made by authenticated users without the required permission return `403 Forbidden`.

## API Endpoints

All item and customer endpoints below require authentication.

### Items

| Method | Endpoint | Description | Permission |
|---|---|---|---|
| GET | `/api/items` | List items | All roles |
| GET | `/api/items/{id}` | View an item | All roles |
| POST | `/api/items` | Create an item | Owner, Manager |
| PUT | `/api/items/{id}` | Replace an item's details | All roles |
| PATCH | `/api/items/{id}` | Update selected fields | All roles |
| DELETE | `/api/items/{id}` | Delete an item | Owner |

To search by item name, add the `search` query parameter:

```http
GET /api/items?search=chocolate
```

Example create request:

```json
{
  "name": "Strawberry Sorbet",
  "description": "Dairy-free strawberry sorbet",
  "price": 380.00,
  "stock_quantity": 15
}
```

Item validation includes a required name, a non-negative price with up to two decimal places, and a non-negative integer stock quantity. The description is optional.

### Customers

| Method | Endpoint | Description | Permission |
|---|---|---|---|
| GET | `/api/customers` | List customers | All roles |
| GET | `/api/customers/{id}` | View a customer | All roles |
| POST | `/api/customers` | Create a customer | All roles |
| PUT | `/api/customers/{id}` | Replace customer details | All roles |
| PATCH | `/api/customers/{id}` | Update selected fields | All roles |
| DELETE | `/api/customers/{id}` | Delete a customer | Owner |

Customer search supports a partial name or email match:

```http
GET /api/customers?search=amali
```

Example create request:

```json
{
  "name": "Amali Fernando",
  "email": "amali@example.com",
  "phone": "0719876543"
}
```

Only the customer's name is required. Email and phone are optional; when supplied, the email must be valid and unique.

### Update requests

Use `PUT` to submit all required fields for an update, or `PATCH` to change only selected fields.

For example, a partial item update can contain:

```json
{
  "stock_quantity": 50
}
```

### Response status codes

The API uses standard HTTP status codes:

| Status | Meaning |
|---|---|
| `200 OK` | Request completed successfully |
| `201 Created` | Resource created |
| `401 Unauthorized` | Authentication is missing or invalid |
| `403 Forbidden` | User lacks the required permission |
| `404 Not Found` | Requested resource does not exist |
| `422 Unprocessable Content` | Request validation failed |

Validation failures include an `errors` object describing the invalid fields.

## Running Tests

Run the full test suite with:

```bash
php artisan test
```

To run individual test suites:

```bash
php artisan test tests/Feature/ItemApiTest.php
php artisan test tests/Feature/CustomerApiTest.php
```

Authentication tests are located under `tests/Feature/Auth`.

The test environment is configured to use an in-memory SQLite database, keeping automated tests separate from the MySQL development database.

## Project Structure

```text
app/
  Http/
    Controllers/
      Api/
    Middleware/
  Models/
database/
  factories/
  migrations/
  seeders/
routes/
  api.php
tests/
  Feature/
```

- `routes/api.php` defines the API routes.
- `app/Http/Controllers/Api/` contains the API controllers.
- `app/Http/Middleware/` contains role-checking middleware.
- `app/Models/` contains the Eloquent models.
- `database/migrations/` defines the database schema.
- `database/seeders/` contains development seed data.
- `tests/Feature/` contains API feature tests.
