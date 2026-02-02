# Design Document: Auction Portal Backend

## Overview

The auction portal backend is a RESTful API service built with Node.js and Express.js, using MongoDB for data persistence. The system provides a complete auction platform where users can register, create item listings, place bids, and complete transactions. The API is designed specifically for integration with a Flutter mobile application, following REST principles and returning JSON responses.

The architecture emphasizes:
- **Stateless authentication** using JWT tokens for mobile app compatibility
- **Clear separation of concerns** with controllers, services, and data access layers
- **Input validation** at API boundaries to ensure data integrity
- **Asynchronous operations** leveraging Node.js event loop for scalability
- **MongoDB document model** optimized for auction data access patterns

## Architecture

### System Architecture

The system follows a layered architecture pattern:

```
┌─────────────────────────────────────┐
│      Flutter Mobile App             │
└──────────────┬──────────────────────┘
               │ HTTP/JSON
               │ (RESTful API)
┌──────────────▼──────────────────────┐
│      API Layer (Express.js)         │
│  - Routes                           │
│  - Middleware (Auth, Validation)    │
│  - Controllers                      │
└──────────────┬──────────────────────┘
               │
┌──────────────▼──────────────────────┐
│      Service Layer                  │
│  - Business Logic                   │
│  - Auction Management               │
│  - Bid Processing                   │
│  - Transaction Creation             │
└──────────────┬──────────────────────┘
               │
┌──────────────▼──────────────────────┐
│      Data Access Layer              │
│  - MongoDB Models (Mongoose)        │
│  - Database Operations              │
└──────────────┬──────────────────────┘
               │
┌──────────────▼──────────────────────┐
│      MongoDB Database               │
│  - Users Collection                 │
│  - Items Collection                 │
│  - Bids Collection                  │
│  - Transactions Collection          │
└─────────────────────────────────────┘
```

### Technology Stack

- **Runtime**: Node.js (v18+)
- **Web Framework**: Express.js
- **Database**: MongoDB with Mongoose ODM
- **Authentication**: JWT (jsonwebtoken)
- **Password Hashing**: bcrypt
- **Validation**: express-validator
- **Environment Configuration**: dotenv

## Components and Interfaces

### 1. Authentication Middleware

**Purpose**: Validates JWT tokens and attaches user information to requests.

**Interface**:
```javascript
// Middleware function
authenticateToken(req, res, next)

// Input: JWT token in Authorization header (Bearer <token>)
// Output: Attaches req.user = { userId, email } or returns 401
// Side effects: None
```

**Behavior**:
- Extracts token from Authorization header
- Verifies token signature and expiration
- Decodes user information and attaches to request object
- Returns 401 if token is missing, invalid, or expired

### 2. User Controller

**Purpose**: Handles HTTP requests for user registration, authentication, and profile management.

**Endpoints**:

```javascript
POST /api/users/register
// Input: { email, password, name }
// Output: { userId, email, name, token }
// Status: 201 Created, 400 Bad Request, 409 Conflict

POST /api/users/login
// Input: { email, password }
// Output: { userId, email, name, token }
// Status: 200 OK, 401 Unauthorized

GET /api/users/profile
// Input: Authorization header with JWT
// Output: { userId, email, name, registeredAt }
// Status: 200 OK, 401 Unauthorized

PUT /api/users/profile
// Input: Authorization header, { name?, email? }
// Output: { userId, email, name }
// Status: 200 OK, 400 Bad Request, 401 Unauthorized

GET /api/users/:userId/public
// Input: userId parameter
// Output: { userId, name, registeredAt, sellerRating? }
// Status: 200 OK, 404 Not Found
```

### 3. Item Controller

**Purpose**: Handles HTTP requests for creating and retrieving auction listings.

**Endpoints**:

```javascript
POST /api/items
// Input: Authorization header, { title, description, startingPrice, endTime }
// Output: { itemId, title, description, startingPrice, currentPrice, endTime, sellerId, status, createdAt }
// Status: 201 Created, 400 Bad Request, 401 Unauthorized

GET /api/items
// Input: Optional query params: ?search=keyword&sellerId=id
// Output: { items: [{ itemId, title, description, currentPrice, endTime, sellerId, sellerName, status }] }
// Status: 200 OK

GET /api/items/:itemId
// Input: itemId parameter
// Output: { itemId, title, description, startingPrice, currentPrice, endTime, sellerId, sellerName, status, highestBidderId?, createdAt }
// Status: 200 OK, 404 Not Found
```

### 4. Bid Controller

**Purpose**: Handles HTTP requests for placing bids and retrieving bid history.

**Endpoints**:

```javascript
POST /api/bids
// Input: Authorization header, { itemId, amount }
// Output: { bidId, itemId, bidderId, amount, timestamp }
// Status: 201 Created, 400 Bad Request, 401 Unauthorized, 403 Forbidden

GET /api/bids/:itemId
// Input: itemId parameter
// Output: { bids: [{ bidId, bidderId, bidderName, amount, timestamp }] }
// Status: 200 OK, 404 Not Found
```

### 5. Transaction Controller

**Purpose**: Handles HTTP requests for retrieving transaction history.

**Endpoints**:

```javascript
GET /api/transactions
// Input: Authorization header
// Output: { transactions: [{ transactionId, itemId, itemTitle, sellerId, buyerId, finalPrice, completedAt }] }
// Status: 200 OK, 401 Unauthorized

GET /api/transactions/:transactionId
// Input: Authorization header, transactionId parameter
// Output: { transactionId, itemId, itemTitle, sellerId, sellerName, buyerId, buyerName, finalPrice, completedAt }
// Status: 200 OK, 401 Unauthorized, 404 Not Found
```

### 6. User Service

**Purpose**: Implements business logic for user management.

**Methods**:

```javascript
async registerUser(email, password, name)
// Returns: { userId, email, name, token }
// Throws: Error if email already exists

async authenticateUser(email, password)
// Returns: { userId, email, name, token }
// Throws: Error if credentials invalid

async getUserProfile(userId)
// Returns: { userId, email, name, registeredAt }
// Throws: Error if user not found

async updateUserProfile(userId, updates)
// Returns: { userId, email, name }
// Throws: Error if user not found or validation fails

async getPublicProfile(userId)
// Returns: { userId, name, registeredAt, sellerRating? }
// Throws: Error if user not found
```

### 7. Item Service

**Purpose**: Implements business logic for auction item management.

**Methods**:

```javascript
async createItem(sellerId, title, description, startingPrice, endTime)
// Returns: { itemId, title, description, startingPrice, currentPrice, endTime, sellerId, status, createdAt }
// Throws: Error if validation fails (past endTime, invalid price)

async getActiveItems(filters)
// filters: { search?, sellerId? }
// Returns: [{ itemId, title, description, currentPrice, endTime, sellerId, sellerName, status }]

async getItemById(itemId)
// Returns: { itemId, title, description, startingPrice, currentPrice, endTime, sellerId, sellerName, status, highestBidderId?, createdAt }
// Throws: Error if item not found

async checkAndCompleteExpiredAuctions()
// Returns: number of auctions completed
// Side effect: Updates auction status and creates transactions
```

### 8. Bid Service

**Purpose**: Implements business logic for bid placement and validation.

**Methods**:

```javascript
async placeBid(itemId, bidderId, amount)
// Returns: { bidId, itemId, bidderId, amount, timestamp }
// Throws: Error if bid invalid (too low, own item, expired auction)
// Side effect: Updates item's currentPrice and highestBidderId

async getBidHistory(itemId)
// Returns: [{ bidId, bidderId, bidderName, amount, timestamp }]
// Throws: Error if item not found
```

### 9. Transaction Service

**Purpose**: Implements business logic for transaction management.

**Methods**:

```javascript
async createTransaction(itemId, sellerId, buyerId, finalPrice)
// Returns: { transactionId, itemId, sellerId, buyerId, finalPrice, completedAt }
// Side effect: Creates transaction record

async getUserTransactions(userId)
// Returns: [{ transactionId, itemId, itemTitle, sellerId, buyerId, finalPrice, completedAt }]

async getTransactionById(transactionId)
// Returns: { transactionId, itemId, itemTitle, sellerId, sellerName, buyerId, buyerName, finalPrice, completedAt }
// Throws: Error if transaction not found
```

## Data Models

### User Model

```javascript
{
  _id: ObjectId,              // MongoDB generated ID
  email: String,              // Unique, required, validated format
  passwordHash: String,       // bcrypt hashed password
  name: String,               // Required
  registeredAt: Date,         // Auto-generated on creation
  createdAt: Date,            // Mongoose timestamp
  updatedAt: Date             // Mongoose timestamp
}

// Indexes:
// - email (unique)
```

### Item Model

```javascript
{
  _id: ObjectId,              // MongoDB generated ID
  title: String,              // Required
  description: String,        // Required
  startingPrice: Number,      // Required, minimum 0.01
  currentPrice: Number,       // Initialized to startingPrice
  endTime: Date,              // Required, must be future date
  sellerId: ObjectId,         // Reference to User, required
  highestBidderId: ObjectId,  // Reference to User, optional
  status: String,             // Enum: 'active', 'completed', 'expired'
  createdAt: Date,            // Mongoose timestamp
  updatedAt: Date             // Mongoose timestamp
}

// Indexes:
// - sellerId
// - status
// - endTime
// - title (text index for search)
// - description (text index for search)
```

### Bid Model

```javascript
{
  _id: ObjectId,              // MongoDB generated ID
  itemId: ObjectId,           // Reference to Item, required
  bidderId: ObjectId,         // Reference to User, required
  amount: Number,             // Required, minimum 0.01
  timestamp: Date,            // Auto-generated on creation
  createdAt: Date,            // Mongoose timestamp
  updatedAt: Date             // Mongoose timestamp
}

// Indexes:
// - itemId
// - bidderId
// - timestamp
// - Compound: (itemId, timestamp) for efficient bid history queries
```

### Transaction Model

```javascript
{
  _id: ObjectId,              // MongoDB generated ID
  itemId: ObjectId,           // Reference to Item, required
  sellerId: ObjectId,         // Reference to User, required
  buyerId: ObjectId,          // Reference to User, required
  finalPrice: Number,         // Required, minimum 0.01
  completedAt: Date,          // Auto-generated on creation
  createdAt: Date,            // Mongoose timestamp
  updatedAt: Date             // Mongoose timestamp
}

// Indexes:
// - sellerId
// - buyerId
// - itemId
// - completedAt
```

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system—essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*


### User Registration and Authentication Properties

**Property 1: Valid registration creates unique user accounts**
*For any* valid registration data (unique email, password ≥8 chars, non-empty name), registering a user should create a new user account with a unique identifier and return a valid JWT token.
**Validates: Requirements 1.1**

**Property 2: Duplicate email registration is rejected**
*For any* registered user, attempting to register again with the same email should be rejected with an appropriate error message, regardless of other field values.
**Validates: Requirements 1.2**

**Property 3: Valid credentials authenticate successfully**
*For any* registered user, logging in with the correct email and password should return a valid JWT token that identifies that user.
**Validates: Requirements 1.3**

**Property 4: Invalid credentials are rejected**
*For any* login attempt with incorrect email or password, the system should reject authentication and return an error message.
**Validates: Requirements 1.4**

**Property 5: Passwords are hashed before storage**
*For any* user registration, the password stored in the database should be a bcrypt hash, not the plaintext password.
**Validates: Requirements 1.5**

**Property 6: Token validation identifies users correctly**
*For any* valid JWT token, making an authenticated request should correctly identify the user and allow access; invalid or missing tokens should result in 401 Unauthorized responses.
**Validates: Requirements 1.6**

### User Profile Management Properties

**Property 7: Authenticated users can retrieve their profiles**
*For any* authenticated user, requesting their own profile should return their complete profile data (email, name, registration date).
**Validates: Requirements 2.1**

**Property 8: Profile updates persist correctly**
*For any* authenticated user and valid profile updates, updating the profile should persist the changes and subsequent profile retrievals should reflect the updates.
**Validates: Requirements 2.2**

**Property 9: Public profiles exclude sensitive data**
*For any* user, their public profile should only contain non-sensitive information (name, registration date) and should not include email or password hash.
**Validates: Requirements 2.3**

**Property 10: Users cannot modify other users' profiles**
*For any* two distinct users A and B, user A should not be able to modify user B's profile, and attempts should be rejected.
**Validates: Requirements 2.4**

### Item Listing Properties

**Property 11: Valid listings are created and associated with sellers**
*For any* authenticated user and valid listing data (non-empty title/description, positive starting price, future end time), creating a listing should store it with status "active", a unique ID, and associate it with the seller.
**Validates: Requirements 3.1, 3.4, 3.5**

**Property 12: Past end times are rejected**
*For any* listing creation attempt with an end time in the past, the system should reject the listing with an error message.
**Validates: Requirements 3.2**

**Property 13: Invalid prices are rejected**
*For any* listing creation attempt with a starting price ≤ 0, the system should reject the listing with an error message.
**Validates: Requirements 3.3**

**Property 14: Active listings are filtered correctly**
*For any* set of listings with various statuses and end times, requesting all active listings should return only those with status "active" and end times in the future.
**Validates: Requirements 4.1**

**Property 15: Listing retrieval includes complete details**
*For any* listing, retrieving it by ID should return all listing details including title, description, prices, end time, seller information, and current highest bid if present.
**Validates: Requirements 4.2, 4.5**

**Property 16: Keyword search returns matching listings**
*For any* search keyword, the results should include all active listings where the title or description contains that keyword (case-insensitive).
**Validates: Requirements 4.3**

**Property 17: Seller filtering returns correct listings**
*For any* seller, requesting listings by that seller ID should return all listings (regardless of status) created by that seller.
**Validates: Requirements 4.4**

### Bidding Properties

**Property 18: Valid bids update auction state**
*For any* active auction and authenticated user (not the seller), placing a bid higher than the current price should record the bid and update the auction's current price and highest bidder.
**Validates: Requirements 5.1, 5.6**

**Property 19: Low bids are rejected**
*For any* auction with current price P, placing a bid with amount ≤ P should be rejected with an error message.
**Validates: Requirements 5.2**

**Property 20: Self-bidding is prevented**
*For any* auction, the seller should not be able to place bids on their own listing, and attempts should be rejected.
**Validates: Requirements 5.3**

**Property 21: Bids on expired auctions are rejected**
*For any* auction with an end time in the past, placing a bid should be rejected with an error message.
**Validates: Requirements 5.4**

**Property 22: Successful bids are stored completely**
*For any* successful bid, the stored bid record should include the bidder ID, item ID, bid amount, and timestamp.
**Validates: Requirements 5.5**

### Auction Completion Properties

**Property 23: Auctions with bids complete and create transactions**
*For any* auction that reaches its end time with at least one bid, the system should mark it as "completed" and create a transaction record with the seller, highest bidder, item, and final price.
**Validates: Requirements 6.1, 6.3**

**Property 24: Auctions without bids expire without transactions**
*For any* auction that reaches its end time with no bids, the system should mark it as "expired" and should not create a transaction record.
**Validates: Requirements 6.2**

**Property 25: Transaction history filtering works correctly**
*For any* user, requesting their transaction history should return all transactions where they are either the buyer or seller, and should not include transactions where they are neither.
**Validates: Requirements 6.4**

**Property 26: Completed auctions are immutable**
*For any* auction with status "completed" or "expired", attempts to modify the auction (place bids, update details) should be rejected.
**Validates: Requirements 6.5**

### Bid History Properties

**Property 27: Bid history is ordered chronologically**
*For any* auction with multiple bids, requesting bid history should return all bids ordered by timestamp (earliest to latest or latest to earliest consistently).
**Validates: Requirements 7.1**

**Property 28: Bid history includes complete bid information**
*For any* bid in the history, the response should include bidder information, bid amount, and timestamp.
**Validates: Requirements 7.2**

**Property 29: Bid history is accessible for all auction states**
*For any* auction (active, completed, or expired), requesting bid history should succeed and return all bids for that auction.
**Validates: Requirements 7.3**

### API Design Properties

**Property 30: All responses are valid JSON**
*For any* API endpoint and request, the response body should be valid JSON that can be parsed without errors.
**Validates: Requirements 8.3**

**Property 31: Errors return appropriate status codes**
*For any* error condition (validation failure, not found, unauthorized, server error), the response should include an appropriate HTTP status code (400, 401, 404, 500) matching the error type.
**Validates: Requirements 8.4**

**Property 32: Protected endpoints require authentication**
*For any* protected endpoint, making a request without a valid JWT token should result in a 401 Unauthorized response.
**Validates: Requirements 8.6**

### Data Persistence Properties

**Property 33: Database failures return appropriate errors**
*For any* database operation failure (connection lost, query timeout, etc.), the system should return an appropriate error response to the client and log the failure.
**Validates: Requirements 9.6**

### Input Validation Properties

**Property 34: Invalid inputs are validated and rejected**
*For any* API endpoint, sending requests with invalid input types, formats, or missing required fields should result in validation errors with descriptive messages.
**Validates: Requirements 10.1, 10.2**

**Property 35: NoSQL injection attempts are sanitized**
*For any* user input containing MongoDB operators (e.g., $where, $ne), the system should sanitize the input to prevent NoSQL injection attacks.
**Validates: Requirements 10.3**

**Property 36: Email format validation works correctly**
*For any* email input, the system should accept valid email formats (user@domain.tld) and reject invalid formats (missing @, invalid characters, etc.).
**Validates: Requirements 10.4**

**Property 37: Password length is enforced**
*For any* password input during registration or password change, passwords with fewer than 8 characters should be rejected with an error message.
**Validates: Requirements 10.5**

**Property 38: Unauthorized access is prevented**
*For any* protected endpoint, requests without valid authentication tokens should receive 401 Unauthorized responses and should not access protected resources.
**Validates: Requirements 10.6**

## Error Handling

### Error Response Format

All error responses follow a consistent JSON structure:

```javascript
{
  error: {
    code: String,        // Machine-readable error code (e.g., "INVALID_BID")
    message: String,     // Human-readable error message
    details?: Object     // Optional additional error details
  }
}
```

### Error Categories

**Validation Errors (400 Bad Request)**:
- Invalid input format or type
- Missing required fields
- Business rule violations (e.g., bid too low, past end time)
- Example codes: `INVALID_INPUT`, `INVALID_BID`, `INVALID_PRICE`, `PAST_END_TIME`

**Authentication Errors (401 Unauthorized)**:
- Missing authentication token
- Invalid or expired token
- Invalid credentials
- Example codes: `MISSING_TOKEN`, `INVALID_TOKEN`, `INVALID_CREDENTIALS`

**Authorization Errors (403 Forbidden)**:
- Attempting to bid on own listing
- Attempting to modify another user's profile
- Example codes: `SELF_BIDDING_NOT_ALLOWED`, `UNAUTHORIZED_MODIFICATION`

**Not Found Errors (404 Not Found)**:
- Requested resource doesn't exist
- Example codes: `USER_NOT_FOUND`, `ITEM_NOT_FOUND`, `TRANSACTION_NOT_FOUND`

**Conflict Errors (409 Conflict)**:
- Email already registered
- Example codes: `EMAIL_ALREADY_EXISTS`

**Server Errors (500 Internal Server Error)**:
- Database connection failures
- Unexpected errors
- Example codes: `DATABASE_ERROR`, `INTERNAL_ERROR`

### Error Handling Strategy

1. **Input Validation**: Use express-validator middleware to validate all inputs at the controller layer before processing
2. **Try-Catch Blocks**: Wrap all async operations in try-catch blocks to handle unexpected errors
3. **Custom Error Classes**: Define custom error classes for different error types to standardize error handling
4. **Error Middleware**: Implement Express error handling middleware to catch and format all errors consistently
5. **Logging**: Log all errors with appropriate severity levels (error, warn, info) for debugging and monitoring
6. **Database Errors**: Catch MongoDB errors and translate them into user-friendly messages (e.g., duplicate key → "Email already exists")

## Testing Strategy

### Dual Testing Approach

The system will be validated using both **unit tests** and **property-based tests**, which are complementary and both necessary for comprehensive coverage:

- **Unit tests**: Verify specific examples, edge cases, and error conditions with concrete test data
- **Property tests**: Verify universal properties across randomly generated inputs to catch edge cases we might not think of

### Unit Testing

**Focus Areas**:
- Specific examples demonstrating correct behavior (e.g., "user can register with email test@example.com")
- Edge cases (e.g., empty strings, boundary values, special characters)
- Error conditions (e.g., database connection failures, invalid tokens)
- Integration points between components (e.g., controller → service → database)

**Testing Framework**: Jest with Supertest for API testing

**Example Unit Tests**:
- User can register with valid data
- Login fails with wrong password
- Cannot create listing with negative price
- Bid history returns empty array for auction with no bids
- Database connection failure returns 500 error

### Property-Based Testing

**Focus Areas**:
- Universal properties that hold for all inputs (e.g., "for any valid registration data, a user account is created")
- Comprehensive input coverage through randomization
- Invariants that must always hold (e.g., "current price is always ≥ starting price")
- Round-trip properties (e.g., "create then retrieve returns equivalent data")

**Testing Framework**: fast-check (JavaScript property-based testing library)

**Configuration**:
- Minimum **100 iterations** per property test (due to randomization)
- Each test tagged with: **Feature: auction-portal-backend, Property {number}: {property_text}**
- Each correctness property implemented by a SINGLE property-based test

**Example Property Tests**:
- Property 1: For any valid registration data, user account is created with unique ID
- Property 18: For any valid bid higher than current price, auction state updates correctly
- Property 23: For any auction reaching end time with bids, transaction is created
- Property 34: For any invalid input, validation error is returned

### Test Organization

```
tests/
├── unit/
│   ├── controllers/
│   │   ├── userController.test.js
│   │   ├── itemController.test.js
│   │   ├── bidController.test.js
│   │   └── transactionController.test.js
│   ├── services/
│   │   ├── userService.test.js
│   │   ├── itemService.test.js
│   │   ├── bidService.test.js
│   │   └── transactionService.test.js
│   └── middleware/
│       └── auth.test.js
├── property/
│   ├── userProperties.test.js
│   ├── itemProperties.test.js
│   ├── bidProperties.test.js
│   ├── transactionProperties.test.js
│   └── validationProperties.test.js
└── integration/
    └── api.test.js
```

### Test Data Management

- **Unit tests**: Use predefined test data and fixtures
- **Property tests**: Use fast-check arbitraries to generate random valid and invalid data
- **Test database**: Use separate MongoDB instance or in-memory MongoDB for testing
- **Cleanup**: Clear database between tests to ensure isolation

### Coverage Goals

- **Line coverage**: Minimum 80%
- **Branch coverage**: Minimum 75%
- **Property coverage**: All 38 correctness properties implemented as property tests
- **Critical paths**: 100% coverage for authentication, bidding, and transaction logic
