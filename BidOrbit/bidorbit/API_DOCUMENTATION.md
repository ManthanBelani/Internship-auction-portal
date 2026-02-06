# API Documentation

This document describes the API endpoints expected by the Flutter Auction App.

## Base URL

- Development: `http://localhost:8000/api`
- Production: `https://your-domain.com/api`

## Authentication

All authenticated endpoints require a JWT token in the Authorization header:

```
Authorization: Bearer <token>
```

## Endpoints

### Authentication

#### Register User

```http
POST /users/register
```

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "role": "buyer",
  "phone": "+1234567890"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "User registered successfully",
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {
    "id": "1",
    "name": "John Doe",
    "email": "john@example.com",
    "role": "buyer",
    "phone": "+1234567890",
    "created_at": "2024-01-01T00:00:00Z"
  }
}
```

#### Login

```http
POST /users/login
```

**Request Body:**
```json
{
  "email": "john@example.com",
  "password": "password123"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Login successful",
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {
    "id": "1",
    "name": "John Doe",
    "email": "john@example.com",
    "role": "buyer",
    "phone": "+1234567890",
    "created_at": "2024-01-01T00:00:00Z"
  }
}
```

#### Get Profile

```http
GET /users/profile
```

**Headers:**
```
Authorization: Bearer <token>
```

**Response (200):**
```json
{
  "success": true,
  "user": {
    "id": "1",
    "name": "John Doe",
    "email": "john@example.com",
    "role": "buyer",
    "phone": "+1234567890",
    "created_at": "2024-01-01T00:00:00Z"
  }
}
```

### Items

#### Get All Items

```http
GET /items?page=1&limit=20&search=house&sort=ending_soon&minPrice=100000&maxPrice=500000
```

**Query Parameters:**
- `page` (optional): Page number (default: 1)
- `limit` (optional): Items per page (default: 20)
- `search` (optional): Search query
- `sort` (optional): Sort by (ending_soon, newest, price_asc, price_desc)
- `minPrice` (optional): Minimum price filter
- `maxPrice` (optional): Maximum price filter

**Response (200):**
```json
{
  "success": true,
  "items": [
    {
      "id": "1",
      "title": "Beautiful Family Home",
      "description": "A stunning 4-bedroom house...",
      "starting_price": 250000,
      "current_price": 275000,
      "reserve_price": 300000,
      "start_time": "2024-01-01T00:00:00Z",
      "end_time": "2024-01-15T23:59:59Z",
      "status": "active",
      "seller_id": "2",
      "seller_name": "Jane Smith",
      "images": [
        "https://example.com/images/house1.jpg",
        "https://example.com/images/house2.jpg"
      ],
      "location": "New York, NY",
      "category": "residential",
      "bid_count": 15,
      "is_favorite": false
    }
  ],
  "pagination": {
    "current_page": 1,
    "total_pages": 5,
    "total_items": 100,
    "items_per_page": 20
  }
}
```

#### Get Item Details

```http
GET /items/:id
```

**Response (200):**
```json
{
  "success": true,
  "item": {
    "id": "1",
    "title": "Beautiful Family Home",
    "description": "A stunning 4-bedroom house with modern amenities...",
    "starting_price": 250000,
    "current_price": 275000,
    "reserve_price": 300000,
    "start_time": "2024-01-01T00:00:00Z",
    "end_time": "2024-01-15T23:59:59Z",
    "status": "active",
    "seller_id": "2",
    "seller_name": "Jane Smith",
    "images": [
      "https://example.com/images/house1.jpg",
      "https://example.com/images/house2.jpg"
    ],
    "location": "New York, NY",
    "category": "residential",
    "bid_count": 15,
    "is_favorite": true
  }
}
```

### Bids

#### Place Bid

```http
POST /bids
```

**Headers:**
```
Authorization: Bearer <token>
```

**Request Body:**
```json
{
  "item_id": "1",
  "amount": 280000
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Bid placed successfully",
  "bid": {
    "id": "1",
    "item_id": "1",
    "bidder_id": "1",
    "bidder_name": "John Doe",
    "amount": 280000,
    "timestamp": "2024-01-10T12:00:00Z",
    "status": "winning"
  }
}
```

#### Get Bids for Item

```http
GET /bids/:itemId
```

**Response (200):**
```json
{
  "success": true,
  "bids": [
    {
      "id": "1",
      "item_id": "1",
      "bidder_id": "1",
      "bidder_name": "John Doe",
      "amount": 280000,
      "timestamp": "2024-01-10T12:00:00Z",
      "status": "winning"
    },
    {
      "id": "2",
      "item_id": "1",
      "bidder_id": "3",
      "bidder_name": "Bob Wilson",
      "amount": 275000,
      "timestamp": "2024-01-10T11:00:00Z",
      "status": "outbid"
    }
  ]
}
```

#### Get My Bids

```http
GET /bids/my-bids
```

**Headers:**
```
Authorization: Bearer <token>
```

**Response (200):**
```json
{
  "success": true,
  "bids": [
    {
      "id": "1",
      "item_id": "1",
      "bidder_id": "1",
      "bidder_name": "John Doe",
      "amount": 280000,
      "timestamp": "2024-01-10T12:00:00Z",
      "status": "winning",
      "item_title": "Beautiful Family Home",
      "item_image": "https://example.com/images/house1.jpg"
    }
  ]
}
```

### Watchlist

#### Add to Watchlist

```http
POST /watchlist
```

**Headers:**
```
Authorization: Bearer <token>
```

**Request Body:**
```json
{
  "item_id": "1"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Item added to watchlist"
}
```

#### Get Watchlist

```http
GET /watchlist
```

**Headers:**
```
Authorization: Bearer <token>
```

**Response (200):**
```json
{
  "success": true,
  "items": [
    {
      "id": "1",
      "title": "Beautiful Family Home",
      "description": "A stunning 4-bedroom house...",
      "starting_price": 250000,
      "current_price": 275000,
      "end_time": "2024-01-15T23:59:59Z",
      "status": "active",
      "images": ["https://example.com/images/house1.jpg"],
      "location": "New York, NY",
      "bid_count": 15,
      "is_favorite": true
    }
  ]
}
```

#### Remove from Watchlist

```http
DELETE /watchlist/:itemId
```

**Headers:**
```
Authorization: Bearer <token>
```

**Response (200):**
```json
{
  "success": true,
  "message": "Item removed from watchlist"
}
```

### Notifications

#### Get Notifications

```http
GET /notifications
```

**Headers:**
```
Authorization: Bearer <token>
```

**Response (200):**
```json
{
  "success": true,
  "notifications": [
    {
      "id": "1",
      "user_id": "1",
      "type": "outbid",
      "title": "You've been outbid!",
      "message": "Someone placed a higher bid on Beautiful Family Home",
      "item_id": "1",
      "item_title": "Beautiful Family Home",
      "is_read": false,
      "timestamp": "2024-01-10T13:00:00Z",
      "data": {
        "new_bid_amount": 285000
      }
    },
    {
      "id": "2",
      "user_id": "1",
      "type": "ending_soon",
      "title": "Auction ending soon",
      "message": "Beautiful Family Home auction ends in 1 hour",
      "item_id": "1",
      "item_title": "Beautiful Family Home",
      "is_read": true,
      "timestamp": "2024-01-10T10:00:00Z",
      "data": null
    }
  ]
}
```

#### Mark Notification as Read

```http
PUT /notifications/:id/read
```

**Headers:**
```
Authorization: Bearer <token>
```

**Response (200):**
```json
{
  "success": true,
  "message": "Notification marked as read"
}
```

## WebSocket Events

### Connection

```javascript
const ws = new WebSocket('ws://localhost:8080');
```

### Subscribe to Item Updates

**Send:**
```json
{
  "action": "subscribe",
  "event": "item_updates",
  "itemId": "1"
}
```

### Unsubscribe from Item Updates

**Send:**
```json
{
  "action": "unsubscribe",
  "event": "item_updates",
  "itemId": "1"
}
```

### Receive Bid Placed Event

**Receive:**
```json
{
  "type": "bid_placed",
  "itemId": "1",
  "currentPrice": 285000,
  "bidCount": 16,
  "bid": {
    "id": "3",
    "bidder_name": "Alice Johnson",
    "amount": 285000,
    "timestamp": "2024-01-10T14:00:00Z"
  }
}
```

### Receive Price Update Event

**Receive:**
```json
{
  "type": "price_update",
  "itemId": "1",
  "currentPrice": 285000,
  "bidCount": 16
}
```

## Error Responses

### 400 Bad Request

```json
{
  "success": false,
  "message": "Invalid request data",
  "errors": {
    "email": ["Email is required"],
    "password": ["Password must be at least 6 characters"]
  }
}
```

### 401 Unauthorized

```json
{
  "success": false,
  "message": "Unauthorized. Please login again."
}
```

### 403 Forbidden

```json
{
  "success": false,
  "message": "Access forbidden"
}
```

### 404 Not Found

```json
{
  "success": false,
  "message": "Resource not found"
}
```

### 422 Unprocessable Entity

```json
{
  "success": false,
  "message": "Bid amount must be higher than current price"
}
```

### 500 Internal Server Error

```json
{
  "success": false,
  "message": "Server error. Please try again later."
}
```

## Status Codes

- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Unprocessable Entity
- `500` - Internal Server Error

## Data Types

### User Roles
- `buyer` - Can place bids
- `seller` - Can list items

### Item Status
- `active` - Currently accepting bids
- `ended` - Auction has ended
- `pending` - Not yet started
- `cancelled` - Cancelled by seller

### Bid Status
- `active` - Bid is active
- `winning` - Currently winning bid
- `outbid` - Outbid by another user
- `won` - Won the auction
- `lost` - Lost the auction

### Notification Types
- `bid_placed` - New bid on your item
- `outbid` - You've been outbid
- `won` - You won an auction
- `ending_soon` - Auction ending soon
- `ended` - Auction has ended

## Rate Limiting

- 100 requests per minute per IP
- 1000 requests per hour per user

## CORS

The API should allow requests from:
- `http://localhost:*`
- Your production domain

## Security

- All passwords should be hashed (bcrypt recommended)
- JWT tokens should expire after 24 hours
- Use HTTPS in production
- Implement CSRF protection
- Validate all input data
- Sanitize user-generated content

## Testing

Use tools like:
- Postman
- Insomnia
- cURL
- Thunder Client (VS Code extension)

Example cURL request:
```bash
curl -X POST http://localhost:8000/api/users/login \
  -H "Content-Type: application/json" \
  -d '{"email":"john@example.com","password":"password123"}'
```
