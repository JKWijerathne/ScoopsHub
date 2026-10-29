# ScoopsHub — Entity Relationship (ER) Diagram

This document defines the database architecture and schema relationships for **ScoopsHub**, developed with Laravel 13, MySQL, and Laravel Sanctum.

---

## 1. Visual ER Diagram (Mermaid)

The diagram below uses GitHub Flavored Markdown Mermaid ER syntax. GitHub and compatible Markdown previewers render this automatically.

```mermaid
erDiagram
    users {
        bigint id PK "auto-increment"
        string name "User full name"
        string email UK "unique"
        timestamp email_verified_at "nullable"
        string password "hashed (bcrypt)"
        string role "default: 'cashier' ('owner'|'manager'|'cashier')"
        string remember_token "nullable (100)"
        timestamp created_at "nullable"
        timestamp updated_at "nullable"
    }

    items {
        bigint id PK "auto-increment"
        string name "Item/flavour name (max 255)"
        text description "nullable (max 1000)"
        decimal price "10,2 - non-negative"
        int unsigned stock_quantity "default: 0 - non-negative"
        timestamp created_at "nullable"
        timestamp updated_at "nullable"
    }

    customers {
        bigint id PK "auto-increment"
        string name "Customer full name (max 255)"
        string email UK "nullable, unique when present"
        string phone "nullable (max 50)"
        timestamp created_at "nullable"
        timestamp updated_at "nullable"
    }

    personal_access_tokens {
        bigint id PK "auto-increment"
        string tokenable_type "Polymorphic class ('App\\Models\\User')"
        bigint tokenable_id "Polymorphic foreign key (users.id)"
        text name "Token identifier name ('scoopshub-api')"
        string token UK "SHA-256 hash (64 chars)"
        text abilities "nullable, JSON permissions array"
        timestamp last_used_at "nullable"
        timestamp expires_at "nullable (indexed)"
        timestamp created_at "nullable"
        timestamp updated_at "nullable"
    }

    users ||--o{ personal_access_tokens : "owns (polymorphic morphs relation)"
```

---

## 2. Table Specifications & Constraints

### 2.1 `users` Table
Stores authenticated user accounts for parlour staff with role-based permissions.

| Column | Type | Nullable | Key / Constraint | Description |
|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | No | **PK** (auto_increment) | Primary surrogate key |
| `name` | `VARCHAR(255)` | No | — | User's full display name |
| `email` | `VARCHAR(255)` | No | **UNIQUE** (`users_email_unique`) | Login identifier |
| `email_verified_at` | `TIMESTAMP` | Yes | — | Email verification timestamp |
| `password` | `VARCHAR(255)` | No | — | Bcrypt-hashed password |
| `role` | `VARCHAR(255)` | No | Default: `'cashier'` | Role for authorization: `owner`, `manager`, `cashier` |
| `remember_token` | `VARCHAR(100)` | Yes | — | Web session remember token |
| `created_at` | `TIMESTAMP` | Yes | — | Record creation timestamp |
| `updated_at` | `TIMESTAMP` | Yes | — | Record last update timestamp |

### 2.2 `items` Table
Stores parlour stock items (ice cream flavours, cones, toppings, tubs).

| Column | Type | Nullable | Key / Constraint | Description |
|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | No | **PK** (auto_increment) | Primary surrogate key |
| `name` | `VARCHAR(255)` | No | — | Item name (e.g., "Vanilla Delight") |
| `description` | `TEXT` | Yes | — | Detailed item description |
| `price` | `DECIMAL(10,2)` | No | — | Selling price (non-negative) |
| `stock_quantity` | `INT UNSIGNED` | No | Default: `0` | Available stock count (non-negative) |
| `created_at` | `TIMESTAMP` | Yes | — | Record creation timestamp |
| `updated_at` | `TIMESTAMP` | Yes | — | Record last update timestamp |

### 2.3 `customers` Table
Stores customer contact records for loyalty and order tracking.

| Column | Type | Nullable | Key / Constraint | Description |
|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | No | **PK** (auto_increment) | Primary surrogate key |
| `name` | `VARCHAR(255)` | No | — | Customer full name |
| `email` | `VARCHAR(255)` | Yes | **UNIQUE** (`customers_email_unique`) | Contact email; nullable, unique when provided |
| `phone` | `VARCHAR(50)` | Yes | — | Contact telephone number |
| `created_at` | `TIMESTAMP` | Yes | — | Record creation timestamp |
| `updated_at` | `TIMESTAMP` | Yes | — | Record last update timestamp |

### 2.4 `personal_access_tokens` Table (Laravel Sanctum)
Manages API bearer authentication tokens.

| Column | Type | Nullable | Key / Constraint | Description |
|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | No | **PK** (auto_increment) | Primary surrogate key |
| `tokenable_type` | `VARCHAR(255)` | No | Index (`tokenable_type`, `tokenable_id`) | Fully qualified model class (`App\Models\User`) |
| `tokenable_id` | `BIGINT UNSIGNED` | No | Index (`tokenable_type`, `tokenable_id`) | ID of the authenticatable model (`users.id`) |
| `name` | `TEXT` | No | — | Identifier name given to token |
| `token` | `VARCHAR(64)` | No | **UNIQUE** (`personal_access_tokens_token_unique`) | 64-character SHA-256 hashed token string |
| `abilities` | `TEXT` | Yes | — | JSON array of token abilities/scopes |
| `last_used_at` | `TIMESTAMP` | Yes | — | Timestamp of last API invocation |
| `expires_at` | `TIMESTAMP` | Yes | Index (`personal_access_tokens_expires_at_index`) | Token expiration timestamp |
| `created_at` | `TIMESTAMP` | Yes | — | Token issuance timestamp |
| `updated_at` | `TIMESTAMP` | Yes | — | Token record update timestamp |

---

## 3. Relationships and Constraints Note

1. **Polymorphic Token Ownership (`users` -> `personal_access_tokens`)**:
   - `personal_access_tokens` uses Laravel Sanctum's polymorphic relation (`morphs('tokenable')`).
   - Each token record links to a `User` via `tokenable_type = 'App\Models\User'` and `tokenable_id = users.id`.
   - In MySQL/SQLite, this is enforced at the application/ORM level rather than with an RDBMS hard foreign key to allow flexibility across different authenticatable models.

2. **Standalone Domain Entities (`items`, `customers`)**:
   - `items` and `customers` are independent core master tables representing inventory and customer profiles respectively.
   - Neither table requires a foreign key constraint to `users` or to each other in this scope.

3. **Database Constraints vs. Application Validation**:
   - Database enforces: `NOT NULL` on required fields, `UNIQUE` on `users.email` and `customers.email` (allowing nulls in MySQL/SQLite), unsigned integers for `items.stock_quantity`, and decimal precision `(10,2)` for `items.price`.
   - Application layer enforces: Role-based authorization rules, format validation (regex/email), decimal range checks (`min:0`, `max:99999999.99`), and partial update semantics (`sometimes`).

