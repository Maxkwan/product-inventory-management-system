# Product Inventory Management API

A REST API built with Laravel 11 for managing product categories, suppliers, products, and stock levels.

## Features

- Category CRUD with duplicate-name validation
- Product CRUD with SKU, price, category, and active-status validation
- Supplier CRUD and many-to-many product-supplier relationships
- Laravel Sanctum token authentication
- Search, filter by category, price range, and stock level, sort, and paginate products
- Low-stock reporting through `?low_stock=1`
- Eloquent low-stock scope and computed `is_low_stock` accessor
- Product soft deletion that preserves product records and supplier relationships
- Protection against negative inventory
- Feature tests and sample seed data
- Interactive Swagger/OpenAPI documentation
- Five-minute caching for product, category, and supplier lists with automatic invalidation after writes
- Named rate limits for API traffic and authentication attempts
- Docker setup with SQLite, migrations, seed data, and Swagger generation

## Choose a setup method

Use one of these methods:

1. **Docker (simplest):** requires Docker Desktop only. PHP, Composer, SQLite, migrations, seed data, and Swagger generation run inside the container.
2. **Local PHP:** requires PHP, Composer, and the PHP extensions listed below.

Node.js and `npm install` are not required to run or test this REST API. They are only needed if you want to modify the optional Vite frontend assets.

## Option 1: Docker setup

### Prerequisites

- Git
- Docker Desktop with Docker Compose
- Ensure Docker Desktop is running before entering the commands

Clone and start the project:

```powershell
git clone https://github.com/Maxkwan/product-inventory-management-system.git
Set-Location product-inventory-management-system
docker compose up --build
```

The first build may take several minutes. The container installs Composer dependencies, creates an SQLite database, generates an application key, runs migrations and seeders, generates Swagger documentation, and starts the API.

When this message appears, the server is ready:

```text
Server running on [http://0.0.0.0:8000]
```

Open:

- API health check: `http://127.0.0.1:8000/api/health`
- Swagger UI: `http://127.0.0.1:8000/api/documentation`

Stop the foreground container with `Ctrl+C`. To stop and remove it from another terminal:

```powershell
docker compose down
```

The current Docker setup stores SQLite data inside the container. Removing and rebuilding the container creates fresh seeded data.

## Option 2: Local PHP setup

### Prerequisites

- PHP 8.2 or newer
- PHP extensions: `ctype`, `curl`, `dom`, `fileinfo`, `mbstring`, `openssl`, `pdo_sqlite` (for SQLite), `tokenizer`, and `xml`
- Composer 2
- Git

On Windows, enable extensions in the `php.ini` reported by `php --ini`. At minimum, uncomment:

```ini
extension=fileinfo
extension=pdo_sqlite
extension=sqlite3
```

Verify PHP and the important extensions:

```powershell
php --version
composer --version
php --ini
php -m | Select-String -Pattern "fileinfo|pdo_sqlite|sqlite3"
```

### Installation

Clone the repository and enter its directory:

```powershell
git clone https://github.com/Maxkwan/product-inventory-management-system.git
Set-Location product-inventory-management-system
```

Install and initialize the application:

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
New-Item database/database.sqlite -ItemType File -Force
php artisan migrate --seed
php artisan l5-swagger:generate
php artisan serve
```

Keep this terminal running. Open `http://127.0.0.1:8000/api/health` and confirm that it returns:

```json
{"status":"ok"}
```

Swagger UI is available at `http://127.0.0.1:8000/api/documentation`.

## Using Swagger and authentication

1. Open `http://127.0.0.1:8000/api/documentation`.
2. Expand `POST /api/register` and select **Try it out**.
3. Submit a name, unique email, password, matching `password_confirmation`, and optional `device_name`.
4. Copy the returned token value.
5. Select **Authorize** at the top of Swagger and paste only the token, for example `1|your-token-here`.
6. You can now call the protected product, category, supplier, and user endpoints.

You can use `POST /api/login` instead when the user already exists. All endpoints except health, registration, login, and Swagger documentation require the Sanctum token.

## Caching and rate limiting

Product, category, and supplier list responses are cached for five minutes. Query parameters are part of the cache key, and create, update, or delete operations invalidate inventory list caches automatically. The cache store is controlled by `CACHE_STORE` and defaults to Laravel's database cache.

The API permits 60 requests per minute per authenticated user or IP address. Registration and login have an additional limit of 10 attempts per minute per email/IP combination. Exceeded limits return a JSON `429 Too Many Requests` response with a `Retry-After` header.

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
| DELETE | `/api/products/{id}` | Soft-delete a product |

Product list query parameters are:

- `search`: match a product name or SKU
- `category_id`: filter by category
- `min_price` and `max_price`: filter by an inclusive price range
- `min_stock` and `max_stock`: filter by an inclusive quantity range
- `low_stock`: use `true` or `1` to return products where `quantity <= reorder_level`
- `is_active`: use `true` or `false` to filter by active status
- `sort`: `name`, `price`, `quantity`, or `created_at`
- `direction`: `asc` or `desc`
- `per_page`: results per page, from 1 to 100

For example, `/api/products?category_id=1&min_price=10&max_price=100&min_stock=1&max_stock=50&per_page=15` combines category, price, stock, and pagination filters.

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

Tests use an isolated in-memory SQLite database and do not alter the local development database:

```powershell
php artisan test
```

Optional code-style check:

```powershell
vendor\bin\pint --test
```

## Resetting local sample data

This command deletes local database records, reruns all migrations, and inserts fresh sample data:

```powershell
php artisan migrate:fresh --seed
```

Only run it when losing the current local data is acceptable.

## Troubleshooting

### `No application encryption key has been specified`

Confirm that `.env` exists, then generate the key and restart the server:

```powershell
php artisan key:generate
php artisan config:clear
php artisan serve
```

### Docker Desktop is running but `docker` is not recognized

Close and reopen PowerShell after installing or starting Docker Desktop. If the command is still unavailable on a per-user Windows installation, run it using the installed CLI path:

```powershell
& "$env:LOCALAPPDATA\Programs\DockerDesktop\resources\bin\docker.exe" compose up --build
```

### `could not find driver`

The PHP CLI is missing SQLite support. Run `php --ini`, open the reported `php.ini`, enable these extensions, and restart the terminal:

```ini
extension=pdo_sqlite
extension=sqlite3
```

Confirm with:

```powershell
php -m | Select-String -Pattern "pdo_sqlite|sqlite3"
```

### Composer reports `ext-fileinfo` or `Class "finfo" not found`

Enable `extension=fileinfo` in the `php.ini` reported by `php --ini`, restart the terminal, and rerun `composer install`.

### Port 8000 is already in use

Start Laravel on another port:

```powershell
php artisan serve --port=8001
```

Then use `http://127.0.0.1:8001`. For Swagger requests to use that port too, update the local server URL in `app/OpenApi/Documentation.php` and regenerate the specification.

### Swagger changes are not visible

Regenerate the OpenAPI JSON and refresh the browser:

```powershell
php artisan l5-swagger:generate
```

## Security note

Laravel 11 has reached the stage where Composer reports known framework security advisories. This repository keeps the required Laravel 11 version for the assignment. For a production system, review `composer audit` and upgrade to a currently supported Laravel release.
