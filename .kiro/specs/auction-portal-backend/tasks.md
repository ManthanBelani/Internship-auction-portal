# Implementation Plan: Auction Portal Backend

## Overview

This implementation plan breaks down the auction portal backend into discrete coding tasks. The approach follows a bottom-up strategy: starting with foundational infrastructure (database, models), then building core services, followed by API controllers, and finally integration and testing. Each task builds incrementally on previous work to ensure continuous validation.

## Tasks

- [x] 1. Set up project structure and dependencies
  - Initialize PHP project with composer.json
  - Install dependencies: firebase/php-jwt, vlucas/phpdotenv
  - Install dev dependencies: phpunit/phpunit
  - Create directory structure: public/, src/, src/Models/, src/Services/, src/Controllers/, src/Middleware/, src/Config/, src/Utils/, database/migrations/, tests/
  - Create .env.example file with configuration variables (DB_HOST, DB_NAME, DB_USER, DB_PASSWORD, JWT_SECRET)
  - Set up basic PHP router in public/index.php
  - Create database connection class in src/Config/Database.php
  - Create SQL migration files for all tables
  - _Requirements: 8.1, 9.5_

- [-] 2. Create database tables and PHP models
  - [x] 2.1 Run database migrations
    - Execute SQL migration files to create tables
    - Verify tables are created correctly in MySQL
    - _Requirements: 9.5, 9.6_
  
  - [x] 2.2 Create User model
    - Define User class in src/Models/User.php
    - Implement methods: create, findByEmail, findById, update
    - Add validation for email format and required fields
    - _Requirements: 9.1, 10.4_
  
  - [x] 2.3 Create Item model
    - Define Item class in src/Models/Item.php
    - Implement methods: create, findById, findActive, search, update
    - Add validation for positive prices and future endTime
    - _Requirements: 9.2, 3.2, 3.3_
  
  - [x] 2.4 Create Bid model
    - Define Bid class in src/Models/Bid.php
    - Implement methods: create, findByItemId, findByBidderId
    - Add validation for positive amount
    - _Requirements: 9.3_
  
  - [x] 2.5 Create Transaction model
    - Define Transaction class in src/Models/Transaction.php
    - Implement methods: create, findByUserId, findById
    - Add validation for positive finalPrice
    - _Requirements: 9.4_
    - Handle connection errors and retry logic
    - Export connection function
    - _Requirements: 9.5, 9.6_
  
  - [ ] 2.2 Create User model
    - Define User schema with email, passwordHash, name, registeredAt fields
    - Add unique index on email field
    - Implement schema validation (email format, required fields)
    - _Requirements: 9.1, 10.4_
  
  - [ ] 2.3 Create Item model
    - Define Item schema with title, description, startingPrice, currentPrice, endTime, sellerId, highestBidderId, status fields
    - Add indexes on sellerId, status, endTime
    - Add text indexes on title and description for search
    - Implement schema validation (positive prices, future endTime)
    - _Requirements: 9.2, 3.2, 3.3_
  
  - [ ] 2.4 Create Bid model
    - Define Bid schema with itemId, bidderId, amount, timestamp fields
    - Add indexes on itemId, bidderId, timestamp
    - Add compound index on (itemId, timestamp)
    - Implement schema validation (positive amount)
    - _Requirements: 9.3_
  
  - [ ] 2.5 Create Transaction model
    - Define Transaction schema with itemId, sellerId, buyerId, finalPrice, completedAt fields
    - Add indexes on sellerId, buyerId, itemId, completedAt
    - Implement schema validation (positive finalPrice)
    - _Requirements: 9.4_

- [-] 3. Implement authentication utilities and middleware
  - [x] 3.1 Create password hashing utilities
    - Implement hashPassword function using password_hash
    - Implement verifyPassword function using password_verify
    - Export utilities from src/Utils/Auth.php
    - _Requirements: 1.5_
  
  - [ ]* 3.2 Write property test for password hashing
    - **Property 5: Passwords are hashed before storage**
    - **Validates: Requirements 1.5**
  
  - [x] 3.3 Create JWT token utilities
    - Implement generateToken function to create JWT tokens
    - Implement verifyToken function to validate JWT tokens
    - Include userId and email in token payload
    - _Requirements: 1.3, 1.6_
  
  - [ ]* 3.4 Write property test for JWT token generation and validation
    - **Property 6: Token validation identifies users correctly**
    - **Validates: Requirements 1.6**
  
  - [x] 3.5 Create authentication middleware
    - Implement authenticate method in src/Middleware/AuthMiddleware.php
    - Extract token from Authorization header
    - Verify token and return user info
    - Return 401 for missing or invalid tokens
    - _Requirements: 1.6, 8.6, 10.6_
  
  - [ ]* 3.6 Write property test for authentication middleware
    - **Property 32: Protected endpoints require authentication**
    - **Validates: Requirements 8.6, 10.6**

- [-] 4. Implement User Service
  - [x] 4.1 Create registerUser function
    - Check if email already exists
    - Hash password using password_hash
    - Create new user in database
    - Generate JWT token
    - Return user data with token
    - _Requirements: 1.1, 1.2, 1.5_
  
  - [ ] 4.2 Write property tests for user registration

    - **Property 1: Valid registration creates unique user accounts**
    - **Validates: Requirements 1.1**
  
  - [ ]* 4.3 Write property test for duplicate email rejection
    - **Property 2: Duplicate email registration is rejected**
    - **Validates: Requirements 1.2**
  
  - [x] 4.4 Create authenticateUser function
    - Find user by email
    - Compare password with stored hash
    - Generate JWT token on success
    - Return user data with token or throw error
    - _Requirements: 1.3, 1.4_
  
  - [ ]* 4.5 Write property tests for authentication
    - **Property 3: Valid credentials authenticate successfully**
    - **Validates: Requirements 1.3**
  
  - [ ]* 4.6 Write property test for invalid credentials
    - **Property 4: Invalid credentials are rejected**
    - **Validates: Requirements 1.4**
  
  - [x] 4.7 Create getUserProfile and updateUserProfile functions
    - Implement profile retrieval by userId
    - Implement profile update with validation
    - Prevent modification of other users' profiles
    - _Requirements: 2.1, 2.2, 2.4_
  
  - [ ]* 4.8 Write property tests for profile management
    - **Property 7: Authenticated users can retrieve their profiles**
    - **Property 8: Profile updates persist correctly**
    - **Property 10: Users cannot modify other users' profiles**
    - **Validates: Requirements 2.1, 2.2, 2.4**
  
  - [x] 4.9 Create getPublicProfile function
    - Return only non-sensitive user information
    - Exclude email and password_hash
    - _Requirements: 2.3_
  
  - [ ]* 4.10 Write property test for public profile data
    - **Property 9: Public profiles exclude sensitive data**
    - **Validates: Requirements 2.3**

- [x] 5. Checkpoint - Ensure user management tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [-] 6. Implement Item Service
  - [x] 6.1 Create createItem function
    - Validate endTime is in the future
    - Validate startingPrice is positive
    - Create item with status "active"
    - Initialize currentPrice to startingPrice
    - Associate with sellerId
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5_
  
  - [ ]* 6.2 Write property tests for item creation
    - **Property 11: Valid listings are created and associated with sellers**
    - **Property 12: Past end times are rejected**
    - **Property 13: Invalid prices are rejected**
    - **Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5**
  
  - [x] 6.3 Create getActiveItems function
    - Filter items by status "active" and future endTime
    - Support optional search keyword filtering (title/description)
    - Support optional sellerId filtering
    - Include seller information in results
    - _Requirements: 4.1, 4.3, 4.4, 4.5_
  
  - [ ]* 6.4 Write property tests for item retrieval
    - **Property 14: Active listings are filtered correctly**
    - **Property 16: Keyword search returns matching listings**
    - **Property 17: Seller filtering returns correct listings**
    - **Validates: Requirements 4.1, 4.3, 4.4**
  
  - [x] 6.5 Create getItemById function
    - Retrieve item by ID with all details
    - Include seller information
    - Include current highest bid information
    - Throw error if item not found
    - _Requirements: 4.2, 4.5_
  
  - [ ]* 6.6 Write property test for item detail retrieval
    - **Property 15: Listing retrieval includes complete details**
    - **Validates: Requirements 4.2, 4.5**
  
  - [x] 6.7 Create checkAndCompleteExpiredAuctions function
    - Find all active auctions with endTime in the past
    - For auctions with bids: mark as "completed" and create transaction
    - For auctions without bids: mark as "expired"
    - Return count of completed auctions
    - _Requirements: 6.1, 6.2, 6.5_
  
  - [ ]* 6.8 Write property tests for auction completion
    - **Property 23: Auctions with bids complete and create transactions**
    - **Property 24: Auctions without bids expire without transactions**
    - **Property 26: Completed auctions are immutable**
    - **Validates: Requirements 6.1, 6.2, 6.5**

- [-] 7. Implement Bid Service
  - [x] 7.1 Create placeBid function
    - Validate auction exists and is active
    - Validate auction endTime is in the future
    - Validate bidder is not the seller
    - Validate bid amount is higher than current price
    - Create bid record with timestamp
    - Update item's currentPrice and highestBidderId
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6_
  
  - [ ]* 7.2 Write property tests for bid placement
    - **Property 18: Valid bids update auction state**
    - **Property 19: Low bids are rejected**
    - **Property 20: Self-bidding is prevented**
    - **Property 21: Bids on expired auctions are rejected**
    - **Property 22: Successful bids are stored completely**
    - **Validates: Requirements 5.1, 5.2, 5.3, 5.4, 5.5, 5.6**
  
  - [x] 7.3 Create getBidHistory function
    - Retrieve all bids for a specific itemId
    - Order bids by timestamp (descending)
    - Include bidder information (name)
    - Throw error if item not found
    - _Requirements: 7.1, 7.2, 7.3_
  
  - [ ]* 7.4 Write property tests for bid history
    - **Property 27: Bid history is ordered chronologically**
    - **Property 28: Bid history includes complete bid information**
    - **Property 29: Bid history is accessible for all auction states**
    - **Validates: Requirements 7.1, 7.2, 7.3**

- [x] 8. Implement Transaction Service
  - [x] 8.1 Create createTransaction function
    - Create transaction record with itemId, sellerId, buyerId, finalPrice
    - Set completedAt to current timestamp
    - Return transaction data
    - _Requirements: 6.3_
  
  - [x] 8.2 Create getUserTransactions function
    - Retrieve all transactions where userId is buyer or seller
    - Include item title in results
    - Order by completedAt (descending)
    - _Requirements: 6.4_
  
  - [ ]* 8.3 Write property test for transaction filtering
    - **Property 25: Transaction history filtering works correctly**
    - **Validates: Requirements 6.4**
  
  - [x] 8.4 Create getTransactionById function
    - Retrieve transaction by ID
    - Include seller and buyer names
    - Include item title
    - Throw error if transaction not found
    - _Requirements: 6.4_

- [x] 9. Checkpoint - Ensure core services tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [-] 10. Implement User Controller and routes
  - [x] 10.1 Create User Controller
    - Implement POST /api/users/register endpoint
    - Implement POST /api/users/login endpoint
    - Implement GET /api/users/profile endpoint (protected)
    - Implement PUT /api/users/profile endpoint (protected)
    - Implement GET /api/users/:userId/public endpoint
    - Add input validation
    - Handle errors and return appropriate status codes
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 2.1, 2.2, 2.3, 8.1, 8.2, 8.3, 8.4, 10.1, 10.2_
  
  - [x] 10.2 Add user routes to main router
    - Add routes to public/index.php
    - Apply authentication middleware to protected routes
    - Apply validation to all routes
    - _Requirements: 8.1, 8.2, 8.6_
  
  - [ ]* 10.3 Write unit tests for User Controller endpoints
    - Test successful registration and login
    - Test validation errors (invalid email, short password)
    - Test duplicate email rejection
    - Test profile retrieval and updates
    - Test unauthorized access to protected endpoints
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 2.1, 2.2, 10.4, 10.5_

- [ ] 11. Implement Item Controller and routes
  - [x] 11.1 Create Item Controller
    - Implement POST /api/items endpoint (protected)
    - Implement GET /api/items endpoint (with query params)
    - Implement GET /api/items/:itemId endpoint
    - Add input validation
    - Handle errors and return appropriate status codes
    - _Requirements: 3.1, 3.2, 3.3, 4.1, 4.2, 4.3, 4.4, 8.1, 8.2, 8.3, 8.4, 10.1, 10.2_
  
  - [x] 11.2 Add item routes to main router
    - Add routes to public/index.php
    - Apply authentication middleware to POST endpoint
    - Apply validation to all routes
    - _Requirements: 8.1, 8.2, 8.6_
  
  - [ ]* 11.3 Write unit tests for Item Controller endpoints
    - Test successful item creation
    - Test validation errors (past endTime, negative price)
    - Test item retrieval with filters
    - Test search functionality
    - Test 404 for non-existent items
    - _Requirements: 3.1, 3.2, 3.3, 4.1, 4.2, 4.3_

- [ ] 12. Implement Bid Controller and routes
  - [x] 12.1 Create Bid Controller
    - Implement POST /api/bids endpoint (protected)
    - Implement GET /api/bids/:itemId endpoint
    - Add input validation
    - Handle errors and return appropriate status codes
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 7.1, 7.2, 8.1, 8.2, 8.3, 8.4, 10.1, 10.2_
  
  - [x] 12.2 Add bid routes to main router
    - Add routes to public/index.php
    - Apply authentication middleware to POST endpoint
    - Apply validation to all routes
    - _Requirements: 8.1, 8.2, 8.6_
  
  - [ ]* 12.3 Write unit tests for Bid Controller endpoints
    - Test successful bid placement
    - Test validation errors (low bid, self-bidding, expired auction)
    - Test bid history retrieval
    - Test 404 for non-existent items
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 7.1_

- [ ] 13. Implement Transaction Controller and routes
  - [x] 13.1 Create Transaction Controller
    - Implement GET /api/transactions endpoint (protected)
    - Implement GET /api/transactions/:transactionId endpoint (protected)
    - Add input validation
    - Handle errors and return appropriate status codes
    - _Requirements: 6.4, 8.1, 8.2, 8.3, 8.4, 10.1, 10.2_
  
  - [x] 13.2 Add transaction routes to main router
    - Add routes to public/index.php
    - Apply authentication middleware to all routes
    - Apply validation to all routes
    - _Requirements: 8.1, 8.2, 8.6_
  
  - [ ]* 13.3 Write unit tests for Transaction Controller endpoints
    - Test transaction history retrieval
    - Test transaction detail retrieval
    - Test 404 for non-existent transactions
    - Test unauthorized access
    - _Requirements: 6.4_

- [ ] 14. Implement input validation and security
  - [x] 14.1 Create validation utilities
    - Implement validation functions for all endpoints
    - Validate email format, password length, positive numbers, future dates
    - Sanitize inputs to prevent SQL injection
    - Return descriptive validation error messages
    - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5_
  
  - [ ]* 14.2 Write property tests for input validation
    - **Property 34: Invalid inputs are validated and rejected**
    - **Property 35: SQL injection attempts are sanitized**
    - **Property 36: Email format validation works correctly**
    - **Property 37: Password length is enforced**
    - **Validates: Requirements 10.1, 10.2, 10.3, 10.4, 10.5**
  
  - [x] 14.3 Implement error handling
    - Create global error handler
    - Format errors consistently with code and message
    - Map error types to appropriate HTTP status codes
    - Log errors for debugging
    - _Requirements: 8.4, 9.6_
  
  - [ ]* 14.4 Write property test for error responses
    - **Property 31: Errors return appropriate status codes**
    - **Validates: Requirements 8.4**

- [ ] 15. Integrate all components and configure server
  - [x] 15.1 Complete main router in public/index.php
    - Add all API routes
    - Apply CORS headers
    - Apply error handling
    - Connect to database on request
    - _Requirements: 8.1, 8.5, 9.5_
  
  - [x] 15.2 Create scheduled task for auction completion
    - Create PHP script for periodic auction completion check
    - Call checkAndCompleteExpiredAuctions function
    - Log completion results
    - Set up cron job or task scheduler
    - _Requirements: 6.1, 6.2_
  
  - [ ]* 15.3 Write integration tests for complete API flows
    - Test complete user registration → login → create item → place bid → auction completion flow
    - Test multiple users bidding on same item
    - Test transaction creation after auction ends
    - _Requirements: 1.1, 1.3, 3.1, 5.1, 6.1_

- [ ] 16. Add API documentation and configuration
  - [x] 16.1 Update README.md with setup instructions
    - Document installation steps
    - Document environment variables
    - Document how to set up MySQL database
    - Document how to run migrations
    - Document how to start the PHP server
    - Document API endpoints with examples
    - _Requirements: 8.1_
  
  - [x] 16.2 Verify .env.example file
    - Ensure all required environment variables are included
    - Add comments explaining each variable
    - _Requirements: 9.5_

- [ ] 17. Write property tests for API design properties
  - [ ]* 17.1 Write property test for JSON responses
    - **Property 30: All responses are valid JSON**
    - **Validates: Requirements 8.3**
  
  - [ ]* 17.2 Write property test for authentication requirement
    - **Property 38: Unauthorized access is prevented**
    - **Validates: Requirements 10.6**
  
  - [ ]* 17.3 Write property test for database error handling
    - **Property 33: Database failures return appropriate errors**
    - **Validates: Requirements 9.6**

- [ ] 18. Final checkpoint - Ensure all tests pass
  - Run complete test suite (unit + property + integration tests)
  - Verify all 38 correctness properties are tested
  - Ensure test coverage meets goals (80% line coverage, 75% branch coverage)
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Property tests validate universal correctness properties from the design document
- Unit tests validate specific examples and edge cases
- Checkpoints ensure incremental validation at key milestones
- The scheduled task for auction completion (15.2) can be implemented with a cron job or Windows Task Scheduler
- PHP uses PDO with prepared statements to prevent SQL injection
- Testing framework: PHPUnit for unit and integration tests