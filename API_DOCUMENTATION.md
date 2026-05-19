# AI-Powered Sales Forecasting Dashboard API Documentation

## Overview
This API manages products, sales, dashboard analytics, and asynchronous AI forecasting, insights, and recommendations.

## Base URL
`http://localhost:8000/api`

## Authentication
All protected endpoints require a JWT token in the Authorization header:

```
Authorization: Bearer {token}
```

## API Structure
- Authentication
- Product management
- Sales management
- Dashboard analytics
- Asynchronous AI operations

---

## Authentication

### Register
- **URL**: `POST /register`
- **Description**: Create a new user account.
- **Request Body**:
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```
- **Response**:
```json
{
  "message": "User registered successfully",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com"
  },
  "token": "jwt_token_here"
}
```
- **Status Codes**: 201, 422

### Login
- **URL**: `POST /login`
- **Description**: Authenticate and receive a JWT token.
- **Request Body**:
```json
{
  "email": "john@example.com",
  "password": "password123"
}
```
- **Response**:
```json
{
  "message": "Login successful",
  "token": "jwt_token_here",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com"
  }
}
```
- **Status Codes**: 200, 401

### Logout
- **URL**: `POST /logout`
- **Description**: Invalidate the current JWT token.
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "message": "Successfully logged out"
}
```
- **Status Codes**: 200

---

## Products

### List Products
- **URL**: `GET /products`
- **Description**: Retrieve all products for the authenticated user.
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
[
  {
    "id": 1,
    "name": "Product A",
    "category": "Electronics",
    "created_at": "2026-04-16T12:00:00.000000Z",
    "updated_at": "2026-04-16T12:00:00.000000Z"
  }
]
```
- **Status Codes**: 200

### Create Product
- **URL**: `POST /products`
- **Description**: Add a new product.
- **Headers**: `Authorization: Bearer {token}`
- **Request Body**:
```json
{
  "name": "New Product",
  "category": "Electronics"
}
```
- **Response**:
```json
{
  "id": 2,
  "name": "New Product",
  "category": "Electronics",
  "created_at": "2026-04-16T12:05:00.000000Z",
  "updated_at": "2026-04-16T12:05:00.000000Z"
}
```
- **Status Codes**: 201, 422

### Show Product
- **URL**: `GET /products/{id}`
- **Description**: Retrieve a single product.
- **Headers**: `Authorization: Bearer {token}`
- **Response**: Product object
- **Status Codes**: 200, 404

### Update Product
- **URL**: `PUT /products/{id}`
- **Description**: Update product fields.
- **Headers**: `Authorization: Bearer {token}`
- **Request Body**:
```json
{
  "name": "Updated Product",
  "category": "Office"
}
```
- **Response**: Updated product object
- **Status Codes**: 200, 404, 422

### Delete Product
- **URL**: `DELETE /products/{id}`
- **Description**: Remove a product and its related records.
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "message": "Product deleted successfully"
}
```
- **Status Codes**: 200, 404

---

## Sales

### List Sales
- **URL**: `GET /sales`
- **Description**: Retrieve sales for all products owned by the user.
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
[
  {
    "id": 1,
    "product_id": 1,
    "region": "North",
    "date": "2026-04-01",
    "quantity": 10,
    "revenue": 100.00,
    "created_at": "2026-04-16T12:10:00.000000Z",
    "updated_at": "2026-04-16T12:10:00.000000Z"
  }
]
```
- **Status Codes**: 200

### Create Sale
- **URL**: `POST /sales`
- **Description**: Create a new sale record.
- **Headers**: `Authorization: Bearer {token}`
- **Request Body**:
```json
{
  "product_id": 1,
  "region": "North",
  "date": "2026-04-01",
  "quantity": 10,
  "revenue": 100.00
}
```
- **Response**: Sale object
- **Status Codes**: 201, 403, 422

### Show Sale
- **URL**: `GET /sales/{id}`
- **Description**: Retrieve a single sale entry.
- **Headers**: `Authorization: Bearer {token}`
- **Response**: Sale object
- **Status Codes**: 200, 404

### Update Sale
- **URL**: `PUT /sales/{id}`
- **Description**: Update sales information.
- **Headers**: `Authorization: Bearer {token}`
- **Request Body**: any sale field
- **Response**: Sale object
- **Status Codes**: 200, 404, 422

### Delete Sale
- **URL**: `DELETE /sales/{id}`
- **Description**: Delete a sale record.
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "message": "Sale deleted successfully"
}
```
- **Status Codes**: 200, 404

---

## Dashboard Analytics

### Total Sales
- **URL**: `GET /dashboard/total-sales`
- **Description**: Return total revenue for the authenticated user.
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "total_sales": 1500.00
}
```
- **Status Codes**: 200

### Sales Over Time
- **URL**: `GET /dashboard/sales-over-time`
- **Description**: Return total revenue for each of the last 6 sales months.
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "sales_over_time": [
    {
      "month": "2026-01",
      "total_revenue": 4500.00
    },
    {
      "month": "2026-02",
      "total_revenue": 5100.00
    }
  ]
}
```
- **Status Codes**: 200

### Top Products
- **URL**: `GET /dashboard/top-products`
- **Description**: Return highest revenue-producing products.
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "top_products": [
    {
      "name": "Product A",
      "total_revenue": 1000.00
    }
  ]
}
```
- **Status Codes**: 200

### Monthly Comparison
- **URL**: `GET /dashboard/monthly-comparison`
- **Description**: Return revenue grouped by month.
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "monthly_comparison": [
    {
      "year": 2026,
      "month": 4,
      "total_revenue": 1500.00
    }
  ]
}
```
- **Status Codes**: 200

### Sales Growth
- **URL**: `GET /dashboard/sales-growth`
- **Description**: Return month-over-month growth percentage.
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "sales_growth_percentage": 12.34
}
```
- **Status Codes**: 200

### Moving Average
- **URL**: `GET /dashboard/moving-average`
- **Description**: Return moving average revenue results.
- **Headers**: `Authorization: Bearer {token}`
- **Query Parameters**:
  - `days` (optional, integer, 3-30, default 7)
- **Response**:
```json
{
  "moving_average": [
    {
      "date": "2026-04-01",
      "moving_average": 120.50
    }
  ]
}
```
- **Status Codes**: 200, 422

### Trends
- **URL**: `GET /dashboard/trends`
- **Description**: Detect sales trend direction.
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "trends": {
    "trend": "increasing",
    "change_percentage": 15.00
  }
}
```
- **Status Codes**: 200

### Anomalies
- **URL**: `GET /dashboard/anomalies`
- **Description**: Return revenue anomalies using statistical outlier detection.
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "anomalies": [
    {
      "date": "2026-04-05",
      "revenue": 2000.00,
      "z_score": 3.20,
      "type": "high"
    }
  ]
}
```
- **Status Codes**: 200

---

## AI Integration

### Overview
AI endpoints are asynchronous. Requests queue a job and return a pending status. Use the status endpoints to poll results after processing.

### Request Forecast
- **URL**: `POST /ai/forecast`
- **Description**: Queue a forecast generation job.
- **Headers**: `Authorization: Bearer {token}`
- **Request Body**:
```json
{
  "product_id": 1,
  "region": "North"
}
```
- **Response**:
```json
{
  "message": "Forecast request queued for processing",
  "forecast_id": 1,
  "status": "pending"
}
```
- **Status Codes**: 202, 403, 422

### Request Insights
- **URL**: `GET /ai/insights`
- **Description**: Queue an insights generation job.
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "message": "Insights request queued for processing",
  "insight_id": 1,
  "status": "pending"
}
```
- **Status Codes**: 202

### Request Recommendations
- **URL**: `GET /ai/recommendations`
- **Description**: Queue a recommendations generation job.
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "message": "Recommendations request queued for processing",
  "recommendation_id": 1,
  "status": "pending"
}
```
- **Status Codes**: 202

### Forecast Status
- **URL**: `GET /ai/forecast/{id}/status`
- **Description**: Retrieve the status and result of a forecast, including model version and timestamps.
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "id": 1,
  "status": "completed",
  "result": {
    "forecasts": [
      {
        "date": "2026-05-01",
        "predicted_revenue": 120.00
      }
    ],
    "model_version": "v1.0"
  },
  "started_at": "2026-04-16T12:00:00.000000Z",
  "completed_at": "2026-04-16T12:00:25.000000Z"
}
```
- **Status Codes**: 200, 404

### Insights Status
- **URL**: `GET /ai/insights/{id}/status`
- **Description**: Retrieve the status and result of an insights job.
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "id": 1,
  "status": "completed",
  "result": {
    "insights": [
      {
        "type": "trend",
        "title": "Sales Increasing",
        "description": "Sales have increased by 20% this month"
      }
    ]
  },
  "started_at": "2026-04-16T12:00:00.000000Z",
  "completed_at": "2026-04-16T12:00:15.000000Z"
}
```
- **Status Codes**: 200, 404

### Recommendations Status
- **URL**: `GET /ai/recommendations/{id}/status`
- **Description**: Retrieve the status and result of a recommendations job.
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
  "id": 1,
  "status": "completed",
  "result": {
    "recommendations": [
      {
        "product_id": 1,
        "message": "Consider increasing stock for Product A",
        "priority": "high"
      }
    ]
  },
  "started_at": "2026-04-16T12:00:00.000000Z",
  "completed_at": "2026-04-16T12:00:20.000000Z"
}
```
- **Status Codes**: 200, 404

---

## Common Error Responses

### Validation Error (422)
```json
{
  "name": ["The name field is required."],
  "email": ["The email has already been taken."]
}
```

### Unauthorized (401)
```json
{
  "error": "Unauthorized"
}
```

### Forbidden (403)
```json
{
  "message": "Forbidden"
}
```

### Not Found (404)
```json
{
  "message": "Not found"
}
```

### Rate Limit Exceeded (429)
```json
{
  "message": "Too Many Attempts. Please slow down."
}
```

### Server Error (500)
```json
{
  "message": "Internal Server Error"
}
```

---

## Notes
- Use `Authorization: Bearer {token}` for all protected endpoints.
- AI operations are queued asynchronously and return pending status immediately.
- Use status endpoints to poll results after processing.
- Dates are `YYYY-MM-DD` and timestamps are ISO 8601.
- Revenue values are decimals with 2 decimal places.
- Configure the AI service endpoint via `AI_SERVICE_URL` in `.env`.
- Dashboard responses are cached for performance.

{
  "error": "Unauthorized"
}
```

### Not Found (404)
```json
{
  "message": "Product not found"
}
```

### Forbidden (403)
```json
{
  "message": "Unauthorized"
}
```

### Server Error (500)
```json
{
  "message": "Internal Server Error"
}
```

## Notes
- All dates are in YYYY-MM-DD format
- Revenue and predicted_revenue are decimal values with 2 decimal places
- Quantity is an integer
- AI service URL can be configured via AI_SERVICE_URL environment variable
- Database is SQLite for development, can be changed to MySQL in production