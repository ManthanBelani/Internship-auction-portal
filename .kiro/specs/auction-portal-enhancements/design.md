# Design Document: Auction Portal Enhancements

## Overview

This design extends an existing PHP/MySQL auction portal backend with six major feature enhancements: multi-image uploads with thumbnail generation, user ratings and reviews system, watchlist/favorites functionality, commission-based fee system, reserve pricing for auctions, and real-time WebSocket updates using Ratchet PHP.

The design maintains backward compatibility with existing RESTful API endpoints while adding new capabilities. All new features follow the existing architectural patterns using PDO with prepared statements, service layer abstraction, and controller-based routing.

## Architecture

### High-Level Architecture

The system follows a layered MVC architecture:

```
┌─────────────────────────────────────────────────────────┐
│                    API Layer (Controllers)               │
│  UserController | ItemController | BidController |       │
│  TransactionController | ImageController |               │
│  ReviewController | WatchlistController                  │
└─────────────────────────────────────────────────────────┘
                            │
┌─────────────────────────────────────────────────────────┐
│                   Service Layer                          │
│  UserService | ItemService | BidService |                │
│  TransactionService | ImageService | ReviewService |     │
│  WatchlistService | CommissionService                    │
└─────────────────────────────────────────────────────────┘
                            │
┌─────────────────────────────────────────────────────────┐
│                    Model Layer                           │
│  User | Item | Bid | Transaction | ItemImage |          │
│  Review | Watchlist                                      │
└─────────────────────────────────────────────────────────┘
                            │
┌─────────────────────────────────────────────────────────┐
│                  Database Layer (PDO)                    │
│  MySQL with prepared statements                          │
└─────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐
│              WebSocket Server (Separate Process)         │
│  Ratchet PHP - Real-time event broadcasting             │
└─────────────────────────────────────────────────────────┘
```

### New Components

1. **ImageService**: Handles file uploads, validation, thumbnail generation, and storage
2. **ReviewService**: Manages user ratings, reviews, and average rating calculations
3. **WatchlistService**: Manages user watchlists and notification triggers
4. **CommissionService**: Calculates fees and tracks platform earnings
5. **WebSocketServer**: Broadcasts real-time events using Ratchet PHP
6. **ImageController**: Exposes image upload and retrieval endpoints
7. **ReviewController**: Exposes rating and review endpoints
8. **WatchlistController**: Exposes watchlist management endpoints

## Components and Interfaces

### 1. Image Upload System

#### ImageService

```php
class ImageService {
    private PDO $db;
    private string $uploadDir = 'uploads/';
    private string $thumbnailDir = 'uploads/thumbnails/';
    private array $allowedFormats = ['jpg', 'jpeg', 'png', 'webp'];
    private int $maxFileSize = 5242880; // 5MB
    private int $thumbnailWidth = 200;
    private int $thumbnailHeight = 200;

    public function uploadImage(int $itemId, array $file): array
    public function generateThumbnail(string $originalPath, string $thumbnailPath): bool
    public function validateImage(array $file): bool
    public function getItemImages(int $itemId): array
    public function deleteImage(int $imageId): bool
    private function generateUniqueFilename(string $extension): string
    private function validateMimeType(string $filePath, string $extension): bool
}
```

**uploadImage**: Validates file, generates unique filename, stores original, creates thumbnail, inserts database record
**generateThumbnail**: Uses GD library to create resized version maintaining aspect ratio
**validateImage**: Checks extension, MIME type, and file size
**getItemImages**: Retrieves all images for an item with URLs
**deleteImage**: Removes files and database record

#### ImageController

```php
class ImageController {
    private ImageService $imageService;

    public function upload(Request $request): Response
    public function getImages(Request $request): Response
    public function delete(Request $request): Response
}
```

**Endpoints**:
- `POST /api/items/{itemId}/images` - Upload image
- `GET /api/items/{itemId}/images` - Get all images for item
- `DELETE /api/images/{imageId}` - Delete specific image

#### ItemImage Model

```php
class ItemImage {
    public int $imageId;
    public int $itemId;
    public string $imageUrl;
    public string $thumbnailUrl;
    public string $uploadTimestamp;

    public function toArray(): array
}
```

### 2. User Ratings and Reviews System

#### ReviewService

```php
class ReviewService {
    private PDO $db;

    public function createReview(int $transactionId, int $reviewerId, int $revieweeId, 
                                 int $rating, string $reviewText): int
    public function getReviewsForUser(int $userId): array
    public function calculateAverageRating(int $userId): float
    public function hasReviewed(int $transactionId, int $reviewerId): bool
    public function canReview(int $transactionId, int $userId): bool
    private function validateRating(int $rating): bool
}
```

**createReview**: Validates transaction, checks for duplicates, validates rating (1-5), inserts review
**getReviewsForUser**: Retrieves all reviews received by a user
**calculateAverageRating**: Computes mean of all ratings for a user
**hasReviewed**: Checks if user already reviewed this transaction
**canReview**: Verifies user is part of the transaction
**validateRating**: Ensures rating is between 1 and 5

#### ReviewController

```php
class ReviewController {
    private ReviewService $reviewService;

    public function create(Request $request): Response
    public function getUserReviews(Request $request): Response
    public function getUserRating(Request $request): Response
}
```

**Endpoints**:
- `POST /api/reviews` - Create review (requires transactionId, revieweeId, rating, reviewText)
- `GET /api/users/{userId}/reviews` - Get all reviews for user
- `GET /api/users/{userId}/rating` - Get average rating for user

#### Review Model

```php
class Review {
    public int $reviewId;
    public int $transactionId;
    public int $reviewerId;
    public int $revieweeId;
    public int $rating;
    public string $reviewText;
    public string $createdAt;

    public function toArray(): array
}
```

### 3. Watchlist/Favorites System

#### WatchlistService

```php
class WatchlistService {
    private PDO $db;

    public function addToWatchlist(int $userId, int $itemId): bool
    public function removeFromWatchlist(int $userId, int $itemId): bool
    public function getWatchlist(int $userId): array
    public function isWatching(int $userId, int $itemId): bool
    public function getEndingSoonItems(int $userId, int $hoursThreshold = 24): array
    private function isDuplicate(int $userId, int $itemId): bool
}
```

**addToWatchlist**: Checks for duplicates, creates watchlist entry
**removeFromWatchlist**: Deletes watchlist entry
**getWatchlist**: Returns all items in user's watchlist with item details
**isWatching**: Checks if user is watching specific item
**getEndingSoonItems**: Finds watched items ending within threshold for notifications
**isDuplicate**: Prevents duplicate watchlist entries

#### WatchlistController

```php
class WatchlistController {
    private WatchlistService $watchlistService;

    public function add(Request $request): Response
    public function remove(Request $request): Response
    public function getWatchlist(Request $request): Response
    public function checkWatching(Request $request): Response
}
```

**Endpoints**:
- `POST /api/watchlist` - Add item to watchlist (requires itemId)
- `DELETE /api/watchlist/{itemId}` - Remove item from watchlist
- `GET /api/watchlist` - Get user's watchlist
- `GET /api/watchlist/check/{itemId}` - Check if watching item

#### Watchlist Model

```php
class Watchlist {
    public int $watchlistId;
    public int $userId;
    public int $itemId;
    public string $addedAt;

    public function toArray(): array
}
```

### 4. Commission/Fee System

#### CommissionService

```php
class CommissionService {
    private PDO $db;
    private float $defaultCommissionRate = 0.05; // 5%

    public function calculateCommission(float $salePrice, float $commissionRate = null): float
    public function getCommissionRate(int $itemId): float
    public function setCommissionRate(int $itemId, float $rate): bool
    public function calculateSellerPayout(float $salePrice, float $commission): float
    public function getTotalPlatformEarnings(): float
    public function getEarningsByDateRange(string $startDate, string $endDate): float
}
```

**calculateCommission**: Multiplies sale price by commission rate
**getCommissionRate**: Retrieves custom rate or returns default
**setCommissionRate**: Sets custom commission rate for specific item
**calculateSellerPayout**: Subtracts commission from sale price
**getTotalPlatformEarnings**: Sums all commission amounts from transactions
**getEarningsByDateRange**: Aggregates earnings within date range

#### Updated TransactionService

```php
class TransactionService {
    private PDO $db;
    private CommissionService $commissionService;

    // Existing methods...
    
    public function createTransaction(int $itemId, int $winnerId, float $finalPrice): int
    public function getTransactionDetails(int $transactionId): array
    private function applyCommission(int $transactionId, float $finalPrice): void
}
```

**createTransaction**: Enhanced to calculate and store commission
**getTransactionDetails**: Enhanced to include commission breakdown
**applyCommission**: Calculates commission, updates transaction record

### 5. Reserve Price System

#### Updated ItemService

```php
class ItemService {
    private PDO $db;

    // Existing methods...
    
    public function setReservePrice(int $itemId, float $reservePrice): bool
    public function getReservePrice(int $itemId): ?float
    public function isReserveMet(int $itemId, float $currentBid): bool
    public function checkReserveStatus(int $itemId): array
    public function completeAuction(int $itemId): array
}
```

**setReservePrice**: Stores reserve price for item (only accessible by seller)
**getReservePrice**: Retrieves reserve price (permission-checked)
**isReserveMet**: Compares current highest bid to reserve price
**checkReserveStatus**: Returns whether reserve is met without revealing amount
**completeAuction**: Enhanced to check reserve before creating transaction

#### Updated Item Model

```php
class Item {
    // Existing properties...
    public ?float $reservePrice;
    public bool $reserveMet;

    public function toArray(bool $isSeller = false): array
}
```

**toArray**: Enhanced to conditionally include reserve price based on user role

### 6. WebSocket Real-Time Updates

#### WebSocketServer

```php
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class AuctionWebSocketServer implements MessageComponentInterface {
    private SplObjectStorage $clients;
    private array $subscriptions; // itemId => [connections]
    private PDO $db;

    public function onOpen(ConnectionInterface $conn): void
    public function onMessage(ConnectionInterface $from, $msg): void
    public function onClose(ConnectionInterface $conn): void
    public function onError(ConnectionInterface $conn, Exception $e): void
    public function broadcastBidUpdate(int $itemId, array $bidData): void
    public function broadcastOutbidNotification(int $itemId, int $previousBidderId): void
    public function broadcastAuctionEnding(int $itemId, int $secondsRemaining): void
    public function broadcastAuctionEnded(int $itemId): void
    private function authenticateConnection(ConnectionInterface $conn, string $token): bool
    private function subscribeToItem(ConnectionInterface $conn, int $itemId): void
    private function unsubscribeFromItem(ConnectionInterface $conn, int $itemId): void
}
```

**onOpen**: Authenticates JWT token, stores connection
**onMessage**: Handles subscription requests and commands
**onClose**: Cleans up subscriptions
**broadcastBidUpdate**: Sends new bid info to all watching clients
**broadcastOutbidNotification**: Notifies previous highest bidder
**broadcastAuctionEnding**: Sends countdown updates
**broadcastAuctionEnded**: Notifies when auction completes
**authenticateConnection**: Validates JWT token
**subscribeToItem**: Adds connection to item's subscriber list
**unsubscribeFromItem**: Removes connection from item's subscriber list

#### WebSocket Message Formats

**Client to Server**:
```json
{
  "action": "subscribe",
  "itemId": 123
}
```

**Server to Client - Bid Update**:
```json
{
  "type": "bid_update",
  "itemId": 123,
  "bidAmount": 150.00,
  "bidderId": 45,
  "bidderName": "john_doe",
  "timestamp": "2024-01-15T10:30:00Z",
  "reserveMet": true
}
```

**Server to Client - Outbid Notification**:
```json
{
  "type": "outbid",
  "itemId": 123,
  "newBidAmount": 150.00,
  "yourBidAmount": 140.00
}
```

**Server to Client - Auction Ending**:
```json
{
  "type": "auction_ending",
  "itemId": 123,
  "secondsRemaining": 180
}
```

#### Updated BidService

```php
class BidService {
    private PDO $db;
    private WebSocketClient $wsClient;

    // Existing methods...
    
    public function placeBid(int $itemId, int $userId, float $amount): int
    private function notifyWebSocket(int $itemId, array $bidData): void
}
```

**placeBid**: Enhanced to trigger WebSocket notifications
**notifyWebSocket**: Sends HTTP request to WebSocket server to broadcast update

## Data Models

### Database Schema Extensions

#### item_images Table

```sql
CREATE TABLE item_images (
    image_id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    thumbnail_url VARCHAR(255) NOT NULL,
    upload_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES items(item_id) ON DELETE CASCADE,
    INDEX idx_item_id (item_id)
);
```

#### reviews Table

```sql
CREATE TABLE reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT NOT NULL,
    reviewer_id INT NOT NULL,
    reviewee_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    review_text TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (transaction_id) REFERENCES transactions(transaction_id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (reviewee_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_review (transaction_id, reviewer_id),
    INDEX idx_reviewee (reviewee_id),
    INDEX idx_transaction (transaction_id)
);
```

#### watchlist Table

```sql
CREATE TABLE watchlist (
    watchlist_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    item_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(item_id) ON DELETE CASCADE,
    UNIQUE KEY unique_watchlist (user_id, item_id),
    INDEX idx_user_id (user_id),
    INDEX idx_item_id (item_id)
);
```

#### items Table Modifications

```sql
ALTER TABLE items 
ADD COLUMN reserve_price DECIMAL(10, 2) DEFAULT NULL,
ADD COLUMN commission_rate DECIMAL(5, 4) DEFAULT 0.05,
ADD COLUMN reserve_met BOOLEAN DEFAULT FALSE;
```

#### transactions Table Modifications

```sql
ALTER TABLE transactions
ADD COLUMN commission_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
ADD COLUMN seller_payout DECIMAL(10, 2) NOT NULL DEFAULT 0.00;
```

### Enhanced API Response Formats

#### Item Response (Enhanced)

```json
{
  "itemId": 123,
  "title": "Vintage Watch",
  "description": "...",
  "startingPrice": 100.00,
  "currentPrice": 150.00,
  "reserveMet": true,
  "reservePrice": null,
  "images": [
    {
      "imageId": 1,
      "imageUrl": "/uploads/abc123.jpg",
      "thumbnailUrl": "/uploads/thumbnails/abc123_thumb.jpg"
    }
  ],
  "isWatching": false,
  "sellerId": 10,
  "endTime": "2024-01-20T15:00:00Z"
}
```

#### Transaction Response (Enhanced)

```json
{
  "transactionId": 456,
  "itemId": 123,
  "sellerId": 10,
  "buyerId": 45,
  "finalPrice": 150.00,
  "commissionRate": 0.05,
  "commissionAmount": 7.50,
  "sellerPayout": 142.50,
  "completedAt": "2024-01-20T15:00:00Z"
}
```

#### User Profile Response (Enhanced)

```json
{
  "userId": 45,
  "username": "john_doe",
  "email": "john@example.com",
  "averageRating": 4.7,
  "totalReviews": 23,
  "recentReviews": [
    {
      "reviewId": 789,
      "reviewerId": 10,
      "reviewerName": "jane_smith",
      "rating": 5,
      "reviewText": "Great buyer!",
      "createdAt": "2024-01-15T10:00:00Z"
    }
  ]
}
```

