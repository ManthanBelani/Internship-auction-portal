# Auction Portal Backend - Complete API Documentation

## 📡 Base URL
```
http://localhost:8000
```

---

## 📋 Table of Contents

1. [Health & Info](#health--info)
2. [User Management](#user-management)
3. [Item/Auction Management](#itemauction-management)
4. [Image Management](#image-management)
5. [Bidding System](#bidding-system)
6. [Transaction Management](#transaction-management)
7. [Reviews & Ratings](#reviews--ratings)
8. [Watchlist/Favorites](#watchlistfavorites)
9. [Auction Status & Dynamic Prices](#auction-status--dynamic-prices)
10. [WebSocket Real-Time Updates](#websocket-real-time-updates)
11. [Error Responses](#error-responses)
12. [Authentication](#authentication)

---

## 🏥 Health & Info

### Check API Health
```http
GET /health
```

**Response:**
```json
{
  "status": "ok",
  "message": "Auction Portal Backend is running"
}
```

### Get API Information
```http
GET /
```

**Response:**
```json
{
  "message": "Auction Portal API",
  "version": "1.0.0",
  "technology": "PHP + MySQL"
}
```

---

## 👥 User Management

### 1. Register New User
```http
POST /api/users/register
```

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "Password123!",
  "name": "John Doe"
}
```

**Validation Rules:**
- Email: Valid email format, unique
- Password: Minimum 8 characters
- Name: Required, non-empty

**Success Response (201):**
```json
{
  "userId": 1,
  "email": "user@example.com",
  "name": "John Doe",
  "registeredAt": "2026-02-01 10:30:00",
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
}
```

**Error Responses:**
- `400` - Invalid input (email format, short password, empty name)
- `409` - Email already exists

---

### 2. Login / Authenticate
```http
POST /api/users/login
```

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "Password123!"
}
```

**Success Response (200):**
```json
{
  "userId": 1,
  "email": "user@example.com",
  "name": "John Doe",
  "registeredAt": "2026-02-01 10:30:00",
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
}
```

**Error Responses:**
- `400` - Missing email or password
- `401` - Invalid credentials

---

### 3. Get User Profile
```http
GET /api/users/profile
```

**Headers:**
```
Authorization: Bearer {token}
```

**Success Response (200):**
```json
{
  "userId": 1,
  "email": "user@example.com",
  "name": "John Doe",
  "registeredAt": "2026-02-01 10:30:00"
}
```

**Error Responses:**
- `401` - Missing or invalid token
- `404` - User not found

---

### 4. Update User Profile
```http
PUT /api/users/profile
```

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "name": "John Updated",
  "email": "newemail@example.com"
}
```

**Success Response (200):**
```json
{
  "userId": 1,
  "email": "newemail@example.com",
  "name": "John Updated",
  "registeredAt": "2026-02-01 10:30:00"
}
```

**Error Responses:**
- `400` - Invalid input
- `401` - Unauthorized
- `409` - Email already exists

---

### 5. Get Public User Profile
```http
GET /api/users/:userId/public
```

**Example:**
```http
GET /api/users/5/public
```

**Success Response (200):**
```json
{
  "userId": 5,
  "name": "John Doe",
  "registeredAt": "2026-02-01 10:30:00"
}
```

**Note:** Email and password are excluded from public profiles.

**Error Responses:**
- `404` - User not found

---

## 🏷️ Item/Auction Management

### 1. Create Auction Item
```http
POST /api/items
```

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "title": "Vintage Camera",
  "description": "Rare vintage camera in excellent condition",
  "startingPrice": 100.00,
  "endTime": "2026-02-08 15:00:00"
}
```

**Validation Rules:**
- Title: Required, non-empty
- Description: Required, non-empty
- Starting Price: Positive number (> 0)
- End Time: Future date/time

**Success Response (201):**
```json
{
  "itemId": 1,
  "title": "Vintage Camera",
  "description": "Rare vintage camera in excellent condition",
  "startingPrice": 100.00,
  "currentPrice": 100.00,
  "endTime": "2026-02-08 15:00:00",
  "sellerId": 1,
  "sellerName": "John Doe",
  "status": "active",
  "createdAt": "2026-02-01 10:30:00",
  "reservePrice": 150.00,
  "reserveMet": false,
  "commissionRate": 0.05,
  "images": []
}
```

**Optional Fields:**
- `reservePrice` - Hidden minimum price (only visible to seller, null for others)
- `commissionRate` - Custom commission rate (default: 0.05 = 5%)

**Error Responses:**
- `400` - Invalid input (negative price, past date, empty fields)
- `401` - Unauthorized

---

### 2. Get All Active Items
```http
GET /api/items
```

**Query Parameters (Optional):**
- `search` - Search keyword (searches title and description)
- `sellerId` - Filter by seller ID

**Examples:**
```http
GET /api/items
GET /api/items?search=camera
GET /api/items?sellerId=5
GET /api/items?search=vintage&sellerId=5
```

**Success Response (200):**
```json
{
  "items": [
    {
      "itemId": 1,
      "title": "Vintage Camera",
      "description": "Rare vintage camera in excellent condition",
      "startingPrice": 100.00,
      "currentPrice": 250.00,
      "endTime": "2026-02-08 15:00:00",
      "sellerId": 1,
      "sellerName": "John Doe",
      "status": "active",
      "reserveMet": true,
      "images": [
        {
          "imageId": 1,
          "imageUrl": "/uploads/abc123.jpg",
          "thumbnailUrl": "/uploads/thumbnails/abc123_thumb.jpg"
        }
      ]
    },
    {
      "itemId": 2,
      "title": "Antique Watch",
      "description": "Beautiful antique watch",
      "startingPrice": 200.00,
      "currentPrice": 200.00,
      "endTime": "2026-02-09 12:00:00",
      "sellerId": 2,
      "sellerName": "Jane Smith",
      "status": "active",
      "reserveMet": false,
      "images": []
    }
  ]
}
```

**Note:** Reserve price is never shown in list view. Only `reserveMet` status is included.

---

### 3. Get Item Details
```http
GET /api/items/:itemId
```

**Example:**
```http
GET /api/items/1
```

**Success Response (200):**
```json
{
  "itemId": 1,
  "title": "Vintage Camera",
  "description": "Rare vintage camera in excellent condition",
  "startingPrice": 100.00,
  "currentPrice": 250.00,
  "endTime": "2026-02-08 15:00:00",
  "sellerId": 1,
  "sellerName": "John Doe",
  "status": "active",
  "highestBidderId": 5,
  "bidCount": 8,
  "createdAt": "2026-02-01 10:30:00",
  "reservePrice": null,
  "reserveMet": true,
  "commissionRate": 0.05,
  "images": [
    {
      "imageId": 1,
      "imageUrl": "/uploads/abc123.jpg",
      "thumbnailUrl": "/uploads/thumbnails/abc123_thumb.jpg",
      "uploadTimestamp": "2026-02-01 10:30:00"
    }
  ],
  "isWatching": false
}
```

**Enhanced Fields:**
- `reservePrice` - Only visible to the seller (null for other users)
- `reserveMet` - Boolean indicating if current bid meets reserve
- `commissionRate` - Commission rate for this item (default: 0.05)
- `images` - Array of uploaded images with thumbnails
- `isWatching` - Whether authenticated user is watching this item (false if not authenticated)

**Error Responses:**
- `404` - Item not found

---

## 🖼️ Image Management

### 1. Upload Image for Item
```http
POST /api/items/:itemId/images
```

**Headers:**
```
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**Form Data:**
- `image` - Image file (JPG, JPEG, PNG, or WEBP)

**Example:**
```http
POST /api/items/1/images
```

**Validation Rules:**
- File format: Must be JPG, JPEG, PNG, or WEBP
- File size: Maximum 5MB (configurable)
- MIME type: Must match file extension
- Authorization: User must be the seller of the item

**Success Response (201):**
```json
{
  "imageId": 1,
  "imageUrl": "/uploads/abc123def456.jpg",
  "thumbnailUrl": "/uploads/thumbnails/abc123def456_thumb.jpg",
  "message": "Image uploaded successfully"
}
```

**Error Responses:**
- `400` - Invalid file format, file too large, or validation error
- `401` - Unauthorized (missing or invalid token)
- `403` - Forbidden (not the seller of this item)
- `404` - Item not found

**cURL Example:**
```bash
curl -X POST http://localhost:8000/api/items/1/images \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "image=@/path/to/image.jpg"
```

---

### 2. Get All Images for Item
```http
GET /api/items/:itemId/images
```

**Example:**
```http
GET /api/items/1/images
```

**Success Response (200):**
```json
{
  "itemId": 1,
  "images": [
    {
      "imageId": 1,
      "imageUrl": "/uploads/abc123def456.jpg",
      "thumbnailUrl": "/uploads/thumbnails/abc123def456_thumb.jpg",
      "uploadTimestamp": "2026-02-01 10:30:00"
    },
    {
      "imageId": 2,
      "imageUrl": "/uploads/xyz789ghi012.jpg",
      "thumbnailUrl": "/uploads/thumbnails/xyz789ghi012_thumb.jpg",
      "uploadTimestamp": "2026-02-01 10:35:00"
    }
  ],
  "count": 2
}
```

**Note:** This is a public endpoint - no authentication required.

**Error Responses:**
- `404` - Item not found

---

### 3. Delete Image
```http
DELETE /api/images/:imageId
```

**Headers:**
```
Authorization: Bearer {token}
```

**Example:**
```http
DELETE /api/images/1
```

**Validation Rules:**
- Authorization: User must be the seller of the item associated with the image

**Success Response (200):**
```json
{
  "message": "Image deleted successfully"
}
```

**Error Responses:**
- `401` - Unauthorized (missing or invalid token)
- `403` - Forbidden (not the seller of this item)
- `404` - Image not found

**cURL Example:**
```bash
curl -X DELETE http://localhost:8000/api/images/1 \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## 💰 Bidding System

### 1. Place Bid
```http
POST /api/bids
```

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "itemId": 1,
  "amount": 250.00
}
```

**Validation Rules:**
- Amount: Must be positive
- Amount: Must be higher than current price
- Auction: Must be active
- Auction: Must not be expired
- Bidder: Cannot be the seller

**Success Response (201):**
```json
{
  "bidId": 15,
  "itemId": 1,
  "bidderId": 5,
  "bidderName": "John Doe",
  "amount": 250.00,
  "timestamp": "2026-02-01 14:30:00"
}
```

**Error Responses:**
- `400` - Invalid bid (too low, negative, expired auction)
- `401` - Unauthorized
- `403` - Self-bidding not allowed

---

### 2. Get Bid History
```http
GET /api/bids/:itemId
```

**Example:**
```http
GET /api/bids/1
```

**Success Response (200):**
```json
{
  "bids": [
    {
      "bidId": 15,
      "bidderId": 5,
      "bidderName": "John Doe",
      "amount": 250.00,
      "timestamp": "2026-02-01 14:30:00"
    },
    {
      "bidId": 14,
      "bidderId": 3,
      "bidderName": "Jane Smith",
      "amount": 200.00,
      "timestamp": "2026-02-01 14:15:00"
    },
    {
      "bidId": 13,
      "bidderId": 5,
      "bidderName": "John Doe",
      "amount": 150.00,
      "timestamp": "2026-02-01 14:00:00"
    }
  ]
}
```

**Note:** Bids are ordered by timestamp (most recent first).

**Error Responses:**
- `404` - Item not found

---

## 📊 Transaction Management

### 1. Get User Transactions
```http
GET /api/transactions
```

**Headers:**
```
Authorization: Bearer {token}
```

**Success Response (200):**
```json
{
  "transactions": [
    {
      "transactionId": 1,
      "itemId": 1,
      "itemTitle": "Vintage Camera",
      "sellerId": 2,
      "sellerName": "Jane Smith",
      "buyerId": 5,
      "buyerName": "John Doe",
      "finalPrice": 250.00,
      "commissionRate": 0.05,
      "commissionAmount": 12.50,
      "sellerPayout": 237.50,
      "completedAt": "2026-02-01 15:00:00"
    },
    {
      "transactionId": 2,
      "itemId": 3,
      "itemTitle": "Antique Watch",
      "sellerId": 5,
      "sellerName": "John Doe",
      "buyerId": 7,
      "buyerName": "Bob Wilson",
      "finalPrice": 180.00,
      "commissionRate": 0.05,
      "commissionAmount": 9.00,
      "sellerPayout": 171.00,
      "completedAt": "2026-02-01 14:30:00"
    }
  ]
}
```

**Enhanced Fields:**
- `commissionRate` - Commission rate applied (e.g., 0.05 = 5%)
- `commissionAmount` - Platform commission in currency
- `sellerPayout` - Amount seller receives (finalPrice - commissionAmount)

**Note:** Returns transactions where user is either buyer or seller.

**Error Responses:**
- `401` - Unauthorized

---

### 2. Get Transaction Details
```http
GET /api/transactions/:transactionId
```

**Headers:**
```
Authorization: Bearer {token}
```

**Example:**
```http
GET /api/transactions/1
```

**Success Response (200):**
```json
{
  "transactionId": 1,
  "itemId": 1,
  "itemTitle": "Vintage Camera",
  "sellerId": 2,
  "sellerName": "Jane Smith",
  "buyerId": 5,
  "buyerName": "John Doe",
  "finalPrice": 250.00,
  "commissionRate": 0.05,
  "commissionAmount": 12.50,
  "sellerPayout": 237.50,
  "completedAt": "2026-02-01 15:00:00"
}
```

**Commission Breakdown:**
- `finalPrice` - Total sale price
- `commissionRate` - Platform commission rate (e.g., 0.05 = 5%)
- `commissionAmount` - Platform fee (finalPrice × commissionRate)
- `sellerPayout` - Amount seller receives (finalPrice - commissionAmount)

**Error Responses:**
- `401` - Unauthorized
- `404` - Transaction not found

---

## ⭐ Reviews & Ratings

### 1. Create Review
```http
POST /api/reviews
```

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "transactionId": 1,
  "revieweeId": 2,
  "rating": 5,
  "reviewText": "Great seller! Item exactly as described."
}
```

**Validation Rules:**
- Rating: Must be between 1 and 5 (integer)
- Transaction: Must exist and user must be part of it (buyer or seller)
- Duplicate: User can only review each transaction once
- Reviewee: Must be the other party in the transaction

**Success Response (201):**
```json
{
  "reviewId": 1,
  "transactionId": 1,
  "reviewerId": 5,
  "reviewerName": "John Doe",
  "revieweeId": 2,
  "rating": 5,
  "reviewText": "Great seller! Item exactly as described.",
  "createdAt": "2026-02-01 16:00:00",
  "message": "Review created successfully"
}
```

**Error Responses:**
- `400` - Invalid rating (not 1-5), missing required fields
- `401` - Unauthorized (missing or invalid token)
- `403` - Cannot review this transaction (not a participant)
- `404` - Transaction not found
- `409` - Duplicate review (already reviewed this transaction)

**cURL Example:**
```bash
curl -X POST http://localhost:8000/api/reviews \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "transactionId": 1,
    "revieweeId": 2,
    "rating": 5,
    "reviewText": "Great seller!"
  }'
```

---

### 2. Get User Reviews
```http
GET /api/users/:userId/reviews
```

**Example:**
```http
GET /api/users/2/reviews
```

**Success Response (200):**
```json
{
  "userId": 2,
  "reviews": [
    {
      "reviewId": 1,
      "transactionId": 1,
      "reviewerId": 5,
      "reviewerName": "John Doe",
      "rating": 5,
      "reviewText": "Great seller! Item exactly as described.",
      "createdAt": "2026-02-01 16:00:00"
    },
    {
      "reviewId": 3,
      "transactionId": 5,
      "reviewerId": 8,
      "reviewerName": "Alice Brown",
      "rating": 4,
      "reviewText": "Good communication, fast shipping.",
      "createdAt": "2026-02-01 14:30:00"
    }
  ],
  "totalReviews": 2
}
```

**Note:** This is a public endpoint - no authentication required. Reviews are ordered by most recent first.

**Error Responses:**
- `404` - User not found

---

### 3. Get User Average Rating
```http
GET /api/users/:userId/rating
```

**Example:**
```http
GET /api/users/2/rating
```

**Success Response (200):**
```json
{
  "userId": 2,
  "userName": "Jane Smith",
  "averageRating": 4.7,
  "totalReviews": 23,
  "ratingBreakdown": {
    "5stars": 18,
    "4stars": 4,
    "3stars": 1,
    "2stars": 0,
    "1star": 0
  }
}
```

**Note:** This is a public endpoint - no authentication required. Average rating is rounded to one decimal place.

**Error Responses:**
- `404` - User not found

---

## 💝 Watchlist/Favorites

### 1. Add Item to Watchlist
```http
POST /api/watchlist
```

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "itemId": 1
}
```

**Validation Rules:**
- Item: Must exist and be active
- Duplicate: Cannot add same item twice to watchlist

**Success Response (201):**
```json
{
  "watchlistId": 1,
  "userId": 5,
  "itemId": 1,
  "addedAt": "2026-02-01 16:00:00",
  "message": "Item added to watchlist"
}
```

**Error Responses:**
- `400` - Invalid item ID or missing required fields
- `401` - Unauthorized (missing or invalid token)
- `404` - Item not found
- `409` - Item already in watchlist

**cURL Example:**
```bash
curl -X POST http://localhost:8000/api/watchlist \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"itemId": 1}'
```

---

### 2. Remove Item from Watchlist
```http
DELETE /api/watchlist/:itemId
```

**Headers:**
```
Authorization: Bearer {token}
```

**Example:**
```http
DELETE /api/watchlist/1
```

**Success Response (200):**
```json
{
  "message": "Item removed from watchlist"
}
```

**Error Responses:**
- `401` - Unauthorized (missing or invalid token)
- `404` - Item not in watchlist

**cURL Example:**
```bash
curl -X DELETE http://localhost:8000/api/watchlist/1 \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

### 3. Get User's Watchlist
```http
GET /api/watchlist
```

**Headers:**
```
Authorization: Bearer {token}
```

**Success Response (200):**
```json
{
  "watchlist": [
    {
      "watchlistId": 1,
      "itemId": 1,
      "title": "Vintage Camera",
      "currentPrice": 250.00,
      "endTime": "2026-02-08 15:00:00",
      "status": "active",
      "reserveMet": true,
      "addedAt": "2026-02-01 16:00:00",
      "images": [
        {
          "imageId": 1,
          "thumbnailUrl": "/uploads/thumbnails/abc123_thumb.jpg"
        }
      ],
      "timeRemaining": {
        "expired": false,
        "seconds": 604800,
        "formatted": "7d"
      }
    }
  ],
  "count": 1
}
```

**Note:** Items are ordered by most recently added first.

**Error Responses:**
- `401` - Unauthorized (missing or invalid token)

---

### 4. Check if Watching Item
```http
GET /api/watchlist/check/:itemId
```

**Headers:**
```
Authorization: Bearer {token}
```

**Example:**
```http
GET /api/watchlist/check/1
```

**Success Response (200):**
```json
{
  "itemId": 1,
  "isWatching": true
}
```

**Error Responses:**
- `401` - Unauthorized (missing or invalid token)
- `404` - Item not found

---

## 🔄 Auction Status & Dynamic Prices

### 1. Get Real-Time Auction Status
```http
GET /api/auction-status/:itemId
```

**Example:**
```http
GET /api/auction-status/1
```

**Use Case:** Perfect for polling every 3-5 seconds for real-time updates

**Success Response (200):**
```json
{
  "itemId": 1,
  "title": "Vintage Camera",
  "status": "active",
  "currentPrice": 250.00,
  "startingPrice": 100.00,
  "highestBidderId": 5,
  "bidCount": 8,
  "endTime": "2026-02-08 15:00:00",
  "timeRemaining": {
    "expired": false,
    "seconds": 604800,
    "days": 7,
    "hours": 0,
    "minutes": 0,
    "formatted": "7d"
  },
  "isActive": true,
  "latestBids": [
    {
      "bidId": 15,
      "bidderId": 5,
      "bidderName": "John Doe",
      "amount": 250.00,
      "timestamp": "2026-02-01 14:30:00"
    }
  ],
  "priceIncrease": 150.00,
  "priceIncreasePercentage": 150.00,
  "timestamp": "2026-02-01 15:00:00"
}
```

**Error Responses:**
- `404` - Item not found

---

### 2. Get Multiple Auction Statuses
```http
GET /api/auction-status/multiple?itemIds=1,2,3
```

**Query Parameters:**
- `itemIds` - Comma-separated list of item IDs

**Use Case:** Dashboard showing multiple auctions

**Success Response (200):**
```json
{
  "items": [
    {
      "itemId": 1,
      "currentPrice": 250.00,
      "bidCount": 8,
      "status": "active",
      "timeRemaining": {
        "expired": false,
        "seconds": 604800,
        "formatted": "7d"
      },
      "isActive": true
    },
    {
      "itemId": 2,
      "currentPrice": 180.00,
      "bidCount": 5,
      "status": "active",
      "timeRemaining": {
        "expired": false,
        "seconds": 432000,
        "formatted": "5d"
      },
      "isActive": true
    }
  ],
  "timestamp": "2026-02-01 15:00:00"
}
```

**Error Responses:**
- `400` - Missing itemIds parameter

---

### 3. Get Price History
```http
GET /api/price-history/:itemId
```

**Example:**
```http
GET /api/price-history/1
```

**Use Case:** Display price progression chart

**Success Response (200):**
```json
{
  "itemId": 1,
  "title": "Vintage Camera",
  "currentPrice": 250.00,
  "priceHistory": [
    {
      "timestamp": "2026-02-01 10:00:00",
      "price": 100.00,
      "type": "starting_price",
      "bidderName": null
    },
    {
      "timestamp": "2026-02-01 12:00:00",
      "price": 150.00,
      "type": "bid",
      "bidderName": "John Doe"
    },
    {
      "timestamp": "2026-02-01 13:00:00",
      "price": 200.00,
      "type": "bid",
      "bidderName": "Jane Smith"
    },
    {
      "timestamp": "2026-02-01 14:30:00",
      "price": 250.00,
      "type": "bid",
      "bidderName": "John Doe"
    }
  ],
  "totalBids": 3
}
```

**Error Responses:**
- `404` - Item not found

---

## 🔌 WebSocket Real-Time Updates

### Connection

**WebSocket URL:**
```
ws://localhost:8080
```

**Authentication:**
WebSocket connections require JWT authentication via query parameter:
```
ws://localhost:8080?token=YOUR_JWT_TOKEN
```

### Message Format

All messages are JSON formatted.

#### Client to Server Messages

**Subscribe to Item Updates:**
```json
{
  "action": "subscribe",
  "itemId": 1
}
```

**Unsubscribe from Item Updates:**
```json
{
  "action": "unsubscribe",
  "itemId": 1
}
```

#### Server to Client Messages

**Bid Update Notification:**
```json
{
  "type": "bid_update",
  "itemId": 1,
  "bidAmount": 250.00,
  "bidderId": 5,
  "bidderName": "John Doe",
  "timestamp": "2026-02-01 14:30:00",
  "reserveMet": true,
  "bidCount": 8
}
```

**Outbid Notification:**
```json
{
  "type": "outbid",
  "itemId": 1,
  "newBidAmount": 250.00,
  "yourBidAmount": 240.00,
  "timestamp": "2026-02-01 14:30:00"
}
```

**Auction Ending Soon:**
```json
{
  "type": "auction_ending",
  "itemId": 1,
  "secondsRemaining": 180,
  "currentPrice": 250.00,
  "timestamp": "2026-02-01 14:57:00"
}
```

**Auction Ended:**
```json
{
  "type": "auction_ended",
  "itemId": 1,
  "finalPrice": 250.00,
  "winnerId": 5,
  "winnerName": "John Doe",
  "reserveMet": true,
  "timestamp": "2026-02-01 15:00:00"
}
```

**Connection Acknowledged:**
```json
{
  "type": "connected",
  "userId": 5,
  "message": "WebSocket connection established"
}
```

**Error Message:**
```json
{
  "type": "error",
  "message": "Invalid action or parameters"
}
```

### JavaScript Example

```javascript
// Connect to WebSocket server
const token = 'YOUR_JWT_TOKEN';
const ws = new WebSocket(`ws://localhost:8080?token=${token}`);

// Handle connection open
ws.onopen = () => {
  console.log('Connected to WebSocket server');
  
  // Subscribe to item updates
  ws.send(JSON.stringify({
    action: 'subscribe',
    itemId: 1
  }));
};

// Handle incoming messages
ws.onmessage = (event) => {
  const data = JSON.parse(event.data);
  
  switch(data.type) {
    case 'connected':
      console.log('Connection acknowledged:', data.message);
      break;
      
    case 'bid_update':
      console.log(`New bid on item ${data.itemId}: $${data.bidAmount}`);
      // Update UI with new bid information
      break;
      
    case 'outbid':
      console.log(`You've been outbid! New bid: $${data.newBidAmount}`);
      // Show notification to user
      break;
      
    case 'auction_ending':
      console.log(`Auction ending in ${data.secondsRemaining} seconds`);
      // Show countdown timer
      break;
      
    case 'auction_ended':
      console.log(`Auction ended. Final price: $${data.finalPrice}`);
      // Update UI to show auction ended
      break;
      
    case 'error':
      console.error('WebSocket error:', data.message);
      break;
  }
};

// Handle connection close
ws.onclose = () => {
  console.log('Disconnected from WebSocket server');
  // Implement reconnection logic
};

// Handle errors
ws.onerror = (error) => {
  console.error('WebSocket error:', error);
};

// Unsubscribe when done
function unsubscribe(itemId) {
  ws.send(JSON.stringify({
    action: 'unsubscribe',
    itemId: itemId
  }));
}
```

### Features

- **Real-Time Bid Updates**: Receive instant notifications when new bids are placed
- **Outbid Alerts**: Get notified immediately when someone outbids you
- **Auction Countdown**: Receive updates every 30 seconds for auctions ending within 5 minutes
- **Automatic Reconnection**: Client should implement reconnection logic for reliability
- **Subscription-Based**: Only receive updates for items you're subscribed to
- **JWT Authentication**: Secure connections using existing authentication tokens

### Notes

- WebSocket server runs on port 8080 by default (configurable via `WS_PORT` in `.env`)
- Connection requires valid JWT token from login/register
- Multiple items can be subscribed to simultaneously
- Subscriptions are automatically cleaned up on disconnect
- Server broadcasts to all subscribed clients for each event

---

## ❌ Error Responses

All error responses follow this format:

```json
{
  "error": {
    "code": "ERROR_CODE",
    "message": "Human-readable error message"
  }
}
```

### Common Error Codes

| HTTP Status | Error Code | Description |
|------------|------------|-------------|
| 400 | `BAD_REQUEST` | Invalid input or validation error |
| 400 | `INVALID_INPUT` | Missing or invalid required fields |
| 400 | `INVALID_BID` | Bid amount too low or invalid |
| 400 | `INVALID_PRICE` | Price must be positive |
| 400 | `INVALID_RATING` | Rating must be between 1 and 5 |
| 400 | `INVALID_FILE_FORMAT` | File format not supported (must be JPG, PNG, or WEBP) |
| 400 | `FILE_TOO_LARGE` | File exceeds maximum size limit (5MB) |
| 400 | `PAST_END_TIME` | End time must be in future |
| 401 | `UNAUTHORIZED` | Missing or invalid authentication token |
| 401 | `MISSING_TOKEN` | No authentication token provided |
| 401 | `INVALID_TOKEN` | Token is invalid or expired |
| 401 | `INVALID_CREDENTIALS` | Email or password incorrect |
| 403 | `FORBIDDEN` | Action not allowed |
| 403 | `SELF_BIDDING_NOT_ALLOWED` | Cannot bid on own item |
| 403 | `UNAUTHORIZED_MODIFICATION` | Cannot modify another user's data |
| 403 | `NOT_SELLER` | Only the seller can perform this action |
| 403 | `CANNOT_REVIEW` | Cannot review this transaction |
| 404 | `NOT_FOUND` | Resource not found |
| 404 | `USER_NOT_FOUND` | User does not exist |
| 404 | `ITEM_NOT_FOUND` | Item does not exist |
| 404 | `TRANSACTION_NOT_FOUND` | Transaction does not exist |
| 404 | `IMAGE_NOT_FOUND` | Image does not exist |
| 404 | `REVIEW_NOT_FOUND` | Review does not exist |
| 409 | `EMAIL_ALREADY_EXISTS` | Email is already registered |
| 409 | `DUPLICATE_REVIEW` | Already reviewed this transaction |
| 409 | `DUPLICATE_WATCHLIST` | Item already in watchlist |
| 500 | `INTERNAL_ERROR` | Server error |
| 500 | `DATABASE_ERROR` | Database operation failed |
| 500 | `FILE_UPLOAD_ERROR` | Failed to upload or process file |

---

## 🔐 Authentication

### JWT Token Authentication

Most endpoints require authentication using JWT (JSON Web Token).

**How to Authenticate:**

1. **Register or Login** to get a token:
```http
POST /api/users/register
POST /api/users/login
```

2. **Include token in requests:**
```http
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

3. **Token expires after 7 days** (configurable via `JWT_EXPIRES_IN` env variable)

### Protected Endpoints

The following endpoints require authentication:

**User Management:**
- `GET /api/users/profile`
- `PUT /api/users/profile`

**Item Management:**
- `POST /api/items`

**Image Management:**
- `POST /api/items/:itemId/images`
- `DELETE /api/images/:imageId`

**Bidding:**
- `POST /api/bids`

**Transactions:**
- `GET /api/transactions`
- `GET /api/transactions/:transactionId`

**Reviews:**
- `POST /api/reviews`

**Watchlist:**
- `POST /api/watchlist`
- `DELETE /api/watchlist/:itemId`
- `GET /api/watchlist`
- `GET /api/watchlist/check/:itemId`

### Public Endpoints

These endpoints do NOT require authentication:

- `GET /health`
- `GET /`
- `GET /api/items`
- `GET /api/items/:itemId`
- `GET /api/items/:itemId/images`
- `GET /api/bids/:itemId`
- `GET /api/users/:userId/public`
- `GET /api/users/:userId/reviews`
- `GET /api/users/:userId/rating`
- `GET /api/auction-status/:itemId`
- `GET /api/auction-status/multiple`
- `GET /api/price-history/:itemId`
- `POST /api/users/register`
- `POST /api/users/login`

---

## 📝 Request/Response Format

### Content Type
All requests and responses use JSON format:
```
Content-Type: application/json
```

### CORS
CORS is enabled for all origins:
```
Access-Control-Allow-Origin: *
Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS
Access-Control-Allow-Headers: Content-Type, Authorization
```

---

## 🚀 Quick Start Examples

### Example 1: Complete User Flow

```javascript
// 1. Register
const register = await fetch('http://localhost:8000/api/users/register', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    email: 'user@example.com',
    password: 'Password123!',
    name: 'John Doe'
  })
});
const { token } = await register.json();

// 2. Create Item
const item = await fetch('http://localhost:8000/api/items', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${token}`
  },
  body: JSON.stringify({
    title: 'Vintage Camera',
    description: 'Rare camera',
    startingPrice: 100.00,
    endTime: '2026-02-08 15:00:00'
  })
});

// 3. Get Active Items
const items = await fetch('http://localhost:8000/api/items');
const data = await items.json();
```

### Example 2: Bidding Flow

```javascript
// 1. Login
const login = await fetch('http://localhost:8000/api/users/login', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    email: 'bidder@example.com',
    password: 'Password123!'
  })
});
const { token } = await login.json();

// 2. Place Bid
const bid = await fetch('http://localhost:8000/api/bids', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${token}`
  },
  body: JSON.stringify({
    itemId: 1,
    amount: 150.00
  })
});

// 3. Check Auction Status
const status = await fetch('http://localhost:8000/api/auction-status/1');
const auctionData = await status.json();
console.log(`Current Price: $${auctionData.currentPrice}`);
```

### Example 3: Real-Time Price Monitoring

```javascript
// Poll for updates every 5 seconds
setInterval(async () => {
  const response = await fetch('http://localhost:8000/api/auction-status/1');
  const data = await response.json();
  
  console.log(`Current Price: $${data.currentPrice}`);
  console.log(`Bid Count: ${data.bidCount}`);
  console.log(`Time Remaining: ${data.timeRemaining.formatted}`);
}, 5000);
```

---

## 📊 Summary

**Total Endpoints:** 30+

- **Health/Info:** 2 endpoints
- **User Management:** 5 endpoints
- **Item Management:** 3 endpoints
- **Image Management:** 3 endpoints
- **Bidding:** 2 endpoints
- **Transactions:** 2 endpoints
- **Reviews & Ratings:** 3 endpoints
- **Watchlist/Favorites:** 4 endpoints
- **Auction Status:** 3 endpoints
- **WebSocket:** Real-time updates

**Base URL:** `http://localhost:8000`  
**WebSocket URL:** `ws://localhost:8080`  
**Format:** JSON  
**Authentication:** JWT Bearer Token  
**CORS:** Enabled for all origins  

**New Features:**
- ✅ Multi-image upload with thumbnails
- ✅ User ratings and reviews (1-5 stars)
- ✅ Watchlist/favorites functionality
- ✅ Commission/fee system (default 5%)
- ✅ Reserve price for auctions
- ✅ Real-time WebSocket updates

---

**Last Updated:** February 1, 2026  
**API Version:** 2.0.0  
**Technology:** PHP 8.1+ | MySQL | JWT | Ratchet WebSocket
