# AI-Powered Sales Forecasting API - Validation Documentation

## Overview
This document outlines all validation rules, middleware checks, and business logic validations implemented in the AI-Powered Sales Forecasting Laravel API.

## Table of Contents
1. [Authentication Validation](#authentication-validation)
2. [Product Validation](#product-validation)
3. [Sales Validation](#sales-validation)
4. [AI Operations Validation](#ai-operations-validation)
5. [Middleware Validation](#middleware-validation)
6. [Dashboard Validation](#dashboard-validation)
7. [Rate Limiting](#rate-limiting)
8. [Error Responses](#error-responses)

---

## Authentication Validation

### User Registration
**Location**: `AuthController@register`  
**Method**: `POST /api/auth/register`

**Validation Rules**:
```php
[
    'name' => 'required|string|max:255',
    'email' => 'required|string|email|max:255|unique:users',
    'password' => 'required|string|min:6|confirmed',
]
```

**Validation Logic**:
- Name: Required, string, maximum 255 characters
- Email: Required, valid email format, unique in users table, max 255 characters
- Password: Required, string, minimum 6 characters, must match password_confirmation

**Error Response** (422):
```json
{
    "name": ["The name field is required."],
    "email": ["The email has already been taken."],
    "password": ["The password must be at least 6 characters."]
}
```

### User Login
**Location**: `AuthController@login`  
**Method**: `POST /api/auth/login`

**Validation Rules**:
- Uses JWT authentication with email/password credentials
- No explicit validation rules - relies on JWT attempt

**Business Logic Validation**:
- Checks if credentials are valid via `JWTAuth::attempt()`
- Returns 401 if authentication fails

---

## Product Validation

### Create Product
**Location**: `ProductController@store` via `ProductStoreRequest`  
**Method**: `POST /api/products`

**Validation Rules**:
```php
[
    'name' => 'required|string|max:255',
    'category' => 'required|string|max:255',
]
```

**Business Logic Validation**:
- User must be authenticated (handled by `auth:api` middleware)
- Product is automatically assigned to authenticated user

### Update Product
**Location**: `ProductController@update` via `ProductUpdateRequest`  
**Method**: `PUT /api/products/{id}`

**Validation Rules**:
```php
[
    'name' => 'sometimes|required|string|max:255',
    'category' => 'sometimes|required|string|max:255',
]
```

**Business Logic Validation**:
- User must be authenticated
- Product must exist and belong to authenticated user
- Only provided fields are validated (using `sometimes`)

### Product Ownership Validation
**Location**: All ProductController methods

**Business Logic**:
```php
if (!$product || $product->user_id !== auth()->id()) {
    return response()->json(['message' => 'Product not found'], 404);
}
```

---

## Sales Validation

### Create Sale
**Location**: `SaleController@store` via `SaleStoreRequest`  
**Method**: `POST /api/sales`

**Validation Rules**:
```php
[
    'product_id' => 'required|exists:products,id',
    'region' => 'required|string|max:255',
    'date' => 'required|date',
    'quantity' => 'required|integer|min:0',
    'revenue' => 'required|numeric|min:0',
]
```

**Business Logic Validation**:
- User must be authenticated
- Product must exist and belong to authenticated user
- Quantity must be non-negative integer
- Revenue must be non-negative numeric value

### Update Sale
**Location**: `SaleController@update` via `SaleUpdateRequest`  
**Method**: `PUT /api/sales/{id}`

**Validation Rules**:
```php
[
    'product_id' => 'sometimes|required|exists:products,id',
    'region' => 'sometimes|required|string|max:255',
    'date' => 'sometimes|required|date',
    'quantity' => 'sometimes|required|integer|min:0',
    'revenue' => 'sometimes|required|numeric|min:0',
]
```

**Business Logic Validation**:
- User must be authenticated
- Sale must exist and its product must belong to authenticated user
- If product_id is updated, new product must also belong to user

### Sale Ownership Validation
**Location**: All SaleController methods

**Business Logic**:
```php
if (!$sale || $sale->product->user_id !== auth()->id()) {
    return response()->json(['message' => 'Sale not found'], 404);
}
```

---

## AI Operations Validation

### Request Forecast
**Location**: `AIController@forecast`  
**Method**: `POST /api/ai/forecast`

**Validation Rules**:
```php
[
    'product_id' => 'required|exists:products,id',
    'region' => 'nullable|string',
]
```

**Business Logic Validation**:
- User must be authenticated
- Product must exist and belong to authenticated user
- Rate limited to 60 requests per minute per user

### AI Status Endpoints
**Location**: `AIController@forecastStatus`, `insightsStatus`, `recommendationsStatus`  
**Methods**: `GET /api/ai/forecast/{id}/status`, etc.

**Business Logic Validation**:
- User must be authenticated
- Resource (forecast/insight/recommendation) must exist and belong to authenticated user

---

## Middleware Validation

### Authentication Middleware
**Middleware**: `auth:api` (JWT Authentication)

**Validation Logic**:
- Checks for valid JWT token in Authorization header
- Token format: `Bearer {token}`
- Validates token signature and expiration
- Sets authenticated user in request

**Error Response** (401):
```json
{
    "error": "Unauthorized"
}
```

### Role-Based Access Control
**Middleware**: `CheckRole` (not currently used in routes)

**Validation Logic**:
```php
public function handle(Request $request, Closure $next, string $role): Response
{
    if (!auth()->check()) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    if (auth()->user()->role !== $role) {
        return response()->json(['message' => 'Forbidden'], 403);
    }

    return $next($request);
}
```

**Usage Example**:
```php
Route::middleware('check_role:admin')->group(function () {
    // Admin-only routes
});
```

### Request Logging Middleware
**Middleware**: `LogRequests`

**Validation Logic**:
- Logs all API requests and responses
- No validation - purely for auditing
- Applied to AI routes for monitoring

**Logged Data**:
- User ID, HTTP method, URL, IP address, User-Agent
- Response status code and timestamp

### Rate Limiting
**Middleware**: `throttle:60,1`

**Validation Logic**:
- Applied to AI routes
- Limits to 60 requests per minute per user
- Uses Laravel's throttle middleware

**Error Response** (429):
```json
{
    "message": "Too Many Attempts. Please slow down."
}
```

---

## Dashboard Validation

### Moving Average Endpoint
**Location**: `DashboardController@movingAverage`  
**Method**: `GET /api/dashboard/moving-average`

**Validation Rules**:
```php
[
    'days' => 'nullable|integer|min:3|max:30'
]
```

**Business Logic Validation**:
- Days parameter defaults to 7 if not provided
- Must be between 3 and 30 days
- User must be authenticated

### Dashboard Data Access
**Location**: All DashboardController methods

**Business Logic Validation**:
- User must be authenticated
- All data filtered by user ownership
- Analytics calculated only on user's products/sales

---

## Model-Level Validation

### Mass Assignment Protection
All models use `$fillable` arrays to prevent mass assignment vulnerabilities:

**User Model**:
```php
protected $fillable = ['name', 'email', 'password', 'role'];
```

**Product Model**:
```php
protected $fillable = ['user_id', 'name', 'category'];
```

**Sale Model**:
```php
protected $fillable = ['product_id', 'region', 'date', 'quantity', 'revenue'];
```

**Forecast Model**:
```php
protected $fillable = ['product_id', 'region', 'date', 'predicted_revenue', 'model_version', 'status', 'input_data', 'result', 'started_at', 'completed_at', 'job_id'];
```

**Insight Model**:
```php
protected $fillable = ['user_id', 'type', 'title', 'description', 'status', 'input_data', 'result', 'model_version', 'started_at', 'completed_at', 'job_id'];
```

**Recommendation Model**:
```php
protected $fillable = ['user_id', 'product_id', 'message', 'priority', 'status', 'input_data', 'result', 'model_version', 'started_at', 'completed_at', 'job_id'];
```

### Data Type Casting
Models use `$casts` for automatic type conversion:

**User Model**:
```php
protected $casts = [
    'email_verified_at' => 'datetime',
    'password' => 'hashed',
];
```

**Sale Model**:
```php
protected $casts = [
    'date' => 'date',
    'quantity' => 'integer',
    'revenue' => 'decimal:2',
];
```

**AI Models** (Forecast, Insight, Recommendation):
```php
protected $casts = [
    'input_data' => 'array',
    'result' => 'array',
    'started_at' => 'datetime',
    'completed_at' => 'datetime',
];
```

---

## Error Responses

### Validation Error (422)
Standard Laravel validation error format:
```json
{
    "field_name": [
        "Error message 1",
        "Error message 2"
    ]
}
```

### Authentication Errors
- **401 Unauthorized**: Invalid or missing JWT token
- **403 Forbidden**: Insufficient permissions or resource ownership

### Not Found (404)
- Resource doesn't exist or doesn't belong to authenticated user

### Rate Limit Exceeded (429)
- Too many requests in time window

### Server Errors (500)
- Internal server errors, database issues, etc.

---

## Security Considerations

### Input Validation
- All user inputs are validated using Laravel's validation system
- SQL injection prevented by Eloquent ORM and prepared statements
- XSS protection via proper output encoding in responses

### Authorization
- JWT tokens required for protected routes
- Resource ownership validation on all CRUD operations
- Rate limiting on expensive AI operations

### Data Integrity
- Foreign key constraints in database
- Mass assignment protection via `$fillable`
- Type casting ensures data consistency

### Audit Trail
- Request logging middleware tracks all API usage
- User actions logged with timestamps and user context

---

## Testing Validation

### Unit Tests
Form request classes can be tested individually:
```php
$request = new ProductStoreRequest();
$validator = Validator::make($data, $request->rules());
$this->assertTrue($validator->fails());
```

### Integration Tests
API endpoints should be tested with various input scenarios:
- Valid data
- Missing required fields
- Invalid data types
- Boundary values
- Unauthorized access attempts

### Validation Rule Examples
```php
// Test required field
$data = []; // Empty data
$request = new ProductStoreRequest();
$this->assertFalse($request->authorize());
$validator = Validator::make($data, $request->rules());
$this->assertTrue($validator->fails());
$this->assertArrayHasKey('name', $validator->errors()->toArray());
```

---

## Future Enhancements

### Potential Validation Improvements
1. **Custom Validation Rules**: Create domain-specific rules (e.g., `ValidProductOwnership`)
2. **Request Validation Classes**: Expand form requests for all endpoints
3. **API Versioning**: Different validation rules per API version
4. **Advanced Rate Limiting**: Different limits based on user roles or subscription tiers
5. **Input Sanitization**: Additional sanitization for rich text inputs
6. **File Upload Validation**: If file uploads are added in the future

### Monitoring and Alerts
- Implement validation failure monitoring
- Alert on unusual validation error patterns
- Track validation performance impact

---

*This document is automatically generated and should be updated when validation rules change.*