# ScoopsHub — Backend Submission Summary

## Project

ScoopsHub is a REST API for managing ice-cream parlour inventory and customer records. It provides staff sign-in, Sanctum bearer-token authentication, role-based access control, and validated CRUD endpoints for items and customers.

## Technology

- PHP `^8.3` (verified in this audit with PHP 8.5.12)
- Laravel 13 and Laravel Sanctum 4
- MySQL-compatible migrations; PHPUnit tests run on isolated in-memory SQLite
- Composer dependencies

## Repository and deliverables

- Repository remote: <https://github.com/JKWijerathne/ScoopsHub>
- **Visibility:** GitHub reported this repository as **private** at the time of this audit. The link is not publicly accessible; do not describe it as a public repository unless its owner changes visibility or grants the recipient access.
- Postman collection: [`docs/ScoopsHub.postman_collection.json`](./ScoopsHub.postman_collection.json)
- ER diagram image: [`docs/er-diagram.png`](./er-diagram.png)
- ER schema notes: [`docs/er-diagram.md`](./er-diagram.md)
- Setup and API reference: [`README.md`](../README.md)

The editable draw.io source was not present in the initial file inventory and was left untouched at the user's direction. The ER diagram files remain at their original tracked contents. **The existing ER deliverables still need a manual schema-accuracy pass:** the Markdown labels customer `phone` as `VARCHAR(50)` although the migration creates `VARCHAR(255)` (the API validator caps input at 50); it calls Sanctum's `tokenable_id` a foreign key although the migration creates only a polymorphic composite index; and it omits framework tables such as `sessions`, `password_reset_tokens`, `cache`, and queue tables. The PNG also predates this audit and was not changed.

## Authentication and permissions

`POST /api/auth/login` issues a Sanctum bearer token. Authenticated users can read their profile and revoke their current token. The `owner` role has full item and customer access, including deletion. `manager` can create, view, and update both resources but cannot delete. `cashier` can view and update items, create/view/update customers, and cannot create items or delete either resource.

## Verification performed

- **Automated tests:** 80 passed, 411 assertions (executed during this audit with PHP 8.5.12).
- **API routes:** `php artisan route:list --path=api --except-vendor` listed 19 API routes.
- **Composer:** manifest validation and locked platform checks passed. The Composer audit initially found a medium Symfony MIME advisory; the lockfile was updated to patched `symfony/mime` 7.4.22 to retain PHP 8.3 compatibility, and the subsequent audit reported no known advisories.
- **Postman:** collection JSON and request configuration were inspected and corrected for role-specific tokens, RBAC denial checks, generated IDs, and final logout ordering. It was not executed in Postman/Newman because neither runner is installed.
- **Database:** the configured MySQL server reports version 8.4.3. A uniquely named disposable schema was created, migrated, seeded, checked for the three expected development users and empty item/customer tables, and then dropped. The existing local database was not modified.
- **GitHub:** the configured remote matches the repository URL above, but GitHub reported it private. No commit, push, or visibility change was made.

## Setup

Follow the fresh-clone, environment, database, migration, seed, server, and test instructions in [`README.md`](../README.md). Seeded example users are local evaluation accounts only and must not be used in production.

## Before submission

Confirm with the recipient whether a private repository link is acceptable. If a public link is required, the repository owner must decide whether to change visibility; otherwise, arrange access with the recipient or use the submission method requested by the exam. Review the GitHub Desktop checklist and restore any intended locally deleted files before committing. No email, commit, push, or repository visibility change has been performed by this audit.

For production use, add an explicit login rate limit and token-expiration policy. The current Sanctum expiration is unset, and the login route has no explicit throttle middleware. Keep seeded evaluation credentials out of production.
