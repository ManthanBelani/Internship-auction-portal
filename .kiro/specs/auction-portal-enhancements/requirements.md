# Requirements Document

## Introduction

This document specifies requirements for enhancing an existing PHP/MySQL auction portal backend with six major features: image uploads, user ratings and reviews, watchlist functionality, commission/fee system, reserve pricing, and real-time WebSocket updates. These enhancements will improve user experience, platform monetization, and real-time engagement while maintaining backward compatibility with the existing RESTful API.

## Glossary

- **System**: The auction portal backend application
- **Seller**: A user who creates auction items for sale
- **Bidder**: A user who places bids on auction items
- **Item**: An auction listing created by a seller
- **Transaction**: A completed auction sale record
- **Watchlist**: A user's collection of favorited auction items
- **Reserve_Price**: A hidden minimum acceptable price set by the seller
- **Commission**: A percentage fee charged by the platform on completed sales
- **WebSocket_Server**: The real-time communication server using Ratchet PHP
- **Thumbnail**: A reduced-size version of an uploaded image
- **Rating**: A 1-5 star score given by one user to another
- **Review**: Written feedback accompanying a rating

## Requirements

### Requirement 1: Image Upload for Auction Items

**User Story:** As a seller, I want to upload multiple images for my auction items, so that bidders can see detailed photos of what I'm selling.

#### Acceptance Criteria

1. WHEN a seller uploads an image for an item, THE System SHALL validate that the file format is JPG, PNG, or WEBP
2. WHEN a seller uploads an image, THE System SHALL store the original file in the uploads/ directory with a unique filename
3. WHEN an image is uploaded, THE System SHALL generate a thumbnail version for performance optimization
4. WHEN a seller uploads images for an item, THE System SHALL associate multiple images with a single item
5. WHEN item data is requested via API, THE System SHALL include all image URLs in the JSON response
6. WHEN an invalid file format is uploaded, THE System SHALL return an error message and reject the upload
7. WHEN an image is successfully uploaded, THE System SHALL return the image URL and thumbnail URL

### Requirement 2: User Ratings and Reviews

**User Story:** As a user, I want to rate and review other users after transactions, so that the community can build trust and reputation.

#### Acceptance Criteria

1. WHEN a transaction is completed, THE System SHALL allow both the seller and bidder to rate each other
2. WHEN a user submits a rating, THE System SHALL validate that the rating is between 1 and 5 stars
3. WHEN a user submits a review, THE System SHALL store both the star rating and written feedback text
4. WHEN a user attempts to review the same transaction twice, THE System SHALL prevent the duplicate review
5. WHEN a user's profile is requested, THE System SHALL calculate and return their average rating from all received reviews
6. WHEN a user's profile is viewed, THE System SHALL display all reviews and ratings they have received
7. WHEN calculating average rating, THE System SHALL include only completed reviews with valid star ratings

### Requirement 3: Watchlist and Favorites

**User Story:** As a bidder, I want to add items to my watchlist, so that I can easily track auctions I'm interested in.

#### Acceptance Criteria

1. WHEN a user adds an item to their watchlist, THE System SHALL create a watchlist entry associating the user with the item
2. WHEN a user requests their watchlist, THE System SHALL return all items they have favorited
3. WHEN a user removes an item from their watchlist, THE System SHALL delete the watchlist entry
4. WHEN a user attempts to add the same item to their watchlist twice, THE System SHALL prevent duplicate entries
5. WHEN a watched item is ending within 24 hours, THE System SHALL generate a notification for the watching user
6. WHEN a user views an item, THE System SHALL indicate whether that item is in their watchlist

### Requirement 4: Commission and Fee System

**User Story:** As a platform administrator, I want to charge commission on completed sales, so that the platform can generate revenue.

#### Acceptance Criteria

1. WHEN an auction completes with a winning bid, THE System SHALL calculate commission as a percentage of the final sale price
2. WHERE no custom rate is configured, THE System SHALL apply a default commission rate of 5%
3. WHEN a transaction is created, THE System SHALL store the commission amount separately from the sale price
4. WHEN transaction details are requested, THE System SHALL include a breakdown showing sale price, commission amount, and seller payout
5. WHEN calculating seller payout, THE System SHALL subtract the commission from the final sale price
6. WHEN querying platform earnings, THE System SHALL aggregate all commission amounts from completed transactions

### Requirement 5: Reserve Price

**User Story:** As a seller, I want to set a hidden reserve price, so that my item won't sell below my minimum acceptable price.

#### Acceptance Criteria

1. WHEN a seller creates an item, THE System SHALL allow the seller to optionally set a reserve price
2. WHEN an auction completes, THE System SHALL compare the highest bid to the reserve price
3. IF the highest bid is below the reserve price, THEN THE System SHALL mark the auction as "Reserve not met" and not create a transaction
4. WHEN a bidder views an item with a reserve price, THE System SHALL indicate whether the reserve has been met without revealing the actual reserve amount
5. WHEN a seller views their own item, THE System SHALL display the reserve price they set
6. WHEN a bidder views an item, THE System SHALL not display the reserve price value
7. WHEN the highest bid meets or exceeds the reserve price, THE System SHALL indicate "Reserve met" status

### Requirement 6: WebSocket Real-Time Updates

**User Story:** As a bidder, I want to see live updates when bids are placed, so that I can respond quickly without refreshing the page.

#### Acceptance Criteria

1. WHEN the WebSocket_Server starts, THE System SHALL establish a connection using the Ratchet PHP library
2. WHEN a new bid is placed, THE System SHALL broadcast a real-time notification to all connected clients watching that item
3. WHEN a bid is placed on an item a user is watching, THE System SHALL send an "outbid" notification to the previous highest bidder
4. WHEN an auction is ending within 5 minutes, THE System SHALL broadcast countdown updates every 30 seconds
5. WHEN a client connects to the WebSocket_Server, THE System SHALL authenticate the connection using JWT tokens
6. WHEN broadcasting bid updates, THE System SHALL include the new price, bidder information, and timestamp
7. WHEN an auction ends, THE System SHALL broadcast a final notification to all watching clients

### Requirement 7: Database Schema Extensions

**User Story:** As a developer, I want proper database schema for new features, so that data is stored efficiently and maintains referential integrity.

#### Acceptance Criteria

1. THE System SHALL create an item_images table with columns for image_id, item_id, image_url, thumbnail_url, and upload_timestamp
2. THE System SHALL create a reviews table with columns for review_id, transaction_id, reviewer_id, reviewee_id, rating, review_text, and created_at
3. THE System SHALL create a watchlist table with columns for watchlist_id, user_id, item_id, and added_at
4. THE System SHALL add a reserve_price column to the items table
5. THE System SHALL add a commission_rate column to the items table
6. THE System SHALL add commission_amount and seller_payout columns to the transactions table
7. THE System SHALL add a reserve_met boolean column to the items table
8. WHEN creating foreign keys, THE System SHALL ensure referential integrity with existing tables

### Requirement 8: API Backward Compatibility

**User Story:** As an API consumer, I want existing endpoints to continue working, so that my current integrations don't break.

#### Acceptance Criteria

1. WHEN existing API endpoints are called with previous request formats, THE System SHALL process them successfully
2. WHEN new fields are added to responses, THE System SHALL include them as additional properties without removing existing fields
3. WHEN new optional parameters are added to endpoints, THE System SHALL maintain default behavior when parameters are omitted
4. WHEN authentication is required, THE System SHALL continue using the existing JWT authentication mechanism
5. WHEN response formats are extended, THE System SHALL maintain the existing JSON structure as the base

### Requirement 9: File Upload Security

**User Story:** As a system administrator, I want secure file uploads, so that malicious files cannot compromise the system.

#### Acceptance Criteria

1. WHEN a file is uploaded, THE System SHALL validate the file extension against an allowlist of JPG, PNG, and WEBP
2. WHEN a file is uploaded, THE System SHALL verify the MIME type matches the file extension
3. WHEN generating filenames, THE System SHALL use unique identifiers to prevent path traversal attacks
4. WHEN storing files, THE System SHALL set appropriate file permissions to prevent execution
5. WHEN a file exceeds the maximum size limit, THE System SHALL reject the upload and return an error
6. THE System SHALL enforce a maximum file size of 5MB per image upload

### Requirement 10: Real-Time Notification Delivery

**User Story:** As a bidder, I want reliable notifications, so that I don't miss important auction events.

#### Acceptance Criteria

1. WHEN a WebSocket connection is lost, THE System SHALL attempt to reconnect automatically
2. WHEN a notification cannot be delivered via WebSocket, THE System SHALL queue the notification for delivery upon reconnection
3. WHEN multiple events occur rapidly, THE System SHALL batch notifications to prevent overwhelming clients
4. WHEN a client subscribes to item updates, THE System SHALL only send notifications relevant to that client's watched items
5. WHEN a notification is sent, THE System SHALL include a unique event ID to prevent duplicate processing
