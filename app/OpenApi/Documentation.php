<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Product Inventory Management API',
    description: 'Laravel 11 REST API for products, categories, suppliers, and inventory.'
)]
#[OA\Server(url: 'http://127.0.0.1:8000', description: 'Local development server')]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Sanctum token'
)]
class Documentation
{
    #[OA\Get(path: '/api/health', summary: 'Check API health', tags: ['Health'], responses: [new OA\Response(response: 200, description: 'API is healthy')])]
    public function health(): void {}

    #[OA\Post(
        path: '/api/register',
        summary: 'Register and receive a Sanctum token',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['name', 'email', 'password', 'password_confirmation'],
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'Jane Doe'),
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'jane@example.com'),
                new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password123'),
                new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'password123'),
                new OA\Property(property: 'device_name', type: 'string', example: 'swagger-ui'),
            ]
        )),
        responses: [new OA\Response(response: 201, description: 'Registered'), new OA\Response(response: 422, description: 'Validation error')]
    )]
    public function register(): void {}

    #[OA\Post(
        path: '/api/login',
        summary: 'Log in and receive a Sanctum token',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['email', 'password'],
            properties: [
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'jane@example.com'),
                new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password123'),
                new OA\Property(property: 'device_name', type: 'string', example: 'swagger-ui'),
            ]
        )),
        responses: [new OA\Response(response: 200, description: 'Authenticated'), new OA\Response(response: 422, description: 'Invalid credentials')]
    )]
    public function login(): void {}

    #[OA\Get(path: '/api/user', summary: 'Show the authenticated user', tags: ['Authentication'], security: [['sanctum' => []]], responses: [new OA\Response(response: 200, description: 'Authenticated user'), new OA\Response(response: 401, description: 'Unauthenticated')])]
    public function currentUser(): void {}

    #[OA\Post(path: '/api/logout', summary: 'Revoke the current token', tags: ['Authentication'], security: [['sanctum' => []]], responses: [new OA\Response(response: 200, description: 'Logged out'), new OA\Response(response: 401, description: 'Unauthenticated')])]
    public function logout(): void {}

    #[OA\Get(
        path: '/api/products',
        summary: 'List, filter, sort, and paginate products',
        tags: ['Products'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'category_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'min_price', in: 'query', schema: new OA\Schema(type: 'number', format: 'float', minimum: 0)),
            new OA\Parameter(name: 'max_price', in: 'query', schema: new OA\Schema(type: 'number', format: 'float', minimum: 0)),
            new OA\Parameter(name: 'min_stock', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 0)),
            new OA\Parameter(name: 'max_stock', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 0)),
            new OA\Parameter(name: 'low_stock', in: 'query', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'is_active', in: 'query', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'sort', in: 'query', schema: new OA\Schema(type: 'string', enum: ['name', 'price', 'quantity', 'created_at'])),
            new OA\Parameter(name: 'direction', in: 'query', schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'])),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, default: 15)),
        ],
        responses: [new OA\Response(response: 200, description: 'Paginated products'), new OA\Response(response: 401, description: 'Unauthenticated'), new OA\Response(response: 422, description: 'Invalid filters')]
    )]
    public function listProducts(): void {}

    #[OA\Post(path: '/api/products', summary: 'Create a product', tags: ['Products'], security: [['sanctum' => []]], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ProductInput')), responses: [new OA\Response(response: 201, description: 'Product created'), new OA\Response(response: 422, description: 'Validation error')])]
    public function createProduct(): void {}

    #[OA\Get(path: '/api/products/{product}', summary: 'Show a product', tags: ['Products'], security: [['sanctum' => []]], parameters: [new OA\Parameter(name: 'product', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Product details'), new OA\Response(response: 404, description: 'Not found')])]
    public function showProduct(): void {}

    #[OA\Patch(path: '/api/products/{product}', summary: 'Update a product', tags: ['Products'], security: [['sanctum' => []]], parameters: [new OA\Parameter(name: 'product', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ProductInput')), responses: [new OA\Response(response: 200, description: 'Product updated'), new OA\Response(response: 422, description: 'Validation error')])]
    public function updateProduct(): void {}

    #[OA\Delete(path: '/api/products/{product}', summary: 'Soft-delete a product', tags: ['Products'], security: [['sanctum' => []]], parameters: [new OA\Parameter(name: 'product', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 204, description: 'Product deleted'), new OA\Response(response: 404, description: 'Not found')])]
    public function deleteProduct(): void {}

    #[OA\Get(path: '/api/categories', summary: 'List categories', tags: ['Categories'], security: [['sanctum' => []]], responses: [new OA\Response(response: 200, description: 'Paginated categories')])]
    public function listCategories(): void {}

    #[OA\Post(path: '/api/categories', summary: 'Create a category', tags: ['Categories'], security: [['sanctum' => []]], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/CategoryInput')), responses: [new OA\Response(response: 201, description: 'Category created'), new OA\Response(response: 422, description: 'Validation error')])]
    public function createCategory(): void {}

    #[OA\Get(path: '/api/categories/{category}', summary: 'Show a category', tags: ['Categories'], security: [['sanctum' => []]], parameters: [new OA\Parameter(name: 'category', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Category details'), new OA\Response(response: 404, description: 'Not found')])]
    public function showCategory(): void {}

    #[OA\Patch(path: '/api/categories/{category}', summary: 'Update a category', tags: ['Categories'], security: [['sanctum' => []]], parameters: [new OA\Parameter(name: 'category', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/CategoryInput')), responses: [new OA\Response(response: 200, description: 'Category updated'), new OA\Response(response: 422, description: 'Validation error')])]
    public function updateCategory(): void {}

    #[OA\Delete(path: '/api/categories/{category}', summary: 'Delete an empty category', tags: ['Categories'], security: [['sanctum' => []]], parameters: [new OA\Parameter(name: 'category', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 204, description: 'Category deleted'), new OA\Response(response: 409, description: 'Category contains products')])]
    public function deleteCategory(): void {}

    #[OA\Get(path: '/api/suppliers', summary: 'List suppliers', tags: ['Suppliers'], security: [['sanctum' => []]], responses: [new OA\Response(response: 200, description: 'Paginated suppliers')])]
    public function listSuppliers(): void {}

    #[OA\Post(path: '/api/suppliers', summary: 'Create a supplier', tags: ['Suppliers'], security: [['sanctum' => []]], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/SupplierInput')), responses: [new OA\Response(response: 201, description: 'Supplier created'), new OA\Response(response: 422, description: 'Validation error')])]
    public function createSupplier(): void {}

    #[OA\Get(path: '/api/suppliers/{supplier}', summary: 'Show a supplier', tags: ['Suppliers'], security: [['sanctum' => []]], parameters: [new OA\Parameter(name: 'supplier', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Supplier details'), new OA\Response(response: 404, description: 'Not found')])]
    public function showSupplier(): void {}

    #[OA\Patch(path: '/api/suppliers/{supplier}', summary: 'Update a supplier', tags: ['Suppliers'], security: [['sanctum' => []]], parameters: [new OA\Parameter(name: 'supplier', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/SupplierInput')), responses: [new OA\Response(response: 200, description: 'Supplier updated'), new OA\Response(response: 422, description: 'Validation error')])]
    public function updateSupplier(): void {}

    #[OA\Delete(path: '/api/suppliers/{supplier}', summary: 'Delete a supplier', tags: ['Suppliers'], security: [['sanctum' => []]], parameters: [new OA\Parameter(name: 'supplier', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 204, description: 'Supplier deleted'), new OA\Response(response: 404, description: 'Not found')])]
    public function deleteSupplier(): void {}

    #[OA\Get(path: '/api/users', summary: 'List users', tags: ['Users'], security: [['sanctum' => []]], responses: [new OA\Response(response: 200, description: 'Paginated users')])]
    public function listUsers(): void {}

    #[OA\Get(path: '/api/users/{user}', summary: 'Show a user', tags: ['Users'], security: [['sanctum' => []]], parameters: [new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'User details'), new OA\Response(response: 404, description: 'Not found')])]
    public function showUser(): void {}
}

#[OA\Schema(
    schema: 'ProductInput',
    required: ['category_id', 'name', 'sku', 'price'],
    properties: [
        new OA\Property(property: 'category_id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Wireless Mouse'),
        new OA\Property(property: 'sku', type: 'string', example: 'WM-001'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'price', type: 'number', format: 'float', minimum: 0, example: 49.90),
        new OA\Property(property: 'quantity', type: 'integer', minimum: 0, example: 25),
        new OA\Property(property: 'reorder_level', type: 'integer', minimum: 0, example: 5),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
        new OA\Property(property: 'supplier_ids', type: 'array', items: new OA\Items(type: 'integer'), example: [1, 2]),
    ],
    type: 'object'
)]
class ProductInput {}

#[OA\Schema(
    schema: 'CategoryInput',
    required: ['name'],
    properties: [new OA\Property(property: 'name', type: 'string', example: 'Electronics'), new OA\Property(property: 'description', type: 'string', nullable: true)],
    type: 'object'
)]
class CategoryInput {}

#[OA\Schema(
    schema: 'SupplierInput',
    required: ['name'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Acme Supplies'),
        new OA\Property(property: 'contact_name', type: 'string', nullable: true, example: 'Jane Doe'),
        new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true),
        new OA\Property(property: 'phone', type: 'string', nullable: true),
        new OA\Property(property: 'address', type: 'string', nullable: true),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
    ],
    type: 'object'
)]
class SupplierInput {}
