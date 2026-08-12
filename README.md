# Product Inventory Management API

A REST API built with Laravel 11 for managing product categories, suppliers, products, and stock levels.

## Features

- Category CRUD with duplicate-name validation
- Product CRUD with SKU, price, category, and active-status validation
- Supplier CRUD and many-to-many product-supplier relationships
- Laravel Sanctum token authentication
- Search, filter, sort, and paginate products
- Low-stock reporting through `?low_stock=1`
- Eloquent low-stock scope and computed `is_low_stock` accessor
- Protection against negative inventory
- Feature tests and sample seed data

## Requirements

- PHP 8.2 or newer
- PHP extensions: `ctype`, `curl`, `dom`, `fileinfo`, `mbstring`, `openssl`, `pdo_sqlite` (for SQLite), `tokenizer`, and `xml`
- Composer 2

On Windows, enable extensions in the `php.ini` reported by `php --ini`. At minimum, uncomment:

```ini
extension=fileinfo
extension=pdo_sqlite
extension=sqlite3
```

## Setup

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
New-Item database/database.sqlite -ItemType File -Force
php artisan migrate --seed
php artisan serve
```

The API will be available at `http://127.0.0.1:8000/api`.

## Endpoints

| Method | Endpoint | Description |
| --- | --- | --- |
| GET | `/api/health` | Health check |
| POST | `/api/register` | Register and receive an API token |
| POST | `/api/login` | Log in and receive an API token |
| GET | `/api/user` | Show the authenticated user |
| POST | `/api/logout` | Revoke the current API token |
| GET | `/api/users` | Paginated user list |
| GET | `/api/users/{id}` | Show a user |
| GET | `/api/categories` | Paginated category list |
| POST | `/api/categories` | Create a category |
| GET | `/api/categories/{id}` | Show a category |
| PUT/PATCH | `/api/categories/{id}` | Update a category |
| DELETE | `/api/categories/{id}` | Delete an empty category |
| GET | `/api/suppliers` | Paginated supplier list |
| POST | `/api/suppliers` | Create a supplier |
| GET | `/api/suppliers/{id}` | Show a supplier |
| PUT/PATCH | `/api/suppliers/{id}` | Update a supplier |
| DELETE | `/api/suppliers/{id}` | Delete a supplier |
| GET | `/api/products` | Paginated and filterable product list |
| POST | `/api/products` | Create a product |
| GET | `/api/products/{id}` | Show a product |
| PUT/PATCH | `/api/products/{id}` | Update product details |
| DELETE | `/api/products/{id}` | Delete a product |

Product list query parameters are `search`, `category_id`, `low_stock`, `is_active`, `sort`, `direction`, and `per_page`.

Except for health, registration, and login, API endpoints require a Sanctum token in the `Authorization: Bearer <token>` header.

## Example requests

Register:

```http
POST /api/register
Content-Type: application/json

{
  "name": "Jane Doe",
  "email": "jane@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "device_name": "postman"
}
```

Use the returned token on protected requests:

```http
Authorization: Bearer 1|your-plain-text-token
```

Create a category:

```http
POST /api/categories
Content-Type: application/json

{"name":"Electronics","description":"Electronic products"}
```

Create a product:

```http
POST /api/products
Content-Type: application/json

{
  "category_id": 1,
  "name": "Wireless Mouse",
  "sku": "WM-001",
  "price": 49.90,
  "quantity": 25,
  "reorder_level": 5,
  "supplier_ids": [1, 2]
}
```

When updating a product, omit `supplier_ids` to keep its existing suppliers. Send an empty array to detach all suppliers, or send an array of IDs to replace its supplier relationships.

Update a product's stock quantity:

```http
PATCH /api/products/1
Content-Type: application/json

{"quantity":23}
```

## Tests

```powershell
php artisan test
```

## Security note

Laravel 11 has reached the stage where Composer reports known framework security advisories. This repository keeps the required Laravel 11 version for the assignment. For a production system, review `composer audit` and upgrade to a currently supported Laravel release.
