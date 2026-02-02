  # Implementation Plan: Auction Portal Enhancements

## Overview

This implementation plan extends the existing PHP/MySQL auction portal backend with six major feature enhancements: multi-image uploads with thumbnail generation, user ratings and reviews system, watchlist/favorites functionality, commission-based fee system, reserve pricing for auctions, and real-time WebSocket updates using Ratchet PHP.

The approach follows the existing architectural patterns and builds incrementally: database schema extensions, model creation, service layer implementation, controller and API endpoints, WebSocket server setup, and comprehensive testing. Each task builds on previous work while maintaining backward compatibility with existing API endpoints.

## Tasks

- [x] 1. Set up dependencies and directory structure for new features
  - Add GD library requirement for image processing to composer.json
  - Add cboden/ratchet dependency for WebSocket server
  - Create uploads/ and uploads/thumbnails/ directories with proper permissions
  - Create src/Services/ImageService.php, ReviewService.php, WatchlistService.php, CommissionService.php files
  - Create src/Controllers/ImageController.php, ReviewController.php, WatchlistController.php files
  - Create src/WebSocket/AuctionWebSocketServer.php file
  - Create database/migrations/ files for new tables
  - Update .env.example with new configuration variables (UPLOAD_DIR, THUMBNAIL_DIR, WS_PORT, DEFAULT_COMMISSION_RATE)
  - _Requirements: 1.1, 1.2, 1.3, 7.1, 7.2, 7.3, 9.1, 9.4_

- [x] 2. Create database migrations for new tables and columns
  - [x] 2.1 Create item_images table migration
    - Create migration file with item_images table schema
    - Add columns: image_id (PK), item_id (FK), image_url, thumbnail_url, upload_timestamp
    - Add foreign key constraint to items table with CASCADE delete
    - Add index on item_id for performance
    - _Requirements: 7.1, 1.2_
  
  - [x] 2.2 Create reviews table migration
    - Create migration file with reviews table schema
    - Add columns: review_id (PK), transaction_id (FK), reviewer_id (FK), reviewee_id (FK), rating, review_text, created_at
    - Add CHECK constraint for rating between 1 and 5
    - Add UNIQUE constraint on (transaction_id, reviewer_id) to prevent duplicates
    - Add foreign key constraints with CASCADE delete
    - Add indexes on reviewee_id and transaction_id
    - _Requirements: 7.2, 2.2, 2.4_
  
  - [x] 2.3 Create watchlist table migration
    - Create migration file with watchlist table schema
    - Add columns: watchlist_id (PK), user_id (FK), item_id (FK), added_at
    - Add UNIQUE constraint on (user_id, item_id) to prevent duplicates
    - Add foreign key constraints with CASCADE delete
    - Add indexes on user_id and item_id
    - _Requirements: 7.3, 3.1, 3.4_
  
  - [x] 2.4 Create items table extension migration
    - Create ALTER TABLE migration for items table
    - Add reserve_price column (DECIMAL(10,2), nullable)
    - Add commission_rate column (DECIMAL(5,4), default 0.05)
    - Add reserve_met column (BOOLEAN, default FALSE)
    - _Requirements: 7.4, 7.5, 7.7, 5.1, 4.1_
  
  - [x] 2.5 Create transactions table extension migration
    - Create ALTER TABLE migration for transactions table
    - Add commission_amount column (DECIMAL(10,2), default 0.00)
    - Add seller_payout column (DECIMAL(10,2), default 0.00)
    - _Requirements: 7.6, 4.3_
  
  - [x] 2.6 Run all migrations
    - Execute all migration files in order
    - Verify tables and columns are created correctly
    - Test foreign key constraints
    - _Requirements: 7.8_


- [x] 3. Create new model classes
  - [x] 3.1 Create ItemImage model
    - Define ItemImage class in src/Models/ItemImage.php
    - Implement properties: imageId, itemId, imageUrl, thumbnailUrl, uploadTimestamp
    - Implement methods: create, findByItemId, findById, delete
    - Implement toArray method for JSON serialization
    - _Requirements: 1.2, 1.5_
  
  - [x] 3.2 Create Review model
    - Define Review class in src/Models/Review.php
    - Implement properties: reviewId, transactionId, reviewerId, revieweeId, rating, reviewText, createdAt
    - Implement methods: create, findByRevieweeId, findByTransactionId, findById
    - Add validation for rating between 1 and 5
    - Implement toArray method for JSON serialization
    - _Requirements: 2.2, 2.3, 2.6_
  
  - [x] 3.3 Create Watchlist model
    - Define Watchlist class in src/Models/Watchlist.php
    - Implement properties: watchlistId, userId, itemId, addedAt
    - Implement methods: create, findByUserId, delete, exists
    - Implement toArray method for JSON serialization
    - _Requirements: 3.1, 3.2, 3.3_

- [x] 4. Implement Image Upload Service
  - [x] 4.1 Create ImageService class structure
    - Define ImageService class in src/Services/ImageService.php
    - Initialize properties: db, uploadDir, thumbnailDir, allowedFormats, maxFileSize
    - Set allowed formats to ['jpg', 'jpeg', 'png', 'webp']
    - Set max file size to 5MB (5242880 bytes)
    - _Requirements: 1.1, 9.1, 9.6_
  
  - [x] 4.2 Implement image validation methods
    - Implement validateImage method to check file extension
    - Implement validateMimeType method to verify MIME type matches extension
    - Implement file size validation
    - Return descriptive error messages for validation failures
    - _Requirements: 1.1, 1.6, 9.1, 9.2, 9.5_
  
  - [x] 4.3 Implement file upload and storage
    - Implement generateUniqueFilename method using uniqid() and hash
    - Implement uploadImage method to handle file upload
    - Move uploaded file to uploads/ directory with unique filename
    - Store file with appropriate permissions (0644)
    - Return error if upload fails
    - _Requirements: 1.2, 9.3, 9.4_
  
  - [x] 4.4 Implement thumbnail generation
    - Implement generateThumbnail method using GD library
    - Resize image to 200x200 maintaining aspect ratio
    - Support JPG, PNG, and WEBP formats
    - Save thumbnail to uploads/thumbnails/ directory
    - Handle errors gracefully
    - _Requirements: 1.3_
  
  - [x] 4.5 Implement database operations for images
    - Complete uploadImage method to insert record into item_images table
    - Implement getItemImages method to retrieve all images for an item
    - Implement deleteImage method to remove file and database record
    - Return image URLs and thumbnail URLs in responses
    - _Requirements: 1.2, 1.4, 1.5, 1.7_
  
  - [ ]* 4.6 Write property test for image upload validation
    - **Property 1: Invalid file formats are rejected**
    - **Validates: Requirements 1.1, 1.6**
  
  - [ ]* 4.7 Write property test for image storage
    - **Property 2: Uploaded images are associated with items**
    - **Validates: Requirements 1.4**
  
  - [ ]* 4.8 Write unit tests for thumbnail generation
    - Test thumbnail creation for JPG, PNG, WEBP
    - Test aspect ratio preservation
    - Test error handling for invalid images
    - _Requirements: 1.3_


- [x] 5. Implement User Ratings and Reviews Service
  - [x] 5.1 Create ReviewService class structure
    - Define ReviewService class in src/Services/ReviewService.php
    - Initialize database connection
    - Define validation constants for rating range (1-5)
    - _Requirements: 2.2_
  
  - [x] 5.2 Implement review creation with validation
    - Implement validateRating method to ensure rating is 1-5
    - Implement canReview method to verify user is part of transaction
    - Implement hasReviewed method to check for duplicate reviews
    - Implement createReview method with all validations
    - Store both rating and review text
    - _Requirements: 2.1, 2.2, 2.3, 2.4_
  
  - [x] 5.3 Implement review retrieval methods
    - Implement getReviewsForUser method to fetch all reviews for a user
    - Include reviewer information (name) in results
    - Order reviews by created_at descending
    - _Requirements: 2.6_
  
  - [x] 5.4 Implement average rating calculation
    - Implement calculateAverageRating method
    - Query all reviews for a user and compute mean rating
    - Return 0.0 if user has no reviews
    - Round to one decimal place
    - _Requirements: 2.5, 2.7_
  
  - [ ]* 5.5 Write property test for rating validation
    - **Property 3: Ratings outside 1-5 range are rejected**
    - **Validates: Requirements 2.2**
  
  - [ ]* 5.6 Write property test for duplicate review prevention
    - **Property 4: Duplicate reviews for same transaction are prevented**
    - **Validates: Requirements 2.4**
  
  - [ ]* 5.7 Write property test for average rating calculation
    - **Property 5: Average rating is calculated correctly**
    - **Validates: Requirements 2.5, 2.7**

- [x] 6. Implement Watchlist Service
  - [x] 6.1 Create WatchlistService class structure
    - Define WatchlistService class in src/Services/WatchlistService.php
    - Initialize database connection
    - Define notification threshold constant (24 hours)
    - _Requirements: 3.1_
  
  - [x] 6.2 Implement watchlist management methods
    - Implement isDuplicate method to check existing entries
    - Implement addToWatchlist method with duplicate prevention
    - Implement removeFromWatchlist method
    - Implement isWatching method to check if user is watching item
    - _Requirements: 3.1, 3.3, 3.4, 3.6_
  
  - [x] 6.3 Implement watchlist retrieval
    - Implement getWatchlist method to fetch user's watchlist
    - Join with items table to include item details
    - Include item title, current price, end time, and images
    - Order by added_at descending
    - _Requirements: 3.2_
  
  - [x] 6.4 Implement ending soon notifications
    - Implement getEndingSoonItems method
    - Find watched items ending within 24 hours
    - Return items with user information for notification
    - _Requirements: 3.5_
  
  - [ ]* 6.5 Write property test for duplicate prevention
    - **Property 6: Duplicate watchlist entries are prevented**
    - **Validates: Requirements 3.4**
  
  - [ ]* 6.6 Write property test for watchlist operations
    - **Property 7: Watchlist add and remove operations work correctly**
    - **Validates: Requirements 3.1, 3.3**


- [x] 7. Implement Commission and Fee System
  - [x] 7.1 Create CommissionService class
    - Define CommissionService class in src/Services/CommissionService.php
    - Initialize database connection
    - Set default commission rate to 0.05 (5%)
    - _Requirements: 4.2_
  
  - [x] 7.2 Implement commission calculation methods
    - Implement calculateCommission method (salePrice * commissionRate)
    - Implement getCommissionRate method (custom or default)
    - Implement setCommissionRate method for custom rates
    - Implement calculateSellerPayout method (salePrice - commission)
    - _Requirements: 4.1, 4.4, 4.5_
  
  - [x] 7.3 Implement platform earnings tracking
    - Implement getTotalPlatformEarnings method
    - Sum all commission_amount from transactions table
    - Implement getEarningsByDateRange method
    - Filter by completedAt date range
    - _Requirements: 4.6_
  
  - [x] 7.4 Update TransactionService to apply commission
    - Modify createTransaction method to accept commission calculation
    - Call CommissionService to calculate commission and payout
    - Store commission_amount and seller_payout in transaction record
    - _Requirements: 4.1, 4.3_
  
  - [ ]* 7.5 Write property test for commission calculation
    - **Property 8: Commission is calculated correctly as percentage**
    - **Validates: Requirements 4.1**
  
  - [ ]* 7.6 Write property test for seller payout
    - **Property 9: Seller payout equals sale price minus commission**
    - **Validates: Requirements 4.5**

- [x] 8. Implement Reserve Price System
  - [x] 8.1 Update ItemService with reserve price methods
    - Implement setReservePrice method (seller only)
    - Implement getReservePrice method with permission check
    - Implement isReserveMet method to compare bid with reserve
    - Implement checkReserveStatus method (returns met status without amount)
    - _Requirements: 5.1, 5.4, 5.5, 5.6, 5.7_
  
  - [x] 8.2 Update auction completion logic for reserve price
    - Modify completeAuction method in ItemService
    - Check if highest bid meets reserve price
    - If reserve not met: mark auction as "Reserve not met", don't create transaction
    - If reserve met: mark reserve_met as TRUE, create transaction normally
    - _Requirements: 5.2, 5.3_
  
  - [x] 8.3 Update Item model toArray method
    - Add reservePrice and reserveMet to Item model
    - Conditionally include reservePrice only for seller
    - Always include reserveMet status for bidders
    - _Requirements: 5.4, 5.5, 5.6_
  
  - [ ]* 8.4 Write property test for reserve price privacy
    - **Property 10: Reserve price is hidden from non-sellers**
    - **Validates: Requirements 5.6**
  
  - [ ]* 8.5 Write property test for reserve price enforcement
    - **Property 11: Auctions below reserve don't create transactions**
    - **Validates: Requirements 5.3**

- [x] 9. Checkpoint - Ensure all service layer tests pass
  - Run all unit and property tests for services
  - Verify database operations work correctly
  - Ensure all tests pass, ask the user if questions arise.


- [x] 10. Implement Image Controller and API endpoints
  - [x] 10.1 Create ImageController class
    - Define ImageController class in src/Controllers/ImageController.php
    - Inject ImageService dependency
    - Implement upload method for POST /api/items/{itemId}/images
    - Implement getImages method for GET /api/items/{itemId}/images
    - Implement delete method for DELETE /api/images/{imageId}
    - Add input validation and error handling
    - Return appropriate HTTP status codes
    - _Requirements: 1.1, 1.2, 1.5, 1.6, 1.7, 8.1, 8.2_
  
  - [x] 10.2 Add image routes to main router
    - Add POST /api/items/{itemId}/images route (protected)
    - Add GET /api/items/{itemId}/images route (public)
    - Add DELETE /api/images/{imageId} route (protected)
    - Apply authentication middleware to protected routes
    - Verify seller owns item before allowing upload/delete
    - _Requirements: 8.1, 8.6_
  
  - [x] 10.3 Update ItemController to include images in responses
    - Modify getItemById to include images array
    - Modify getActiveItems to include images for each item
    - Use ImageService.getItemImages to fetch images
    - _Requirements: 1.5, 8.2_
  
  - [ ]* 10.4 Write unit tests for Image Controller endpoints
    - Test successful image upload
    - Test validation errors (invalid format, file too large)
    - Test image retrieval for items
    - Test image deletion by owner
    - Test unauthorized deletion attempts
    - _Requirements: 1.1, 1.6, 9.5, 9.6_

- [x] 11. Implement Review Controller and API endpoints
  - [x] 11.1 Create ReviewController class
    - Define ReviewController class in src/Controllers/ReviewController.php
    - Inject ReviewService dependency
    - Implement create method for POST /api/reviews
    - Implement getUserReviews method for GET /api/users/{userId}/reviews
    - Implement getUserRating method for GET /api/users/{userId}/rating
    - Add input validation and error handling
    - Return appropriate HTTP status codes
    - _Requirements: 2.1, 2.2, 2.3, 2.6, 8.1, 8.2_
  
  - [x] 11.2 Add review routes to main router
    - Add POST /api/reviews route (protected)
    - Add GET /api/users/{userId}/reviews route (public)
    - Add GET /api/users/{userId}/rating route (public)
    - Apply authentication middleware to POST route
    - Validate user can review transaction
    - _Requirements: 8.1, 8.6_
  
  - [x] 11.3 Update UserController to include rating in profile
    - Modify getUserProfile to include averageRating
    - Modify getPublicProfile to include averageRating and totalReviews
    - Use ReviewService to calculate rating
    - _Requirements: 2.5, 2.6, 8.2_
  
  - [ ]* 11.4 Write unit tests for Review Controller endpoints
    - Test successful review creation
    - Test validation errors (invalid rating, duplicate review)
    - Test review retrieval for users
    - Test average rating calculation
    - Test unauthorized review attempts
    - _Requirements: 2.2, 2.4_


- [x] 12. Implement Watchlist Controller and API endpoints
  - [x] 12.1 Create WatchlistController class
    - Define WatchlistController class in src/Controllers/WatchlistController.php
    - Inject WatchlistService dependency
    - Implement add method for POST /api/watchlist
    - Implement remove method for DELETE /api/watchlist/{itemId}
    - Implement getWatchlist method for GET /api/watchlist
    - Implement checkWatching method for GET /api/watchlist/check/{itemId}
    - Add input validation and error handling
    - Return appropriate HTTP status codes
    - _Requirements: 3.1, 3.2, 3.3, 3.6, 8.1, 8.2_
  
  - [x] 12.2 Add watchlist routes to main router
    - Add POST /api/watchlist route (protected)
    - Add DELETE /api/watchlist/{itemId} route (protected)
    - Add GET /api/watchlist route (protected)
    - Add GET /api/watchlist/check/{itemId} route (protected)
    - Apply authentication middleware to all routes
    - _Requirements: 8.1, 8.6_
  
  - [x] 12.3 Update ItemController to include watchlist status
    - Modify getItemById to include isWatching field
    - Check if authenticated user is watching the item
    - Use WatchlistService.isWatching method
    - _Requirements: 3.6, 8.2_
  
  - [ ]* 12.4 Write unit tests for Watchlist Controller endpoints
    - Test successful watchlist add and remove
    - Test duplicate prevention
    - Test watchlist retrieval
    - Test watching status check
    - Test unauthorized access
    - _Requirements: 3.1, 3.3, 3.4_

- [x] 13. Update Transaction Controller for commission breakdown
  - [x] 13.1 Update transaction response format
    - Modify getTransactionById to include commission breakdown
    - Include commissionRate, commissionAmount, sellerPayout fields
    - Modify getUserTransactions to include commission data
    - _Requirements: 4.3, 4.4, 8.2, 8.3_
  
  - [ ]* 13.2 Write unit tests for enhanced transaction responses
    - Test transaction detail includes commission breakdown
    - Test commission calculation accuracy
    - Test seller payout calculation
    - _Requirements: 4.3, 4.4_

- [ ] 14. Implement WebSocket Server for real-time updates
  - [x] 14.1 Create AuctionWebSocketServer class
    - Define AuctionWebSocketServer class implementing MessageComponentInterface
    - Initialize SplObjectStorage for client connections
    - Initialize subscriptions array (itemId => connections)
    - Inject database connection for authentication
    - _Requirements: 6.1_
  
  - [x] 14.2 Implement WebSocket connection lifecycle methods
    - Implement onOpen method to handle new connections
    - Authenticate connection using JWT token from query string
    - Store authenticated connection with user info
    - Implement onClose method to clean up subscriptions
    - Implement onError method for error handling
    - _Requirements: 6.1, 6.5_
  
  - [x] 14.3 Implement subscription management
    - Implement onMessage method to handle client messages
    - Parse JSON messages for subscribe/unsubscribe actions
    - Implement subscribeToItem method to add connection to item's subscribers
    - Implement unsubscribeFromItem method to remove connection
    - Validate itemId exists before subscribing
    - _Requirements: 10.4_


  - [x] 14.4 Implement broadcast methods for real-time events
    - Implement broadcastBidUpdate method to send new bid info
    - Include bidAmount, bidderId, bidderName, timestamp, reserveMet
    - Implement broadcastOutbidNotification for previous highest bidder
    - Implement broadcastAuctionEnding for countdown updates
    - Implement broadcastAuctionEnded for completion
    - Send messages only to subscribed clients
    - _Requirements: 6.2, 6.3, 6.4, 6.6, 6.7_
  
  - [x] 14.5 Create WebSocket server startup script
    - Create bin/websocket-server.php script
    - Initialize Ratchet WebSocket server
    - Bind to configured port (default 8080)
    - Load environment variables
    - Add error handling and logging
    - _Requirements: 6.1_
  
  - [x] 14.6 Integrate WebSocket notifications with BidService
    - Create WebSocketClient helper class for HTTP communication
    - Update BidService.placeBid to trigger WebSocket broadcast
    - Send bid data to WebSocket server via HTTP endpoint
    - Handle WebSocket server unavailability gracefully
    - _Requirements: 6.2, 6.3_
  
  - [ ] 14.7 Implement auction ending countdown scheduler
    - Create scheduled task to check auctions ending within 5 minutes
    - Broadcast countdown updates every 30 seconds
    - Integrate with existing auction completion scheduler
    - _Requirements: 6.4_
  
  - [ ]* 14.8 Write unit tests for WebSocket message formats
    - Test bid update message structure
    - Test outbid notification message structure
    - Test auction ending message structure
    - Test subscription/unsubscription handling
    - _Requirements: 6.2, 6.3, 6.4, 6.6_

- [x] 15. Implement notification queueing for reliability
  - [x] 15.1 Create notification queue system
    - Create notifications table for queued messages
    - Implement queue storage when WebSocket delivery fails
    - Store notification type, itemId, userId, payload, created_at
    - _Requirements: 10.2_
  
  - [x] 15.2 Implement notification delivery on reconnection
    - Check for queued notifications when client connects
    - Deliver pending notifications to reconnected client
    - Mark notifications as delivered
    - Delete old delivered notifications (older than 24 hours)
    - _Requirements: 10.1, 10.2_
  
  - [x] 15.3 Implement notification batching
    - Batch multiple rapid events into single notification
    - Implement debouncing for high-frequency updates
    - Include unique event IDs to prevent duplicate processing
    - _Requirements: 10.3, 10.5_
  
  - [ ]* 15.4 Write property test for notification delivery
    - **Property 12: Notifications are delivered reliably**
    - **Validates: Requirements 10.2**

- [x] 16. Checkpoint - Ensure all API and WebSocket tests pass
  - Run all controller unit tests
  - Test WebSocket connection and subscription
  - Test real-time bid notifications
  - Ensure all tests pass, ask the user if questions arise.


- [ ] 17. Ensure backward compatibility with existing API
  - [ ] 17.1 Test existing endpoints with previous request formats
    - Test POST /api/users/register with original fields
    - Test POST /api/items with original fields (without reserve_price)
    - Test POST /api/bids with original fields
    - Verify all existing endpoints return successful responses
    - _Requirements: 8.1_
  
  - [ ] 17.2 Verify new fields are optional in requests
    - Test item creation without reserve_price (should use NULL)
    - Test item creation without commission_rate (should use default 5%)
    - Verify default behavior maintained when new parameters omitted
    - _Requirements: 8.3_
  
  - [ ] 17.3 Verify response format extensions
    - Verify existing fields remain in responses
    - Verify new fields added as additional properties
    - Test that old API consumers can ignore new fields
    - Verify JSON structure compatibility
    - _Requirements: 8.2, 8.5_
  
  - [ ]* 17.4 Write property test for backward compatibility
    - **Property 13: Existing API endpoints maintain compatibility**
    - **Validates: Requirements 8.1, 8.2, 8.3**

- [ ] 18. Implement security enhancements for file uploads
  - [ ] 18.1 Enhance file upload validation
    - Verify file extension against allowlist
    - Verify MIME type matches extension using finfo_file
    - Reject files with mismatched extension/MIME type
    - _Requirements: 9.1, 9.2_
  
  - [ ] 18.2 Implement secure filename generation
    - Use uniqid() combined with hash for unique filenames
    - Prevent path traversal by removing directory separators
    - Validate no special characters in generated names
    - _Requirements: 9.3_
  
  - [ ] 18.3 Set secure file permissions
    - Set uploaded files to 0644 permissions (read-only for others)
    - Ensure uploads directory is not executable
    - Add .htaccess to prevent PHP execution in uploads directory
    - _Requirements: 9.4_
  
  - [ ]* 18.4 Write property test for file upload security
    - **Property 14: File uploads are validated securely**
    - **Validates: Requirements 9.1, 9.2, 9.3, 9.4**

- [x] 19. Write integration tests for complete feature flows
  - [ ]* 19.1 Write integration test for image upload flow
    - Test complete flow: create item → upload images → retrieve item with images
    - Test multiple image uploads for single item
    - Test image deletion
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5_
  
  - [ ]* 19.2 Write integration test for review flow
    - Test complete flow: create auction → place bid → complete auction → create review
    - Test both seller and buyer can review
    - Test duplicate review prevention
    - Test average rating calculation
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6_
  
  - [ ]* 19.3 Write integration test for watchlist flow
    - Test complete flow: add to watchlist → retrieve watchlist → remove from watchlist
    - Test watchlist status in item details
    - Test ending soon notifications
    - _Requirements: 3.1, 3.2, 3.3, 3.5, 3.6_
  
  - [ ]* 19.4 Write integration test for commission flow
    - Test complete flow: create item with custom commission → place bid → complete auction
    - Verify commission calculated correctly
    - Verify seller payout calculated correctly
    - Test platform earnings aggregation
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6_
  
  - [ ]* 19.5 Write integration test for reserve price flow
    - Test auction with reserve not met (no transaction created)
    - Test auction with reserve met (transaction created)
    - Test reserve price visibility (hidden from bidders, visible to seller)
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 5.7_
  
  - [ ]* 19.6 Write integration test for WebSocket real-time updates
    - Test WebSocket connection with JWT authentication
    - Test subscription to item updates
    - Test bid update broadcast to subscribers
    - Test outbid notification to previous bidder
    - Test auction ending countdown
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 6.7_


- [x] 20. Update documentation and configuration
  - [x] 20.1 Update README.md with new features
    - Document image upload feature and supported formats
    - Document ratings and reviews system
    - Document watchlist functionality
    - Document commission system and configuration
    - Document reserve price feature
    - Document WebSocket real-time updates
    - Add setup instructions for WebSocket server
    - Add instructions for creating uploads directories
    - _Requirements: 8.1_
  
  - [x] 20.2 Document new API endpoints
    - Document all image endpoints with request/response examples
    - Document all review endpoints with request/response examples
    - Document all watchlist endpoints with request/response examples
    - Document enhanced transaction endpoints with commission breakdown
    - Document WebSocket connection and message formats
    - Add curl examples for each endpoint
    - _Requirements: 8.1_
  
  - [x] 20.3 Update .env.example with new variables
    - Add UPLOAD_DIR configuration
    - Add THUMBNAIL_DIR configuration
    - Add MAX_FILE_SIZE configuration
    - Add DEFAULT_COMMISSION_RATE configuration
    - Add WS_PORT configuration for WebSocket server
    - Add comments explaining each variable
    - _Requirements: 9.1, 9.6_
  
  - [x] 20.4 Create deployment guide for WebSocket server
    - Document how to start WebSocket server as background process
    - Document process management (systemd, supervisor, pm2)
    - Document firewall configuration for WebSocket port
    - Document monitoring and logging setup
    - _Requirements: 6.1_

- [ ] 21. Performance optimization and testing
  - [ ] 21.1 Add database indexes for new queries
    - Verify indexes on item_images.item_id
    - Verify indexes on reviews.reviewee_id and reviews.transaction_id
    - Verify indexes on watchlist.user_id and watchlist.item_id
    - Test query performance with EXPLAIN
    - _Requirements: 7.1, 7.2, 7.3_
  
  - [ ] 21.2 Optimize image loading
    - Implement lazy loading for item images
    - Return thumbnail URLs by default, full images on demand
    - Add caching headers for image responses
    - _Requirements: 1.3, 1.5_
  
  - [ ] 21.3 Optimize WebSocket performance
    - Implement connection pooling
    - Add rate limiting for subscription requests
    - Optimize broadcast to only send to relevant subscribers
    - _Requirements: 6.2, 10.3, 10.4_
  
  - [ ]* 21.4 Write performance tests
    - Test image upload with maximum file size
    - Test concurrent bid updates via WebSocket
    - Test watchlist retrieval with many items
    - Test review aggregation with many reviews
    - _Requirements: 9.6_

- [ ] 22. Final checkpoint - Complete system testing
  - Run complete test suite (unit + property + integration tests)
  - Test all 14 correctness properties
  - Verify backward compatibility with existing API
  - Test WebSocket server under load
  - Test file upload security measures
  - Verify all database migrations applied correctly
  - Test commission calculations with various rates
  - Test reserve price enforcement
  - Ensure all tests pass, ask the user if questions arise.


## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Property tests validate universal correctness properties from the design document
- Unit tests validate specific examples and edge cases
- Integration tests validate complete feature flows
- Checkpoints ensure incremental validation at key milestones
- The WebSocket server runs as a separate process from the main PHP application
- Image uploads require GD library installed and configured in PHP
- File permissions must be set correctly to prevent security vulnerabilities
- Database migrations should be run in order to maintain referential integrity
- Backward compatibility is maintained by making all new fields optional
- Commission system uses default 5% rate unless custom rate is set per item
- Reserve price is optional and hidden from bidders (only reserve met status shown)
- WebSocket authentication uses JWT tokens from the existing auth system
- Notification queueing ensures reliable delivery even with connection issues
- Testing framework: PHPUnit for unit and integration tests
- WebSocket library: Ratchet PHP (cboden/ratchet)
- Image processing: PHP GD library
