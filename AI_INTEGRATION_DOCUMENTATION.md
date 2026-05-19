# AI Integration Documentation

## Overview

The AI Forecast Project integrates machine learning capabilities to provide forecasting, insights, and recommendations based on historical sales data. The system uses a queue-based asynchronous architecture to handle intensive AI computations without blocking API requests.

---

## Table of Contents

1. [Architecture](#architecture)
2. [Core Components](#core-components)
3. [API Endpoints](#api-endpoints)
4. [Data Models](#data-models)
5. [Workflow](#workflow)
6. [Configuration](#configuration)
7. [Error Handling](#error-handling)
8. [Usage Examples](#usage-examples)

---

## Architecture

### System Overview

```
┌─────────────────┐
│   Client/API    │
│    Request      │
└────────┬────────┘
         │
         ▼
┌─────────────────────────────────────┐
│   API Controller (AIController)      │
│  - Validates input                  │
│  - Creates AI records               │
│  - Dispatches queue jobs            │
└────────┬────────────────────────────┘
         │
         ▼
┌─────────────────────────────────────┐
│   Queue System (Laravel Queue)       │
│  - GenerateForecastJob              │
│  - GenerateInsightsJob              │
│  - GenerateRecommendationsJob       │
└────────┬────────────────────────────┘
         │
         ▼
┌─────────────────────────────────────┐
│   AIService (HTTP Client)            │
│  - Communicates with AI Service     │
│  - Retry logic (exponential backoff) │
│  - Fallback responses               │
└────────┬────────────────────────────┘
         │
         ▼
┌─────────────────────────────────────┐
│   External AI Service                │
│  - ML Models                         │
│  - Data Processing                  │
│  - Predictions                      │
└─────────────────────────────────────┘
```

### Key Design Principles

- **Asynchronous Processing**: Heavy computations are handled via queues to keep API responsive
- **Non-blocking**: API returns immediately (202 Accepted) while jobs process in background
- **Resilient**: Retry logic and fallback responses ensure reliability
- **Authenticated**: All AI endpoints require authentication
- **Rate Limited**: AI operations are rate-limited to 60 requests per minute per user

---

## Core Components

### 1. AIController

**File**: `app/Http/Controllers/AIController.php`

Handles API requests and initiates AI operations.

#### Methods:

**`forecast(Request $request)`**
- Creates a forecast request for a product
- Validates product ownership
- Dispatches `GenerateForecastJob`
- Returns: 202 Accepted with forecast_id

**`insights()`**
- Creates an insights generation request
- Dispatches `GenerateInsightsJob`
- Returns: 202 Accepted with insight_id

**`recommendations()`**
- Creates a recommendations request
- Dispatches `GenerateRecommendationsJob`
- Returns: 202 Accepted with recommendation_id

**`forecastStatus(int $id)`**
- Retrieves the status and results of a forecast
- Validates authorization

**`insightsStatus(int $id)`**
- Retrieves the status and results of insights

**`recommendationsStatus(int $id)`**
- Retrieves the status and results of recommendations

### 2. AIService

**File**: `app/Services/AIService.php`

Manages communication with the external AI service.

#### Key Features:

- **Retry Logic**: Up to 3 retry attempts with exponential backoff
- **Timeout**: 30 seconds per request
- **Error Handling**: Catches exceptions and returns fallback responses
- **HTTP Client**: Uses Laravel's HTTP client for external requests

#### Methods:

**`requestForecastSync(int $productId, ?string $region = null): array`**
- Sends forecast request to AI service
- Includes product sales data
- Returns prediction results

**`getInsightsSync(int $userId): array`**
- Requests insights generation
- Returns insights data

**`getRecommendationsSync(int $userId): array`**
- Requests recommendations
- Returns recommendations data

**`makeRequestWithRetry(string $method, string $endpoint, array $payload = []): array`** (Private)
- Core HTTP request logic with retry mechanism
- Implements exponential backoff: 2^attempts seconds
- Returns fallback response on all retries exhausted

**`getFallbackResponse(string $endpoint): array`** (Private)
- Provides safe fallback responses when AI service is unavailable
- Prevents system failure

### 3. Queue Jobs

#### GenerateForecastJob

**File**: `app/Jobs/GenerateForecastJob.php`

Handles forecast generation in background queue.

**Process**:
1. Updates forecast status to 'processing'
2. Retrieves product sales data
3. Calls `AIService->requestForecastSync()`
4. Stores result and updates status to 'completed'
5. On error, updates status to 'failed'

#### GenerateInsightsJob

**File**: `app/Jobs/GenerateInsightsJob.php`

Handles insights generation asynchronously.

**Process**:
1. Updates insight status to 'processing'
2. Calls `AIService->getInsightsSync()`
3. Stores results
4. Updates status to 'completed'

#### GenerateRecommendationsJob

**File**: `app/Jobs/GenerateRecommendationsJob.php`

Handles recommendations generation asynchronously.

**Process**:
1. Updates recommendation status to 'processing'
2. Calls `AIService->getRecommendationsSync()`
3. Stores results
4. Updates status to 'completed'

### 4. Data Models

#### Forecast Model

**File**: `app/Models/Forecast.php`

Stores forecast records and predictions.

**Attributes**:
- `product_id`: Foreign key to products table
- `region`: Optional region for targeted forecasting
- `date`: Forecast date
- `predicted_revenue`: ML model prediction (decimal)
- `model_version`: Version of AI model used
- `status`: pending | processing | completed | failed
- `input_data`: JSON array of input parameters
- `result`: JSON array of prediction results
- `started_at`: Timestamp when job started
- `completed_at`: Timestamp when job completed
- `job_id`: Queue job ID for tracking

**Relations**:
- `product()`: Belongs to Product model

#### Insight Model

**File**: `app/Models/Insight.php`

Stores AI-generated insights about sales trends.

**Attributes**:
- `user_id`: Foreign key to users table
- `type`: Type of insight (trend, anomaly, pattern, etc.)
- `title`: Insight title
- `description`: Detailed description
- `status`: pending | processing | completed | failed
- `input_data`: JSON array of input data
- `result`: JSON array of insight results
- `model_version`: AI model version
- `started_at`: Processing start time
- `completed_at`: Processing completion time
- `job_id`: Queue job ID

**Relations**:
- `user()`: Belongs to User model

#### Recommendation Model

**File**: `app/Models/Recommendation.php`

Stores AI-generated recommendations.

**Attributes**:
- `user_id`: Foreign key to users table
- `product_id`: Foreign key to products table (optional)
- `message`: Recommendation message
- `priority`: Priority level (low, medium, high)
- `status`: pending | processing | completed | failed
- `input_data`: JSON array of input data
- `result`: JSON array of recommendation results
- `model_version`: AI model version
- `started_at`: Processing start time
- `completed_at`: Processing completion time
- `job_id`: Queue job ID

**Relations**:
- `user()`: Belongs to User model
- `product()`: Belongs to Product model

---

## API Endpoints

All endpoints require authentication and are protected with `auth:api` middleware.

### Rate Limiting
- **Limit**: 60 requests per minute per user
- **Middleware**: `throttle:60,1`
- **Applied to**: All `/ai/*` endpoints

### Forecast Endpoints

#### POST `/api/ai/forecast`

Generate a sales forecast for a product.

**Request**:
```json
{
  "product_id": 1,
  "region": "US-West"
}
```

**Parameters**:
- `product_id` (required): ID of product to forecast
- `region` (optional): Region for targeted forecast

**Response** (202 Accepted):
```json
{
  "message": "Forecast request queued for processing",
  "forecast_id": 5,
  "status": "pending"
}
```

**Authorization**: User must own the product

#### GET `/api/ai/forecast/{id}/status`

Check forecast status and retrieve results.

**Response** (200 OK):
```json
{
  "id": 5,
  "status": "completed",
  "result": {
    "predictions": [...],
    "confidence": 0.92,
    "model_version": "v1.0"
  },
  "started_at": "2026-05-04T10:30:00Z",
  "completed_at": "2026-05-04T10:35:00Z"
}
```

**Status Values**:
- `pending`: Queued, not yet processing
- `processing`: Currently being processed
- `completed`: Successfully completed
- `failed`: Processing failed

### Insights Endpoints

#### GET `/api/ai/insights`

Request generation of insights.

**Response** (202 Accepted):
```json
{
  "message": "Insights request queued for processing",
  "insight_id": 3,
  "status": "pending"
}
```

#### GET `/api/ai/insights/{id}/status`

Retrieve insight results.

**Response** (200 OK):
```json
{
  "id": 3,
  "status": "completed",
  "result": {
    "insights": [
      {
        "type": "trend",
        "title": "Rising Sales Trend",
        "description": "Product sales increasing 15% month-over-month"
      }
    ]
  }
}
```

### Recommendations Endpoints

#### GET `/api/ai/recommendations`

Request generation of recommendations.

**Response** (202 Accepted):
```json
{
  "message": "Recommendations request queued for processing",
  "recommendation_id": 7,
  "status": "pending"
}
```

#### GET `/api/ai/recommendations/{id}/status`

Retrieve recommendation results.

**Response** (200 OK):
```json
{
  "id": 7,
  "status": "completed",
  "result": {
    "recommendations": [
      {
        "product_id": 1,
        "priority": "high",
        "message": "Increase inventory for Product A due to predicted surge"
      }
    ]
  }
}
```

---

## Data Models

### Database Schema

#### forecasts table
```sql
CREATE TABLE forecasts (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  product_id BIGINT UNSIGNED NOT NULL,
  region VARCHAR(255) NULLABLE,
  date DATE NULLABLE,
  predicted_revenue DECIMAL(10,2) NULLABLE,
  model_version VARCHAR(50) NULLABLE DEFAULT 'v1.0',
  status VARCHAR(50) NOT NULL DEFAULT 'pending',
  input_data JSON NULLABLE,
  result JSON NULLABLE,
  started_at TIMESTAMP NULL,
  completed_at TIMESTAMP NULL,
  job_id VARCHAR(255) NULLABLE,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
)
```

#### insights table
```sql
CREATE TABLE insights (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  type VARCHAR(100) NULLABLE,
  title VARCHAR(255) NULLABLE,
  description TEXT NULLABLE,
  status VARCHAR(50) NOT NULL DEFAULT 'pending',
  input_data JSON NULLABLE,
  result JSON NULLABLE,
  model_version VARCHAR(50) NULLABLE DEFAULT 'v1.0',
  started_at TIMESTAMP NULL,
  completed_at TIMESTAMP NULL,
  job_id VARCHAR(255) NULLABLE,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)
```

#### recommendations table
```sql
CREATE TABLE recommendations (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  product_id BIGINT UNSIGNED NULLABLE,
  message TEXT NULLABLE,
  priority VARCHAR(50) NULLABLE,
  status VARCHAR(50) NOT NULL DEFAULT 'pending',
  input_data JSON NULLABLE,
  result JSON NULLABLE,
  model_version VARCHAR(50) NULLABLE DEFAULT 'v1.0',
  started_at TIMESTAMP NULL,
  completed_at TIMESTAMP NULL,
  job_id VARCHAR(255) NULLABLE,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
)
```

---

## Workflow

### Complete Request Flow

#### 1. Forecast Generation Flow

```
User Request
     ↓
AIController::forecast()
     ↓
Validate product ownership
     ↓
Create Forecast record with status='pending'
     ↓
Dispatch GenerateForecastJob
     ↓
Return 202 Accepted immediately
     ↓
[Async Queue Processing]
     ↓
GenerateForecastJob::handle()
     ↓
Update status='processing', set started_at
     ↓
Fetch product sales data
     ↓
Call AIService::requestForecastSync()
     ↓
AIService calls external AI service (with retries)
     ↓
Store result, update status='completed'
     ↓
Set completed_at timestamp
```

#### 2. Polling for Results

```
User calls GET /api/ai/forecast/{id}/status
     ↓
AIController::forecastStatus()
     ↓
Verify user authorization
     ↓
Return current status & result
```

**Status Responses**:
- `pending`: Data still processing
- `completed`: Results available in `result` field
- `failed`: Processing encountered error

---

## Configuration

### Environment Variables

Add to `.env` file:

```env
# AI Service Configuration
AI_SERVICE_URL=http://localhost:5000

# Queue Configuration
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Rate Limiting
AI_RATE_LIMIT=60
AI_RATE_PERIOD=1
```

### Configuration File

Edit `config/services.php`:

```php
'ai_service' => [
    'url' => env('AI_SERVICE_URL', 'http://localhost:8000'),
    'timeout' => 30,
    'max_retries' => 3,
]
```

### Queue Configuration

Ensure queue driver is configured in `config/queue.php`:

```php
'default' => env('QUEUE_CONNECTION', 'sync'),

'connections' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => env('REDIS_QUEUE', 'default'),
        'retry_after' => 90,
        'block_for' => null,
    ],
]
```

---

## Error Handling

### Retry Strategy

The AIService implements automatic retry logic:

**Configuration**:
- Maximum retries: 3
- Backoff strategy: Exponential (2^attempts seconds)
  - Attempt 1: Immediate
  - Attempt 2: Wait 2 seconds
  - Attempt 3: Wait 4 seconds

**Retry Triggers**:
- HTTP 5xx errors (server errors)
- Network timeout exceptions
- Connection refused errors

**Non-Retryable Errors**:
- HTTP 4xx errors (client errors)
- Validation errors
- Not found (404) errors

### Error Responses

#### Validation Error
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "product_id": ["The product_id field is required."]
  }
}
```

#### Authorization Error
```json
{
  "message": "Unauthorized"
}
Status: 403
```

#### Not Found
```json
{
  "message": "Not found"
}
Status: 404
```

#### Service Unavailable
If AI service is down after retries:

**Forecast Response**:
```json
{
  "forecasts": [],
  "model_version": "fallback",
  "message": "AI service temporarily unavailable"
}
```

The system gracefully degrades rather than failing completely.

### Logging

All AI operations are logged to `storage/logs/laravel.log`:

```
[2026-05-04 10:30:00] local.INFO: Forecast job dispatched
  {"forecast_id": 5, "user_id": 1}

[2026-05-04 10:30:05] local.INFO: AI Service Request Attempt 1
  {"method": "POST", "endpoint": "/api/forecast", "payload": {...}}

[2026-05-04 10:30:08] local.INFO: Forecast generated successfully
  {"forecast_id": 5}
```

---

## Usage Examples

### 1. Request a Forecast

```bash
curl -X POST http://localhost/api/ai/forecast \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": 1,
    "region": "US-East"
  }'
```

**Response**:
```json
{
  "message": "Forecast request queued for processing",
  "forecast_id": 5,
  "status": "pending"
}
```

### 2. Check Forecast Status

```bash
curl http://localhost/api/ai/forecast/5/status \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**While Processing**:
```json
{
  "id": 5,
  "status": "processing",
  "result": null,
  "started_at": "2026-05-04T10:30:00Z",
  "completed_at": null
}
```

**After Completion**:
```json
{
  "id": 5,
  "status": "completed",
  "result": {
    "predictions": [
      {
        "date": "2026-05-15",
        "predicted_revenue": 45000,
        "confidence": 0.92
      }
    ],
    "model_version": "v1.0"
  },
  "started_at": "2026-05-04T10:30:00Z",
  "completed_at": "2026-05-04T10:32:15Z"
}
```

### 3. Request Insights

```bash
curl -X GET http://localhost/api/ai/insights \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 4. Check Insights Status

```bash
curl http://localhost/api/ai/insights/3/status \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 5. Request Recommendations

```bash
curl -X GET http://localhost/api/ai/recommendations \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 6. JavaScript/Frontend Example

```javascript
// Request forecast
async function requestForecast(productId, region) {
  const response = await fetch('/api/ai/forecast', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${authToken}`
    },
    body: JSON.stringify({
      product_id: productId,
      region: region
    })
  });
  
  const data = await response.json();
  return data.forecast_id;
}

// Poll for results
async function checkForecastStatus(forecastId) {
  const response = await fetch(`/api/ai/forecast/${forecastId}/status`, {
    headers: {
      'Authorization': `Bearer ${authToken}`
    }
  });
  
  return response.json();
}

// Usage
const forecastId = await requestForecast(1, 'US-West');

// Poll every 2 seconds
const interval = setInterval(async () => {
  const status = await checkForecastStatus(forecastId);
  
  if (status.status === 'completed') {
    console.log('Forecast ready:', status.result);
    clearInterval(interval);
  } else if (status.status === 'failed') {
    console.error('Forecast failed');
    clearInterval(interval);
  }
}, 2000);
```

---

## Troubleshooting

### AI Service Not Responding

**Issue**: Getting fallback responses consistently

**Solution**:
1. Check AI service is running: `curl http://localhost:8000/health`
2. Verify AI_SERVICE_URL in `.env`
3. Check network connectivity between services
4. Review logs: `tail -f storage/logs/laravel.log`

### Jobs Not Processing

**Issue**: Requests stay in 'pending' status

**Solution**:
1. Verify queue worker is running: `php artisan queue:work`
2. Check queue connection in `.env` (usually 'redis')
3. Verify Redis is running if using Redis queue
4. Check job failures: `php artisan queue:failed`

### Authorization Errors (403)

**Issue**: "Unauthorized" response

**Solution**:
1. Verify user owns the product (for forecasts)
2. Check authentication token is valid
3. Ensure token has correct permissions

### Rate Limiting (429)

**Issue**: Too Many Requests error

**Solution**:
- Default limit: 60 requests/minute
- Implement client-side rate limiting
- Distribute requests across multiple users
- Contact admin to increase limits if needed

---

## Performance Optimization

### Best Practices

1. **Batch Requests**: Request multiple forecasts in succession rather than waiting for each
2. **Cache Results**: Store completed predictions if they're frequently accessed
3. **Monitor Processing Time**: Track average job completion time
4. **Optimize Sales Data**: Ensure sales data is indexed for fast retrieval
5. **Queue Prioritization**: Use job priorities for critical forecasts

### Monitoring

Track these metrics:

- Average forecast generation time
- AI service response times
- Failed job count
- Queue backlog
- Rate limit usage per user

---

## Integration Checklist

Before deploying to production:

- [ ] Configure AI_SERVICE_URL environment variable
- [ ] Set up queue worker (redis/database)
- [ ] Configure rate limiting settings
- [ ] Set up monitoring and alerting
- [ ] Test error scenarios (AI service down, network timeout)
- [ ] Configure logging
- [ ] Set up database backups
- [ ] Document API endpoints for frontend team
- [ ] Implement client-side polling strategy
- [ ] Test authentication and authorization
- [ ] Load test the API
- [ ] Document any custom modifications

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | 2026-05-04 | Initial AI integration documentation |

---

## Support

For questions or issues:

1. Check logs: `storage/logs/laravel.log`
2. Review this documentation
3. Check AI service documentation
4. Contact development team
