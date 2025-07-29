# API Documentation

## Base URL
```
https://your-domain.com/api/v1
```

## Authentication
Most endpoints require authentication using Bearer token. Include the token in the Authorization header:
```
Authorization: Bearer {your_token}
```

## Response Format
All API responses follow this standard format:

### Success Response
```json
{
    "status": true,
    "message": "Success message",
    "data": {
        // Response data
    }
}
```

### Error Response
```json
{
    "status": false,
    "message": "Error message",
    "errors": {
        // Validation errors (if any)
    }
}
```

---

## 1. User Registration

### Register New User
**POST** `/register`

Creates a new user account.

#### Request Body
```json
{
    "firstname": "John",
    "lastname": "Doe",
    "email": "john.doe@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "gender": "m",
    "captcha": "captcha_value",
    "agree": true,
    "zip": "12345",
    "state": "California",
    "city": "Los Angeles",
    "address": "123 Main St"
}
```

#### Field Descriptions
- `firstname` (required): User's first name
- `lastname` (required): User's last name
- `email` (required): Unique email address
- `password` (required): Password (minimum 6 characters, may require complexity based on settings)
- `password_confirmation` (required): Password confirmation
- `gender` (required): Gender - "m" for male, "f" for female
- `captcha` (optional): Captcha verification value
- `agree` (optional): Terms and conditions agreement
- `zip` (optional): ZIP/Postal code (6 digits)
- `state` (optional): State/Province
- `city` (optional): City
- `address` (optional): Address

#### Success Response (200)
```json
{
    "status": true,
    "message": "Registration successful",
    "data": {
        "user": {
            "id": 1,
            "profile_id": "12345678",
            "firstname": "John",
            "lastname": "Doe",
            "email": "john.doe@example.com",
            "gender": "m",
            "created_at": "2024-01-01T00:00:00.000000Z",
            "updated_at": "2024-01-01T00:00:00.000000Z"
        },
        "token": "1|abc123def456..."
    }
}
```

#### Error Responses
- **422 Unprocessable Entity**: Validation errors
- **400 Bad Request**: Invalid captcha or registration disabled
- **403 Forbidden**: Registration disabled

---

## 2. User Authentication

### Login
**POST** `/v1/user/login`

Authenticates a user and returns an access token.

#### Request Body
```json
{
    "username": "john.doe@example.com",
    "password": "password123"
}
```

#### Field Descriptions
- `username` (required): Email address or username
- `password` (required): User's password

#### Success Response (200)
```json
{
    "status": true,
    "message": "Login successful",
    "data": {
        "token": "1|abc123def456...",
        "user": {
            "id": 1,
            "profile_id": "12345678",
            "firstname": "John",
            "lastname": "Doe",
            "email": "john.doe@example.com",
            "username": "john_doe",
            "gender": "m",
            "profile_complete": 0,
            "created_at": "2024-01-01T00:00:00.000000Z",
            "updated_at": "2024-01-01T00:00:00.000000Z"
        }
    }
}
```

#### Error Responses
- **422 Unprocessable Entity**: Validation errors
- **401 Unauthorized**: Invalid credentials

### Logout
**POST** `/v1/user/logout`

Logs out the authenticated user by revoking the current token.

#### Headers
```
Authorization: Bearer {your_token}
```

#### Success Response (200)
```json
{
    "status": true,
    "message": "Logout successful",
    "data": null
}
```

#### Error Responses
- **401 Unauthorized**: Invalid or missing token

---

## 3. User Profile Management

### Get User Data
**GET** `/v1/user/user-data`

Retrieves user data for profile completion steps.

#### Headers
```
Authorization: Bearer {your_token}
```

#### Success Response (200)
```json
{
    "status": true,
    "message": "User data step retrieved",
    "data": {
        "current_step": "basicInfo",
        "data": {
            "religions": [
                {
                    "id": 1,
                    "name": "Islam"
                }
            ],
            "maritalStatuses": [
                {
                    "id": 1,
                    "title": "Single",
                    "children": []
                }
            ],
            "countries": [
                {
                    "country": "United States",
                    "dial_code": "+1",
                    "code": "US"
                }
            ],
            "user": {
                "id": 1,
                "profile_id": "12345678",
                "firstname": "John",
                "lastname": "Doe",
                "email": "john.doe@example.com"
            },
            "mobileCode": "+1"
        }
    }
}
```

### Submit User Data Step
**POST** `/v1/user/user-data-submit/{step}`

Submits data for a specific profile completion step.

#### Headers
```
Authorization: Bearer {your_token}
```

#### URL Parameters
- `step` (required): Step name - one of: `basicInfo`, `familyInfo`, `educationInfo`, `careerInfo`, `physicalAttributeInfo`, `partnerExpectation`

#### Request Body Examples

##### Basic Info Step
```json
{
    "birth_date": "1990-01-01",
    "religion": "Islam",
    "gender": "m",
    "profession": "Software Engineer",
    "financial_condition": "Good",
    "smoking_status": "0",
    "drinking_status": "0",
    "marital_status": "Single",
    "languages": ["English", "Spanish"],
    "country_code": "US",
    "country": "United States",
    "mobile_code": "+1",
    "username": "john_doe",
    "mobile": "1234567890",
    "pre_city": "Los Angeles",
    "per_city": "Los Angeles"
}
```

##### Family Info Step
```json
{
    "father_name": "John Doe Sr.",
    "mother_name": "Jane Doe",
    "family_type": "Nuclear",
    "family_status": "Middle Class",
    "family_values": "Traditional",
    "family_income": "50000-75000",
    "siblings": "2",
    "family_location": "Los Angeles"
}
```

##### Education Info Step
```json
{
    "education_level": "Bachelor's Degree",
    "institution": "University of California",
    "degree": "Computer Science",
    "passing_year": "2012",
    "result": "3.8 GPA"
}
```

##### Career Info Step
```json
{
    "occupation": "Software Engineer",
    "company": "Tech Corp",
    "income": "75000-100000",
    "work_location": "San Francisco"
}
```

##### Physical Attribute Info Step
```json
{
    "height": "5'10\"",
    "weight": "70 kg",
    "body_type": "Athletic",
    "complexion": "Fair",
    "blood_group": "O+",
    "disability": "None"
}
```

##### Partner Expectation Step
```json
{
    "preferred_age_min": "25",
    "preferred_age_max": "35",
    "preferred_height_min": "5'5\"",
    "preferred_height_max": "6'0\"",
    "preferred_marital_status": "Single",
    "preferred_religion": "Islam",
    "preferred_education": "Bachelor's Degree",
    "preferred_profession": "Professional",
    "preferred_country": "United States"
}
```

#### Success Response (200)
```json
{
    "status": true,
    "message": "Step completed successfully",
    "data": null
}
```

#### Error Responses
- **422 Unprocessable Entity**: Validation errors
- **404 Not Found**: Invalid step name
- **400 Bad Request**: Invalid back_to field

---

## 4. Dashboard

### Get Dashboard Data
**GET** `/v1/user/dashboard`

Retrieves user dashboard information including profile data, interests, and statistics.

#### Headers
```
Authorization: Bearer {your_token}
```

#### Success Response (200)
```json
{
    "status": true,
    "message": "User dashboard data retrieved successfully",
    "data": {
        "id": 1,
        "profile_id": "12345678",
        "firstname": "John",
        "lastname": "Doe",
        "email": "john.doe@example.com",
        "username": "john_doe",
        "gender": "m",
        "profile_complete": 1,
        "limitation": {
            "id": 1,
            "package_id": 1,
            "interest_express_limit": 10,
            "contact_view_limit": 5,
            "image_upload_limit": 10,
            "validity_period": 30,
            "expire_date": "2024-02-01T00:00:00.000000Z"
        },
        "interests": [
            {
                "id": 1,
                "user_id": 1,
                "profile_id": 2,
                "created_at": "2024-01-01T00:00:00.000000Z",
                "profile": {
                    "id": 2,
                    "basicInfo": {
                        "id": 2,
                        "user_id": 2,
                        "gender": "f",
                        "profession": "Doctor",
                        "birth_date": "1992-05-15"
                    }
                }
            }
        ],
        "interestRequests": [
            {
                "id": 1,
                "user_id": 2,
                "profile_id": 1,
                "created_at": "2024-01-01T00:00:00.000000Z",
                "user": {
                    "id": 2,
                    "basicInfo": {
                        "id": 2,
                        "user_id": 2,
                        "gender": "f",
                        "profession": "Doctor",
                        "birth_date": "1992-05-15"
                    }
                }
            }
        ],
        "total_images": 5,
        "totalShortlisted": 3,
        "interestSent": 8,
        "totalInterestRequests": 12
    }
}
```

---

## 5. Check User Existence

### Check User
**POST** `/check-user`

Checks if a user with the provided email, mobile, or username already exists.

#### Request Body
```json
{
    "email": "john.doe@example.com"
}
```

OR

```json
{
    "mobile": "1234567890",
    "mobile_code": "+1"
}
```

OR

```json
{
    "username": "john_doe"
}
```

#### Success Response (200)
```json
{
    "status": true,
    "message": "User check completed",
    "data": {
        "data": true,
        "type": "email",
        "field": "Email"
    }
}
```

#### Response Fields
- `data`: Boolean indicating if user exists
- `type`: Type of field checked ("email", "mobile", or "username")
- `field`: Human-readable field name

---

## Error Codes

| Status Code | Description |
|-------------|-------------|
| 200 | Success |
| 400 | Bad Request (Invalid captcha, registration disabled) |
| 401 | Unauthorized (Invalid credentials, missing token) |
| 403 | Forbidden (Registration disabled, unauthorized access) |
| 404 | Not Found (Invalid step, resource not found) |
| 422 | Unprocessable Entity (Validation errors) |

---

## Notes

1. **Profile Completion**: Users must complete their profile through the step-by-step process before accessing certain features.

2. **Token Management**: Access tokens are automatically generated upon login/registration and should be included in the Authorization header for protected endpoints.

3. **Validation**: All endpoints include comprehensive validation with detailed error messages.

4. **Rate Limiting**: API endpoints may be subject to rate limiting based on server configuration.

5. **Captcha**: Some endpoints may require captcha verification depending on system settings.

6. **Profile Steps**: The profile completion process follows this order:
   - basicInfo
   - familyInfo
   - educationInfo
   - careerInfo
   - physicalAttributeInfo
   - partnerExpectation 