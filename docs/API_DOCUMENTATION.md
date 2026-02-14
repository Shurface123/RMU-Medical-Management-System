# REST API Documentation

## Base URL
```
http://localhost/RMU-Medical-Management-System/php/api/router.php
```

## Authentication

All API requests (except login/register) require a Bearer token in the Authorization header:

```
Authorization: Bearer YOUR_JWT_TOKEN
```

---

## Endpoints

### Authentication

#### Login
```http
POST /login
Content-Type: application/json

{
  "username": "johndoe",
  "password": "password123"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "user": {
      "id": 1,
      "username": "johndoe",
      "name": "John Doe",
      "email": "john@example.com",
      "role": "patient"
    }
  }
}
```

#### Register
```http
POST /register
Content-Type: application/json

{
  "username": "johndoe",
  "email": "john@example.com",
  "password": "password123",
  "name": "John Doe"
}
```

---

### Appointments

#### Get All Appointments
```http
GET /appointments?page=1&per_page=20
Authorization: Bearer TOKEN
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "date": "2026-02-15",
      "time": "10:00:00",
      "status": "Scheduled",
      "reason": "Regular checkup",
      "doctor_name": "Dr. Smith"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total": 45,
    "total_pages": 3,
    "has_next": true,
    "has_prev": false
  }
}
```

#### Get Single Appointment
```http
GET /appointments/{id}
Authorization: Bearer TOKEN
```

#### Create Appointment
```http
POST /appointments
Authorization: Bearer TOKEN
Content-Type: application/json

{
  "doctor_id": 5,
  "date": "2026-02-20",
  "time": "14:00:00",
  "reason": "Follow-up consultation"
}
```

#### Update Appointment
```http
PUT /appointments/{id}
Authorization: Bearer TOKEN
Content-Type: application/json

{
  "date": "2026-02-21",
  "time": "15:00:00"
}
```

#### Cancel Appointment
```http
DELETE /appointments/{id}
Authorization: Bearer TOKEN
```

---

### Notifications

#### Get Notifications
```http
GET /notifications?page=1&per_page=20
Authorization: Bearer TOKEN
```

#### Mark as Read
```http
PUT /notifications/{id}/read
Authorization: Bearer TOKEN
```

#### Mark All as Read
```http
PUT /notifications/mark-all-read
Authorization: Bearer TOKEN
```

---

### Profile

#### Get Profile
```http
GET /profile
Authorization: Bearer TOKEN
```

#### Update Profile
```http
PUT /profile
Authorization: Bearer TOKEN
Content-Type: application/json

{
  "name": "John Updated",
  "email": "john.new@example.com"
}
```

---

### Doctors

#### Get All Doctors
```http
GET /doctors
Authorization: Bearer TOKEN
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 5,
      "name": "Dr. Jane Smith",
      "email": "dr.smith@example.com",
      "specialization": "General Practice"
    }
  ]
}
```

---

## Error Responses

All errors follow this format:

```json
{
  "success": false,
  "message": "Error description",
  "errors": [],
  "timestamp": "2026-02-14T12:00:00+00:00"
}
```

### Common Status Codes

- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `405` - Method Not Allowed
- `409` - Conflict
- `500` - Internal Server Error

---

## Rate Limiting

API requests are limited to 100 requests per minute per user.

---

## Mobile App Integration Example

```javascript
// Login example
async function login(username, password) {
  const response = await fetch('http://localhost/RMU-Medical-Management-System/php/api/router.php?path=login', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ username, password })
  });
  
  const data = await response.json();
  if (data.success) {
    // Store token
    localStorage.setItem('token', data.data.token);
    return data.data.user;
  }
  throw new Error(data.message);
}

// Authenticated request example
async function getAppointments() {
  const token = localStorage.getItem('token');
  
  const response = await fetch('http://localhost/RMU-Medical-Management-System/php/api/router.php?path=appointments', {
    headers: {
      'Authorization': `Bearer ${token}`
    }
  });
  
  const data = await response.json();
  return data.data;
}
```
