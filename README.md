# Auction Portal Backend

A RESTful API backend for an auction portal built with PHP and MySQL.

## Features

### Core Auction Features
- User registration and authentication with JWT
- Item listing creation and management
- Real-time bidding system
- Automatic auction completion
- Transaction management
- RESTful API design

### Enhanced Features
- **Multi-Image Upload**: Upload multiple images per auction item with automatic thumbnail generation (JPG, PNG, WEBP supported)
- **User Ratings & Reviews**: Rate and review other users after transactions to build trust and reputation (1-5 star ratings)
- **Watchlist/Favorites**: Add items to your watchlist to track auctions you're interested in
- **Commission/Fee System**: Configurable commission rates on completed sales (default 5%)
- **Reserve Price**: Set hidden minimum acceptable prices for auction items
- **Real-Time Updates**: WebSocket-based live notifications for bids, outbid alerts, and auction endings
- **Admin Dashboard**: Full-featured admin panel for user management, content moderation, and platform statistics
- **Role-Based Access Control**: Four user roles (Admin, Moderator, Seller, Buyer) with granular permissions

## Requirements

- PHP 8.1 or higher
- MySQL 5.7 or higher
- Composer
- PHP GD Library (for image processing)
- PHP Ratchet (for WebSocket server)

## Installation

1. Clone the repository
2. Install dependencies:
   ```bash
   composer install
   ```

3. Copy `.env.example` to `.env` and configure your database:
   ```bash
   cp .env.example .env
   ```

4. Create the uploads directories:
   ```bash
   mkdir -p uploads/thumbnails
   chmod 755 uploads
   chmod 755 uploads/thumbnails
   ```

5. Create the database:
   ```sql
   CREATE DATABASE auction_portal;
   ```

6. Run migrations:
   ```bash
   mysql -u root -p auction_portal < database/migrations/001_create_users_table.sql
   mysql -u root -p auction_portal < database/migrations/002_create_items_table.sql
   mysql -u root -p auction_portal < database/migrations/003_create_bids_table.sql
   mysql -u root -p auction_portal < database/migrations/004_create_transactions_table.sql
   mysql -u root -p auction_portal < database/migrations/005_create_item_images_table.sql
   mysql -u root -p auction_portal < database/migrations/006_create_reviews_table.sql
   mysql -u root -p auction_portal < database/migrations/007_create_watchlist_table.sql
   mysql -u root -p auction_portal < database/migrations/008_alter_items_add_reserve_commission.sql
   mysql -u root -p auction_portal < database/migrations/009_alter_transactions_add_commission.sql
   mysql -u root -p auction_portal < database/migrations/010_create_notifications_table.sql
   mysql -u root -p auction_portal < database/migrations/011_add_role_to_users.sql
   ```

7. Create admin user for dashboard access:
   ```bash
   mysql -u root -p auction_portal < database/create_admin_user.sql
   ```

## Running the Server

### Main API Server

Start the development server:
```bash
composer start
```

Or use PHP's built-in server:
```bash
php -S localhost:8000 -t public
```

The API will be available at `http://localhost:8000`

### WebSocket Server (Optional - for Real-Time Updates)

To enable real-time bid notifications and auction updates, start the WebSocket server in a separate terminal:

```bash
php bin/websocket-server.php
```

The WebSocket server will run on port 8080 by default (configurable via `WS_PORT` in `.env`).

**Note**: The WebSocket server is optional. The main API will work without it, but real-time features will not be available.

## Admin Dashboard

A comprehensive admin dashboard is available for platform management.

### Access the Dashboard

1. Navigate to: `http://localhost/admin/login.php`
2. Login with default credentials:
   - Email: `admin@auction.com`
   - Password: `admin123`
3. **⚠️ Change the default password immediately after first login!**

### Dashboard Features

- **Statistics Overview**: Real-time platform metrics (users, items, transactions, earnings)
- **User Management**: View, filter, suspend, ban, and manage user roles
- **Item Management**: View and moderate auction listings
- **Interactive Charts**: Visual analytics with Chart.js
- **Role-Based Access**: Different permissions for Admin vs Moderator roles

### User Roles

- **Admin**: Full system access, manage users/roles, view earnings, ban users
- **Moderator**: Content moderation, suspend users, delete items (no financial access)
- **Seller**: Create items, upload images, manage own listings
- **Buyer**: Place bids, watchlist, reviews

For detailed dashboard documentation, see:
- [Admin Dashboard Setup Guide](ADMIN_DASHBOARD_SETUP.md)
- [Admin Dashboard Features](admin/FEATURES.md)
- [RBAC Implementation](RBAC_IMPLEMENTATION_SUMMARY.md)

## API Endpoints

### Health Check
- `GET /health` - Check if the server is running

### User Management
- `POST /api/users/register` - Register a new user
- `POST /api/users/login` - Login and get JWT token
- `GET /api/users/profile` - Get user profile (protected)
- `PUT /api/users/profile` - Update user profile (protected)
- `GET /api/users/:userId/public` - Get public user profile

### Item Management
- `POST /api/items` - Create new auction listing (protected)
- `GET /api/items` - Get all active listings
- `GET /api/items/:itemId` - Get specific listing details

### Image Management
- `POST /api/items/:itemId/images` - Upload image for item (protected)
- `GET /api/items/:itemId/images` - Get all images for item
- `DELETE /api/images/:imageId` - Delete specific image (protected)

### Bidding
- `POST /api/bids` - Place a bid (protected)
- `GET /api/bids/:itemId` - Get bid history for an item

### Transactions
- `GET /api/transactions` - Get user's transaction history (protected)
- `GET /api/transactions/:transactionId` - Get transaction details (protected)

### Reviews (Coming Soon)
- `POST /api/reviews` - Create a review (protected)
- `GET /api/users/:userId/reviews` - Get reviews for a user
- `GET /api/users/:userId/rating` - Get average rating for a user

### Watchlist (Coming Soon)
- `POST /api/watchlist` - Add item to watchlist (protected)
- `DELETE /api/watchlist/:itemId` - Remove item from watchlist (protected)
- `GET /api/watchlist` - Get user's watchlist (protected)
- `GET /api/watchlist/check/:itemId` - Check if watching item (protected)

**For detailed API documentation with request/response examples, see [API_ENDPOINTS.md](API_ENDPOINTS.md)**

## Testing

Run tests:
```bash
composer test
```

## Project Structure

```
.
├── public/              # Public web root
│   └── index.php       # Application entry point
├── src/
│   ├── Config/         # Configuration classes
│   ├── Controllers/    # API controllers
│   ├── Middleware/     # Middleware classes
│   ├── Models/         # Data models
│   ├── Services/       # Business logic
│   ├── Utils/          # Utility classes
│   └── WebSocket/      # WebSocket server (optional)
├── database/
│   └── migrations/     # SQL migration files
├── tests/              # Test files
│   ├── Unit/          # Unit tests
│   ├── Property/      # Property-based tests
│   └── Integration/   # Integration tests
├── uploads/           # Uploaded images
│   └── thumbnails/    # Generated thumbnails
├── bin/               # Executable scripts
│   └── websocket-server.php  # WebSocket server
├── .env.example       # Environment variables template
├── composer.json      # PHP dependencies
└── phpunit.xml        # PHPUnit configuration
```

## Feature Details

### Image Upload System
- **Supported Formats**: JPG, JPEG, PNG, WEBP
- **Maximum File Size**: 5MB (configurable)
- **Automatic Thumbnails**: 200x200px thumbnails generated automatically
- **Multiple Images**: Upload multiple images per auction item
- **Security**: File validation, MIME type checking, secure filename generation

### User Ratings & Reviews
- **Star Ratings**: 1-5 star rating system
- **Written Reviews**: Optional text feedback
- **Average Ratings**: Automatically calculated from all reviews
- **Duplicate Prevention**: Users can only review each transaction once
- **Mutual Reviews**: Both buyers and sellers can review each other

### Watchlist/Favorites
- **Track Auctions**: Add items to your personal watchlist
- **Ending Soon Alerts**: Get notified when watched items are ending within 24 hours
- **Quick Access**: View all your watched items in one place
- **Duplicate Prevention**: Can't add the same item twice

### Commission System
- **Configurable Rates**: Set custom commission rates per item
- **Default Rate**: 5% commission on all sales (configurable)
- **Transparent Breakdown**: Transaction details show sale price, commission, and seller payout
- **Platform Earnings**: Track total platform revenue from commissions

### Reserve Price
- **Hidden Minimum**: Set a minimum acceptable price without revealing it to bidders
- **Reserve Met Indicator**: Bidders see if reserve is met, but not the actual amount
- **Seller Visibility**: Sellers can see their own reserve prices
- **Automatic Enforcement**: Auctions below reserve don't create transactions

### Real-Time WebSocket Updates
- **Live Bid Notifications**: See new bids instantly without refreshing
- **Outbid Alerts**: Get notified immediately when someone outbids you
- **Auction Ending Countdown**: Real-time countdown for auctions ending within 5 minutes
- **JWT Authentication**: Secure WebSocket connections using existing JWT tokens
- **Subscription-Based**: Only receive updates for items you're watching

## Environment Variables

### Application Configuration
- `APP_ENV` - Application environment (development/production)
- `APP_DEBUG` - Enable debug mode (true/false)

### Database Configuration
- `DB_HOST` - Database host
- `DB_PORT` - Database port
- `DB_NAME` - Database name
- `DB_USER` - Database username
- `DB_PASSWORD` - Database password

### Authentication
- `JWT_SECRET` - Secret key for JWT tokens
- `JWT_EXPIRES_IN` - Token expiration time in seconds (default: 604800 = 7 days)

### File Upload Configuration
- `UPLOAD_DIR` - Directory for uploaded images (default: uploads/)
- `THUMBNAIL_DIR` - Directory for thumbnail images (default: uploads/thumbnails/)
- `MAX_FILE_SIZE` - Maximum file size in bytes (default: 5242880 = 5MB)

### Commission Configuration
- `DEFAULT_COMMISSION_RATE` - Default commission rate as decimal (default: 0.05 = 5%)

### WebSocket Configuration
- `WS_PORT` - WebSocket server port (default: 8080)
- `WS_HOST` - WebSocket server host (default: 0.0.0.0)

## License

MIT
