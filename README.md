# ScoopsHub

ScoopsHub is an ice cream parlor management system backend built with Laravel and MySQL for the Delivergate Backend Engineer Intern Practical Exam.

## Database Setup & Configuration

### Prerequisites
- PHP >= 8.3 with `pdo_mysql` enabled
- Composer >= 2.x
- MySQL server (e.g., via Laragon) running on `127.0.0.1:3306`

### Environment Configuration
1. Copy `.env.example` to `.env` if not already present:
   ```sh
   cp .env.example .env
   ```
2. Verify or update the MySQL database configuration in `.env`:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=scoopshub
   DB_USERNAME=root
   DB_PASSWORD=
   ```
3. Generate the application key if not already generated:
   ```sh
   php artisan key:generate
   ```

### Running Migrations
Run all database migrations to establish the schema:
```sh
php artisan migrate
```

### Starting Development Server
Start the local Laravel development server:
```sh
php artisan serve
```
By default, the API will be available at `http://127.0.0.1:8000`.

## API Authentication & Authorization (Phase 3)

### Overview
Authentication is implemented via **Laravel Sanctum** personal access tokens. Role-based authorization is enforced on the backend via the `EnsureUserHasRole` middleware (alias: `role`). Unauthenticated requests to API endpoints return HTTP `401 Unauthorized` as JSON.

### Endpoints

#### 1. Login
- **Method & URL:** `POST /api/auth/login`
- **Access:** Public
- **Headers:** `Content-Type: application/json`, `Accept: application/json`
- **Request Body:**
  ```json
  {
    "email": "owner@scoopshub.test",
    "password": "Owner123!"
  }
  ```
- **Responses:**
  - `200 OK` (Success):
    ```json
    {
      "message": "Login successful",
      "token": "1|abcdef123456...",
      "token_type": "Bearer",
      "user": {
        "id": 1,
        "name": "ScoopsHub Owner",
        "email": "owner@scoopshub.test",
        "role": "owner"
      }
    }
    ```
  - `401 Unauthorized` (Invalid credentials):
    ```json
    {
      "message": "Invalid credentials"
    }
    ```
  - `422 Unprocessable Entity` (Validation failure):
    ```json
    {
      "message": "The email field is required.",
      "errors": {
        "email": ["The email field is required."]
      }
    }
    ```

#### 2. Authenticated User Profile
- **Method & URL:** `GET /api/auth/me` (or `GET /api/user`)
- **Access:** Authenticated (Bearer Token required)
- **Header:** `Authorization: Bearer <token>`
- **Response `200 OK`:**
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

#### 3. Logout
- **Method & URL:** `POST /api/auth/logout`
- **Access:** Authenticated (Bearer Token required)
- **Header:** `Authorization: Bearer <token>`
- **Response `200 OK`:**
  ```json
  {
    "message": "Logged out successfully"
  }
  ```
  *(Revokes the current access token only, without invalidating tokens on other client devices.)*

### Roles & Permissions Matrix
The application defines three roles: `owner`, `manager`, and `cashier`. Role checks are applied via the `role:<roles>` middleware.

| Permission | Owner | Manager | Cashier |
|---|---|---|---|
| View items | Yes | Yes | Yes |
| Create items | Yes | Yes | No |
| Update items | Yes | Yes | Yes |
| Delete items | Yes | No | No |
| View customers | Yes | Yes | Yes |
| Create customers | Yes | Yes | Yes |
| Update customers | Yes | Yes | Yes |
| Delete customers | Yes | No | No |

- Authenticated users without the required role receive `403 Forbidden` (`{"message": "Forbidden. You do not have permission to access this resource."}`).
- Unauthenticated requests receive `401 Unauthorized` (`{"message": "Unauthenticated."}`).

### Running Automated Tests
Run the PHPUnit feature test suite covering login, token authentication, and role authorization:
```sh
php artisan test
```

### Known Limitations
- Item and customer CRUD operations belong to the next phase (Phase 4). Role authorization middleware is registered and ready to protect those controllers.
- Tokens currently have no automatic expiration configured (`config/sanctum.php` default); token lifetime can be customized if needed.

---

<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
