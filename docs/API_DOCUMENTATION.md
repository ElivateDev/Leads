# Leads API Documentation

## Overview

This API provides endpoints for retrieving lead information for reporting purposes. 

**Admin Access**: Admin users have access to all clients' data and can filter by specific clients.
**Client Access**: Regular client users can only access their own client's data.

## Authentication

The API uses **API Token authentication**. You need to include your API token in each request.

### Ways to include your API token:

1. **Authorization header (Recommended):**
   ```
   Authorization: Bearer YOUR_API_TOKEN
   ```

2. **X-API-Token header:**
   ```
   X-API-Token: YOUR_API_TOKEN
   ```

3. **Query parameter:**
   ```
   ?api_token=YOUR_API_TOKEN
   ```

### Getting an API Token

Ask your system administrator to generate an API token for you using this command:
```bash
php artisan api:token your-email@example.com "Token Name"
```

## Base URL

```
http://leads.test/api/
```

## Endpoints

### 1. Get Leads (with filtering)

**GET** `/api/leads`

Retrieves leads with optional filtering and pagination.

**Query Parameters:**

-   `start_date` (optional): Filter leads created after this date (YYYY-MM-DD)
-   `end_date` (optional): Filter leads created before this date (YYYY-MM-DD)
-   `client_id` (optional, admin only): Filter by specific client ID
-   `campaign` (optional): Filter by campaign name (use 'null' for leads without campaign)
-   `source` (optional): Filter by lead source (website, phone, referral, social, other)
-   `status` (optional): Filter by disposition/status
-   `per_page` (optional): Number of results per page (default: 50, max: 100)
-   `page` (optional): Page number for pagination

**Example Request:**

```
GET /api/leads?start_date=2025-09-01&end_date=2025-09-18&client_id=5&source=website&per_page=25
```

**Response:**

```json
{
    "data": [
        {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "phone": "555-1234",
            "message": "Interested in your services",
            "notes": "Follow up needed",
            "from_email": "contact@website.com",
            "email_subject": "Contact Form Submission",
            "status": "new",
            "source": "website",
            "campaign": "Summer Campaign",
            "client_id": 5,
            "client": {
                "id": 5,
                "name": "Acme Corp",
                "company": "Acme Corporation"
            },
            "created_at": "2025-09-18T10:30:00.000Z",
            "updated_at": "2025-09-18T10:30:00.000Z",
            "email_received_at": "2025-09-18T10:29:45.000Z"
        }
            "message": "Interested in your services",
            "notes": "Follow up needed",
            "from_email": "contact@website.com",
            "email_subject": "Contact Form Submission",
            "status": "new",
            "source": "website",
            "campaign": "Summer Campaign",
            "created_at": "2025-09-18T10:30:00.000Z",
            "updated_at": "2025-09-18T10:30:00.000Z",
            "email_received_at": "2025-09-18T10:29:45.000Z"
        }
    ],
    "meta": {
        "current_page": 1,
        "per_page": 25,
        "total": 15,
        "last_page": 1,
        "from": 1,
        "to": 15
    },
    "filters_applied": {
        "start_date": "2025-09-01",
        "end_date": "2025-09-18",
        "client_id": "5",
        "campaign": null,
        "source": "website",
        "status": null
    }
}
```

### 2. Get Lead Statistics

**GET** `/api/leads/stats`

Retrieves aggregated statistics for leads within a date range.

**Query Parameters:**

-   `start_date` (optional): Start date for statistics (default: 30 days ago)
-   `end_date` (optional): End date for statistics (default: today)
-   `client_id` (optional, admin only): Filter statistics for specific client

**Example Request:**

```
GET /api/leads/stats?start_date=2025-09-01&end_date=2025-09-18&client_id=5
```

**Response:**

```json
{
    "period": {
        "start_date": "2025-09-01",
        "end_date": "2025-09-18",
        "days": 18
    },
    "totals": {
        "total_leads": 15,
        "avg_per_day": 0.83
    },
    "breakdown": {
        "by_status": {
            "new": 8,
            "contacted": 4,
            "qualified": 2,
            "converted": 1
        },
        "by_source": {
            "website": 12,
            "phone": 2,
            "referral": 1
        },
        "by_campaign": {
            "Summer Campaign": 8,
            "No Campaign": 7
        },
        "by_client": {
            "Acme Corp": 10,
            "Widget Co": 5
        }
    },
    "daily_breakdown": {
        "2025-09-15": 3,
        "2025-09-16": 2,
        "2025-09-17": 5,
        "2025-09-18": 1
    }
}
```

### 3. Get Single Lead

**GET** `/api/leads/{id}`

Retrieves details for a specific lead.

**Example Request:**

```
GET /api/leads/1
```

**Response:**

```json
{
    "data": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "phone": "555-1234",
        "message": "Interested in your services",
        "notes": "Follow up needed",
        "from_email": "contact@website.com",
        "email_subject": "Contact Form Submission",
        "status": "new",
        "source": "website",
        "campaign": "Summer Campaign",
        "created_at": "2025-09-18T10:30:00.000Z",
        "updated_at": "2025-09-18T10:30:00.000Z",
        "email_received_at": "2025-09-18T10:29:45.000Z",
        "client_id": 1,
        "client": {
            "id": 1,
            "name": "Acme Corp",
            "company": "Acme Corporation"
        }
    }
}
```

### 4. Get Clients (Admin Only)

**GET** `/api/clients`

Retrieves a list of all clients with lead counts. Only accessible by admin users.

**Example Request:**

```
GET /api/clients
```

**Response:**

```json
{
    "data": [
        {
            "id": 1,
            "name": "Acme Corp",
            "company": "Acme Corporation",
            "email": "contact@acme.com",
            "leads_count": 45
        },
        {
            "id": 2,
            "name": "Widget Co",
            "company": "Widget Company",
            "email": "info@widget.com",
            "leads_count": 23
        }
    ],
    "total_clients": 2
}
```

## Data Fields

### Lead Object

-   `id`: Unique lead identifier
-   `name`: Lead's full name
-   `email`: Lead's email address
-   `phone`: Lead's phone number
-   `message`: Original message/inquiry from the lead
-   `notes`: Internal notes about the lead
-   `from_email`: Email address the lead was received from
-   `email_subject`: Subject line of the email
-   `status`: Current disposition (new, contacted, qualified, converted, lost)
-   `source`: How the lead was generated (website, phone, referral, social, other)
-   `campaign`: Marketing campaign associated with the lead
-   `client_id`: ID of the client this lead belongs to
-   `client`: Client object with id, name, and company
-   `created_at`: When the lead was created (ISO 8601 format)
-   `updated_at`: When the lead was last updated (ISO 8601 format)
-   `email_received_at`: When the original email was received (ISO 8601 format)

### Client Object

-   `id`: Unique client identifier
-   `name`: Client's name
-   `company`: Client's company name
-   `email`: Client's contact email
-   `leads_count`: Total number of leads for this client
-   `from_email`: Email address the lead was received from
-   `email_subject`: Subject line of the email
-   `status`: Current disposition (new, contacted, qualified, converted, lost)
-   `source`: How the lead was generated (website, phone, referral, social, other)
-   `campaign`: Marketing campaign associated with the lead
-   `created_at`: When the lead was created (ISO 8601 format)
-   `updated_at`: When the lead was last updated (ISO 8601 format)
-   `email_received_at`: When the original email was received (ISO 8601 format)

## Error Responses

### 403 Forbidden

```json
{
    "error": "User not associated with a client"
}
```

### 404 Not Found

```json
{
    "error": "Lead not found"
}
```

### 401 Unauthorized

```json
{
    "message": "Unauthenticated."
}
```

## Usage Examples

## Access Control

### Admin Users
- Can access leads from **all clients**
- Can filter by specific `client_id` to view data for a particular client
- Can access the `/api/clients` endpoint to get a list of all clients
- Statistics include an additional `by_client` breakdown when not filtering by client

### Client Users  
- Can only access leads from **their own client**
- Cannot use the `client_id` filter (it's ignored)
- Cannot access the `/api/clients` endpoint
- Statistics are automatically scoped to their client only

## Usage Examples

### Admin: Get all leads from the last 7 days
```bash
curl -X GET "/api/leads?start_date=2025-09-11" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_API_TOKEN"
```

### Admin: Get leads for specific client
```bash
curl -X GET "/api/leads?client_id=5&start_date=2025-09-01&end_date=2025-09-30" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_API_TOKEN"
```

### Admin: Get list of all clients
```bash
curl -X GET "/api/clients" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_API_TOKEN"
```

### Admin: Get cross-client statistics
```bash
curl -X GET "/api/leads/stats?start_date=2025-09-01&end_date=2025-09-30" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_API_TOKEN"
```

### Alternative: Using query parameter
```bash
curl -X GET "/api/leads?api_token=YOUR_API_TOKEN&start_date=2025-09-11" \
  -H "Accept: application/json"
```
