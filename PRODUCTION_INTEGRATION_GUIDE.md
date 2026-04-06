# BidOrbit - Production Integration Guide

## Overview

This document provides a comprehensive guide for integrating the BidOrbit Flutter mobile app with the PHP backend API for production deployment.

## Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                    BidOrbit Architecture                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌──────────────────┐         ┌──────────────────────────┐     │
│  │   Flutter App    │ ◄─────► │    PHP Backend API       │     │
│  │   (BidOrbit)     │  HTTP   │    (Port 8000)           │     │
│  │                  │  REST   │                          │     │
│  │  - User Screens  │         │  - Controllers           │     │
│  │  - Seller Screen │         │  - Services              │     │
│  │  - Providers     │         │  - Models                │     │
│  └────────┬─────────┘         └────────────┬─────────────┘     │
│           │                                │                    │
│           │ WebSocket                      │                    │
│           ▼                                ▼                    │
│  ┌──────────────────┐         ┌──────────────────────────┐     │
│  │  WebSocket Svc   │ ◄─────► │   WebSocket Server       │     │
│  │  (Real-time)     │   WS    │   (Port 8081)            │     │
│  │                  │         │                          │     │
│  │  - Bid Updates   │         │  - Auction Events        │     │
│  │  - Notifications │         │  - Real-time Bids        │     │
│  └──────────────────┘         └──────────────────────────┘     │
│                                                                  │
│                       ┌──────────────────┐                      │
│                       │    Database      │                      │
│                       │   (SQLite/MySQL) │                      │
│                       └──────────────────┘                      │
└─────────────────────────────────────────────────────────────────┘
```

## Backend API Endpoints

### Authentication
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/users/register` | Register new user |
| POST | `/api/users/login` | User login |
| POST | `/api/users/refresh` | Refresh access token |
| GET | `/api/users/profile` | Get user profile |
| PUT | `/api/users/profile` | Update user profile |

### Items/Auctions
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/items` | Get all items (with pagination) |
| GET | `/api/items/{id}` | Get single item |
| POST | `/api/items` | Create new item (seller) |
| PUT | `/api/seller/items/{id}` | Update item (seller) |
| GET | `/api/items/{id}/images` | Get item images |
| POST | `/api/items/{id}/images` | Upload item images |
| POST | `/api/seller/items/{id}/images/bulk` | Bulk upload images |

### Bidding
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/bids` | Place a bid |
| GET | `/api/bids/{itemId}` | Get bid history |
| GET | `/api/my/bids` | Get user's bids |

### Watchlist
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/watchlist` | Get user's watchlist |
| POST | `/api/watchlist` | Add to watchlist |
| DELETE | `/api/watchlist/{itemId}` | Remove from watchlist |
| GET | `/api/watchlist/check/{itemId}` | Check if watching |

### Seller Endpoints
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/seller/stats` | Get seller dashboard stats |
| GET | `/api/seller/listings` | Get seller's inventory |
| GET | `/api/seller/sales` | Get seller's sales |
| GET | `/api/seller/analytics/*` | Analytics endpoints |
| POST | `/api/seller/payouts` | Request payout |
| GET | `/api/seller/messages` | Get messages |

### Payment & Orders
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/payments/methods` | Get payment methods |
| POST | `/api/payments/methods` | Add payment method |
| POST | `/api/payments/create-intent` | Create payment intent |
| POST | `/api/payments/confirm` | Confirm payment |
| GET | `/api/orders` | Get user's orders |
| POST | `/api/orders/create` | Create order |
| GET | `/api/orders/won-items` | Get won items |

### Shipping
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/shipping/addresses` | Get addresses |
| POST | `/api/shipping/addresses` | Add address |
| PUT | `/api/shipping/addresses/{id}` | Update address |
| DELETE | `/api/shipping/addresses/{id}` | Delete address |

## WebSocket Events

### Client → Server
```json
// Subscribe to item updates
{
  "action": "subscribe",
  "itemId": 123
}

// Unsubscribe from item
{
  "action": "unsubscribe",
  "itemId": 123
}
```

### Server → Client
```json
// Bid update
{
  "type": "bid_update",
  "itemId": 123,
  "bidAmount": 150.00,
  "bidderId": 456,
  "bidderName": "John D.",
  "timestamp": 1709876543,
  "reserveMet": true
}

// Auction status
{
  "type": "auction_status",
  "itemId": 123,
  "status": "ended",
  "winnerId": 456
}

// Notification
{
  "type": "notification",
  "notificationType": "outbid",
  "itemId": 123,
  "message": "You have been outbid!"
}
```

## Environment Configuration

### Flutter App (lib/config/env_config.dart)

The app supports three environments:

1. **Development** (default)
   - API: `http://10.205.162.238:8000/api`
   - WebSocket: `ws://10.205.162.238:8081`

2. **Staging**
   - API: `https://staging-api.bidorbit.com/api`
   - WebSocket: `wss://staging-ws.bidorbit.com`

3. **Production**
   - API: `https://api.bidorbit.com/api`
   - WebSocket: `wss://ws.bidorbit.com`

#### Building for Different Environments

```bash
# Development (default)
flutter run

# Staging
flutter build apk --dart-define=ENVIRONMENT=staging

# Production
flutter build apk --dart-define=ENVIRONMENT=production \
  --dart-define=API_BASE_URL=https://api.bidorbit.com/api \
  --dart-define=WS_BASE_URL=wss://ws.bidorbit.com
```

### Backend (.env)

Copy `.env.example` to `.env` and configure:

```env
# Application
APP_ENV=production
APP_DEBUG=false

# Database
DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=bidorbit_prod
DB_USERNAME=bidorbit_user
DB_PASSWORD=your_secure_password

# JWT
JWT_SECRET=your_secure_random_string_min_64_chars
JWT_EXPIRES_IN=3600

# WebSocket
WS_HOST=0.0.0.0
WS_PORT=8081
```

## Authentication Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                    Authentication Flow                           │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  1. Login                                                        │
│     ┌────────┐    POST /login     ┌────────┐                    │
│     │  App   │ ──────────────────►│ Backend │                    │
│     │        │    email/password  │         │                    │
│     │        │                    │         │                    │
│     │        │◄────────────────── │         │                    │
│     │        │  token, refreshToken│        │                    │
│     └────────┘                    └────────┘                    │
│                                                                  │
│  2. API Request                                                  │
│     ┌────────┐    GET /items     ┌────────┐                    │
│     │  App   │ ──────────────────►│ Backend │                    │
│     │        │  Authorization:    │         │                    │
│     │        │  Bearer <token>    │         │                    │
│     │        │                    │         │                    │
│     │        │◄────────────────── │         │                    │
│     │        │      Response      │         │                    │
│     └────────┘                    └────────┘                    │
│                                                                  │
│  3. Token Refresh (on 401)                                       │
│     ┌────────┐    POST /refresh   ┌────────┐                    │
│     │  App   │ ──────────────────►│ Backend │                    │
│     │        │  {refreshToken}    │         │                    │
│     │        │                    │         │                    │
│     │        │◄────────────────── │         │                    │
│     │        │  new token         │         │                    │
│     └────────┘                    └────────┘                    │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

## Security Considerations

### 1. Token Storage
- Access tokens stored in `FlutterSecureStorage`
- Refresh tokens stored separately
- Tokens cleared on logout

### 2. API Security
- All authenticated endpoints require Bearer token
- CORS configured for allowed origins only
- Rate limiting: 100 requests/minute

### 3. WebSocket Security
- Token required in query string for authentication
- Connections without valid token are rejected
- Per-item subscription model

### 4. Input Validation
- All inputs validated on backend
- XSS protection enabled
- SQL injection prevention via prepared statements

## Error Handling

### API Error Response Format
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Invalid input data",
    "details": {}
  }
}
```

### Common Error Codes
| Code | HTTP Status | Description |
|------|-------------|-------------|
| UNAUTHORIZED | 401 | Token expired or invalid |
| FORBIDDEN | 403 | Insufficient permissions |
| NOT_FOUND | 404 | Resource not found |
| VALIDATION_ERROR | 422 | Invalid input data |
| SERVER_ERROR | 500 | Internal server error |

## Deployment Checklist

### Backend
- [ ] Configure `.env` for production
- [ ] Set `APP_DEBUG=false`
- [ ] Generate secure `JWT_SECRET`
- [ ] Configure MySQL database
- [ ] Set up SSL certificates
- [ ] Configure CORS origins
- [ ] Start PHP server: `php -S 0.0.0.0:8000 -t public`
- [ ] Start WebSocket server: `php websocket_server.php`

### Flutter App
- [ ] Update `env_config.dart` with production URLs
- [ ] Build release APK/IPA
- [ ] Test on multiple devices
- [ ] Verify WebSocket connectivity
- [ ] Test all API endpoints
- [ ] Verify push notifications

### Infrastructure
- [ ] Set up load balancer
- [ ] Configure SSL/TLS
- [ ] Set up database backups
- [ ] Configure monitoring
- [ ] Set up error tracking (Sentry)
- [ ] Configure CDN for images

## Testing the Integration

### 1. Test Authentication
```bash
# Register
curl -X POST http://localhost:8000/api/users/register \
  -H "Content-Type: application/json" \
  -d '{"email":"test@test.com","password":"password123","name":"Test User"}'

# Login
curl -X POST http://localhost:8000/api/users/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@test.com","password":"password123"}'
```

### 2. Test Items API
```bash
# Get items
curl http://localhost:8000/api/items

# Get single item
curl http://localhost:8000/api/items/1
```

### 3. Test WebSocket
```javascript
// Using JavaScript in browser console
const ws = new WebSocket('ws://localhost:8081?token=YOUR_TOKEN');
ws.onmessage = (e) => console.log(e.data);
ws.send(JSON.stringify({action: 'subscribe', itemId: 1}));
```

## Troubleshooting

### Common Issues

1. **Connection Refused**
   - Check if backend server is running
   - Verify IP address and port
   - Check firewall settings

2. **401 Unauthorized**
   - Token may have expired
   - Check token format: `Bearer <token>`
   - Try refreshing the token

3. **WebSocket Not Connecting**
   - Verify WebSocket server is running
   - Check token is valid
   - Verify URL format (ws:// vs wss://)

4. **CORS Errors**
   - Add app origin to CORS whitelist
   - Check preflight OPTIONS handling

## Support

For issues or questions:
- Check the API documentation in `/API_DOCUMENTATION.md`
- Review logs in `/logs/` directory
- Contact development team

---

**Version:** 1.0.0  
**Last Updated:** March 2026
