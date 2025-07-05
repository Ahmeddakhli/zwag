# Matrimonial Platform API Documentation

## Overview

This is a comprehensive REST API for a matrimonial/dating platform built with Laravel 11. The API provides functionality for user registration, profile management, matchmaking, messaging, payments, and admin management.

## Base URL

```
https://yourdomain.com/api/v1
```

## Authentication

The API uses Laravel Sanctum for authentication. After successful login, you'll receive a bearer token that should be included in the Authorization header for protected endpoints.

### Headers

```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {your-token}
```

## Response Format

All API responses follow a consistent JSON format:

```json
{
    "success": true|false,
    "message": "Response message",
    "data": {}, // Response data
    "errors": {} // Validation errors (if any)
}
```

## Authentication Endpoints

### Register User

```http
POST /auth/register
```

**Request Body:**
```json
{
    "firstname": "John",
    "lastname": "Doe",
    "email": "john.doe@example.com",
    "mobile": "+1234567890",
    "password": "password123",
    "password_confirmation": "password123",
    "country_code": "+1",
    "country": "United States",
    "agree": true
}
```

**Response:**
```json
{
    "success": true,
    "message": "Registration successful",
    "data": {
        "user": {
            "id": 1,
            "firstname": "John",
            "lastname": "Doe",
            "email": "john.doe@example.com",
            "mobile": "+1234567890"
        },
        "token": "bearer-token-here",
        "email_verification_required": true,
        "mobile_verification_required": true
    }
}
```

### Login

```http
POST /auth/login
```

**Request Body:**
```json
{
    "username": "john.doe@example.com", // Can be email or mobile
    "password": "password123"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": {
            "id": 1,
            "firstname": "John",
            "lastname": "Doe",
            "email": "john.doe@example.com"
        },
        "token": "bearer-token-here",
        "profile_complete": false,
        "email_verified": true,
        "mobile_verified": false,
        "kyc_verified": false,
        "two_factor_enabled": false
    }
}
```

### Logout

```http
POST /auth/logout
```
*Requires Authentication*

### Get Current User

```http
GET /auth/user
```
*Requires Authentication*

### Change Password

```http
POST /auth/change-password
```
*Requires Authentication*

**Request Body:**
```json
{
    "current_password": "oldpassword",
    "password": "newpassword123",
    "password_confirmation": "newpassword123"
}
```

### Forgot Password

```http
POST /auth/forgot-password
```

**Request Body:**
```json
{
    "email": "john.doe@example.com"
}
```

### Reset Password

```http
POST /auth/reset-password
```

**Request Body:**
```json
{
    "email": "john.doe@example.com",
    "code": "123456",
    "password": "newpassword123",
    "password_confirmation": "newpassword123"
}
```

### Social Login

```http
POST /auth/social-login
```

**Request Body:**
```json
{
    "provider": "google", // or "facebook"
    "provider_id": "google-user-id",
    "email": "john.doe@example.com",
    "name": "John Doe"
}
```

## Profile Management

### Get Profile

```http
GET /profile
```
*Requires Authentication*

### Update Profile

```http
PUT /profile
```
*Requires Authentication*

**Request Body:**
```json
{
    "firstname": "John",
    "lastname": "Doe",
    "mobile": "+1234567890",
    "address": "123 Main St",
    "city": "New York",
    "state": "NY",
    "zip": "10001"
}
```

### Update Avatar

```http
POST /profile/avatar
```
*Requires Authentication*

**Request Body:** (multipart/form-data)
```
avatar: [image file]
```

### Update Basic Information

```http
PUT /profile/basic-info
```
*Requires Authentication*

**Request Body:**
```json
{
    "gender": 1, // 1 = Male, 2 = Female
    "profession": "Software Engineer",
    "financial_condition": "Upper Middle Class",
    "my_information": "About myself...",
    "present_address": "Current address",
    "permanent_address": "Permanent address"
}
```

### Update Physical Attributes

```http
PUT /profile/physical-attributes
```
*Requires Authentication*

**Request Body:**
```json
{
    "height": 175, // in cm
    "weight": 70, // in kg
    "eye_color": "brown",
    "hair_color": "black",
    "complexion": "fair",
    "body_type": "average"
}
```

### Career Information

```http
GET /profile/career
POST /profile/career
PUT /profile/career/{id}
DELETE /profile/career/{id}
```
*Requires Authentication*

**POST Request Body:**
```json
{
    "designation": "Software Engineer",
    "company": "Tech Corp",
    "start_date": "2020-01-01",
    "end_date": "2023-12-31", // nullable if present is true
    "present": false,
    "details": "Job description..."
}
```

### Education Information

```http
GET /profile/education
POST /profile/education
PUT /profile/education/{id}
DELETE /profile/education/{id}
```
*Requires Authentication*

## Matchmaking & Search

### Search Matches

```http
GET /matches?age_min=25&age_max=35&location=New York&religion=Christian
```
*Requires Authentication*

**Query Parameters:**
- `age_min` (optional): Minimum age
- `age_max` (optional): Maximum age
- `height_min` (optional): Minimum height in cm
- `height_max` (optional): Maximum height in cm
- `religion` (optional): Religion filter
- `marital_status` (optional): Marital status filter
- `education` (optional): Education filter
- `profession` (optional): Profession filter
- `location` (optional): Location filter
- `per_page` (optional): Results per page (default: 20, max: 50)

### Get Recommendations

```http
GET /matches/recommendations
```
*Requires Authentication*

### Get Profile Details

```http
GET /matches/{user_id}
```
*Requires Authentication*

### Record Profile View

```http
POST /matches/{user_id}/view
```
*Requires Authentication*

## User Interactions

### Interest Management

```http
GET /interactions/interests
GET /interactions/interests/sent
GET /interactions/interests/received
POST /interactions/interests/{user_id}/send
POST /interactions/interests/{interest_id}/accept
POST /interactions/interests/{interest_id}/decline
DELETE /interactions/interests/{interest_id}
```
*Requires Authentication*

### Shortlist Management

```http
GET /interactions/shortlist
POST /interactions/shortlist/{user_id}
DELETE /interactions/shortlist/{user_id}
```
*Requires Authentication*

### Ignored Profiles

```http
GET /interactions/ignored
POST /interactions/ignored/{user_id}
DELETE /interactions/ignored/{user_id}
```
*Requires Authentication*

### Contact Views

```http
GET /interactions/contacts
POST /interactions/contacts/{user_id}/view
GET /interactions/contacts/limit-status
```
*Requires Authentication*

## Messaging System

### Get Conversations

```http
GET /messages
```
*Requires Authentication*

### Get Messages in Conversation

```http
GET /messages/{conversation_id}
```
*Requires Authentication*

### Send Message

```http
POST /messages
```
*Requires Authentication*

**Request Body:**
```json
{
    "receiver_id": 123,
    "message": "Hello, how are you?",
    "conversation_id": 456 // optional
}
```

### Update Message

```http
PUT /messages/{message_id}
```
*Requires Authentication*

**Request Body:**
```json
{
    "message": "Updated message content"
}
```

### Delete Message

```http
DELETE /messages/{message_id}
```
*Requires Authentication*

### Mark Conversation as Read

```http
POST /messages/{conversation_id}/mark-read
```
*Requires Authentication*

### Search Users for Messaging

```http
GET /messages/search/users?query=john
```
*Requires Authentication*

### Get Unread Messages Count

```http
GET /messages/unread/count
```
*Requires Authentication*

## Gallery Management

### Get Gallery Images

```http
GET /gallery
```
*Requires Authentication*

### Upload Image

```http
POST /gallery
```
*Requires Authentication*

**Request Body:** (multipart/form-data)
```
image: [image file]
```

### Delete Image

```http
DELETE /gallery/{image_id}
```
*Requires Authentication*

### Set Primary Image

```http
PUT /gallery/{image_id}/set-primary
```
*Requires Authentication*

## Package & Payment System

### Get Packages

```http
GET /packages
```
*Requires Authentication*

### Get Package Details

```http
GET /packages/{package_id}
```
*Requires Authentication*

### Get Current Package Status

```http
GET /packages/current/status
```
*Requires Authentication*

### Purchase Package

```http
POST /packages/{package_id}/purchase
```
*Requires Authentication*

### Get Payment Gateways

```http
GET /payments/gateways
```
*Requires Authentication*

### Initiate Payment

```http
POST /payments/initiate
```
*Requires Authentication*

**Request Body:**
```json
{
    "package_id": 1,
    "gateway": "stripe",
    "amount": 99.99
}
```

### Payment History

```http
GET /payments/history
```
*Requires Authentication*

## Support & Reports

### Report User

```http
POST /reports/user/{user_id}
```
*Requires Authentication*

**Request Body:**
```json
{
    "reason": "inappropriate_behavior",
    "description": "Detailed description of the issue"
}
```

### Get My Reports

```http
GET /reports/my-reports
```
*Requires Authentication*

### Support Tickets

```http
GET /support/tickets
POST /support/tickets
GET /support/tickets/{ticket_id}
POST /support/tickets/{ticket_id}/reply
POST /support/tickets/{ticket_id}/close
```
*Requires Authentication*

**Create Ticket Request Body:**
```json
{
    "subject": "Technical Issue",
    "message": "I'm having trouble with...",
    "priority": "medium", // low, medium, high
    "attachments": ["file1.jpg", "file2.pdf"] // optional
}
```

## Notifications

### Get Notifications

```http
GET /notifications
```
*Requires Authentication*

### Mark Notification as Read

```http
POST /notifications/{notification_id}/read
```
*Requires Authentication*

### Mark All Notifications as Read

```http
POST /notifications/read-all
```
*Requires Authentication*

### Register Device Token (Push Notifications)

```http
POST /notifications/device-token
```
*Requires Authentication*

**Request Body:**
```json
{
    "token": "device-fcm-token",
    "type": "android" // android, ios, web
}
```

## KYC Verification

### Get KYC Status

```http
GET /kyc
```
*Requires Authentication*

### Submit KYC

```http
POST /kyc/submit
```
*Requires Authentication*

**Request Body:** (multipart/form-data)
```json
{
    "kyc_data": {
        "document_type": "passport",
        "document_number": "ABC123456",
        "full_name": "John Doe",
        "date_of_birth": "1990-01-01"
    },
    "documents": {
        "front": [image file],
        "back": [image file]
    }
}
```

### Get KYC Form Structure

```http
GET /kyc/form
```
*Requires Authentication*

## Dashboard

### Get Dashboard Stats

```http
GET /dashboard/stats
```
*Requires Authentication*

### Get Recent Activities

```http
GET /dashboard/recent-activities
```
*Requires Authentication*

### Get Profile Views

```http
GET /dashboard/profile-views
```
*Requires Authentication*

## Public Data Endpoints

These endpoints don't require authentication:

### Get Master Data

```http
GET /data/religions
GET /data/blood-groups
GET /data/marital-statuses
GET /data/languages
GET /data/packages
GET /data/general-settings
```

**Example Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Christian",
            "status": 1
        },
        {
            "id": 2,
            "name": "Muslim",
            "status": 1
        }
    ]
}
```

## Admin API Endpoints

Admin endpoints require authentication with admin privileges:

### Admin Dashboard

```http
GET /admin/dashboard
GET /admin/stats
```

### User Management

```http
GET /admin/users
GET /admin/users/active
GET /admin/users/banned
GET /admin/users/{id}
PUT /admin/users/{id}
POST /admin/users/{id}/ban
POST /admin/users/{id}/unban
```

### Package Management

```http
GET /admin/packages
POST /admin/packages
PUT /admin/packages/{id}
DELETE /admin/packages/{id}
POST /admin/packages/{id}/status
```

### Transaction Management

```http
GET /admin/transactions/deposits
GET /admin/transactions/deposits/pending
POST /admin/transactions/deposits/{id}/approve
POST /admin/transactions/deposits/{id}/reject
```

## Error Codes

- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Validation Error
- `500` - Internal Server Error

## Rate Limiting

The API implements rate limiting:
- Public endpoints: 60 requests per minute
- Authenticated endpoints: 120 requests per minute
- Admin endpoints: 200 requests per minute

## Pagination

List endpoints support pagination:

```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [...],
        "first_page_url": "...",
        "from": 1,
        "last_page": 5,
        "last_page_url": "...",
        "next_page_url": "...",
        "path": "...",
        "per_page": 20,
        "prev_page_url": null,
        "to": 20,
        "total": 100
    }
}
```

## Postman Collection

You can import the API endpoints into Postman using this collection:

```json
{
    "info": {
        "name": "Matrimonial Platform API",
        "description": "Complete API collection for the matrimonial platform"
    },
    "auth": {
        "type": "bearer",
        "bearer": [
            {
                "key": "token",
                "value": "{{access_token}}",
                "type": "string"
            }
        ]
    },
    "variable": [
        {
            "key": "base_url",
            "value": "https://yourdomain.com/api/v1"
        },
        {
            "key": "access_token",
            "value": ""
        }
    ]
}
```

## SDKs and Libraries

### JavaScript/TypeScript

```javascript
// Example using axios
const api = axios.create({
    baseURL: 'https://yourdomain.com/api/v1',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    }
});

// Set auth token
api.defaults.headers.common['Authorization'] = `Bearer ${token}`;

// Login
const login = async (username, password) => {
    const response = await api.post('/auth/login', {
        username,
        password
    });
    return response.data;
};

// Get matches
const getMatches = async (filters = {}) => {
    const response = await api.get('/matches', { params: filters });
    return response.data;
};
```

### PHP

```php
<?php
// Example using Guzzle HTTP client
use GuzzleHttp\Client;

class MatrimonialAPI
{
    private $client;
    private $token;

    public function __construct($baseUrl, $token = null)
    {
        $this->client = new Client(['base_uri' => $baseUrl]);
        $this->token = $token;
    }

    public function login($username, $password)
    {
        $response = $this->client->post('/auth/login', [
            'json' => [
                'username' => $username,
                'password' => $password
            ]
        ]);
        
        $data = json_decode($response->getBody(), true);
        if ($data['success']) {
            $this->token = $data['data']['token'];
        }
        
        return $data;
    }

    public function getMatches($filters = [])
    {
        $response = $this->client->get('/matches', [
            'headers' => ['Authorization' => 'Bearer ' . $this->token],
            'query' => $filters
        ]);
        
        return json_decode($response->getBody(), true);
    }
}
```

## Support

For API support, please contact:
- Email: api-support@yourdomain.com
- Documentation: https://docs.yourdomain.com
- GitHub Issues: https://github.com/yourdomain/matrimonial-api

## Changelog

### Version 1.0.0
- Initial API release
- Authentication system
- Profile management
- Matchmaking features
- Messaging system
- Payment integration
- Admin panel APIs