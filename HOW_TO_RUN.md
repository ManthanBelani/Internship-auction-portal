# How to Run the Auction Portal Backend

This guide will help you set up and run the PHP auction portal backend server on your local machine.

## Prerequisites

Before you start, make sure you have:
- **XAMPP** installed (includes PHP, MySQL, and Apache)
- **Composer** installed (PHP package manager)
- A text editor or IDE

## Step-by-Step Setup

### 1. Install XAMPP

If you haven't already:
1. Download XAMPP from https://www.apachefriends.org/
2. Install XAMPP (default installation is fine)
3. Start the XAMPP Control Panel

### 2. Start Required Services

Open XAMPP Control Panel and start:
- **Apache** (web server)
- **MySQL** (database server)

Both should show green "Running" status.

### 3. Set Up the Database

#### Option A: Using phpMyAdmin (Recommended for beginners)
1. Open your browser and go to: `http://localhost/phpmyadmin`
2. Click "New" in the left sidebar
3. Create a database named: `auction_portal`
4. Click "Create"

#### Option B: Using MySQL Command Line
```bash
mysql -u root -p
CREATE DATABASE auction_portal;
EXIT;
```

### 4. Configure Environment Variables

1. Copy the `.env.example` file to `.env`:
   ```bash
   copy .env.example .env
   ```

2. Open `.env` and update the database settings:
   ```
   DB_HOST=localhost
   DB_NAME=auction_portal
   DB_USER=root
   DB_PASSWORD=
   JWT_SECRET=your-secret-key-here-change-this-in-production
   ```

   **Note:** Default XAMPP MySQL has no password for root user. If you set a password, update `DB_PASSWORD`.

### 5. Install PHP Dependencies

Open Command Prompt in your project directory and run:
```bash
composer install
```

This will install all required packages (JWT, PHPUnit, etc.).

### 6. Run Database Migrations

Create the database tables by running:
```bash
php database/migrate.php
```

You should see messages confirming that all 4 tables were created successfully.

### 7. Start the PHP Development Server

Run the following command in your project directory:
```bash
php -S localhost:8000 -t public
```

You should see:
```
PHP 8.x Development Server (http://localhost:8000) started
```

## Verify the Server is Running

Open your browser or use a tool like Postman to test:

**Health Check:**
```
GET http://localhost:8000/api/health
```

You should get a response like:
```json
{
  "status": "ok",
  "message": "Auction Portal API is running"
}
```

## Common Issues and Solutions

### Issue: "Port 8000 is already in use"
**Solution:** Use a different port:
```bash
php -S localhost:8080 -t public
```

### Issue: "Connection refused" or database errors
**Solution:** 
1. Make sure MySQL is running in XAMPP Control Panel
2. Verify your `.env` database credentials are correct
3. Check that the `auction_portal` database exists

### Issue: "Class not found" errors
**Solution:** Run composer install again:
```bash
composer install
```

### Issue: "Permission denied" errors
**Solution:** Make sure you're running the command prompt as Administrator

## Testing the API

### Register a New User
```bash
curl -X POST http://localhost:8000/api/users/register ^
  -H "Content-Type: application/json" ^
  -d "{\"email\":\"test@example.com\",\"password\":\"password123\",\"name\":\"Test User\"}"
```

### Login
```bash
curl -X POST http://localhost:8000/api/users/login ^
  -H "Content-Type: application/json" ^
  -d "{\"email\":\"test@example.com\",\"password\":\"password123\"}"
```

Save the `token` from the response - you'll need it for authenticated requests.

### Create an Auction Item (requires authentication)
```bash
curl -X POST http://localhost:8000/api/items ^
  -H "Content-Type: application/json" ^
  -H "Authorization: Bearer YOUR_TOKEN_HERE" ^
  -d "{\"title\":\"Vintage Watch\",\"description\":\"Beautiful vintage watch\",\"startingPrice\":100,\"endTime\":\"2026-02-15T12:00:00Z\"}"
```

## Running Tests

To run the test suite:
```bash
vendor/bin/phpunit
```

You should see all tests passing (50 tests, 1,545 assertions).

## Setting Up Automatic Auction Completion

To automatically complete expired auctions, set up a scheduled task:

### Windows Task Scheduler
1. Open Task Scheduler
2. Create a new task
3. Set trigger: Every 5 minutes (or your preferred interval)
4. Set action: Run program
   - Program: `C:\xampp\php\php.exe`
   - Arguments: `C:\path\to\your\project\cron\complete_auctions.php`

### Manual Execution
You can also run it manually anytime:
```bash
php cron/complete_auctions.php
```

## API Documentation

For complete API documentation with all endpoints, see:
- `API_ENDPOINTS.md` - Complete endpoint reference
- `DYNAMIC_PRICES_GUIDE.md` - Real-time price updates guide

## Stopping the Server

To stop the PHP development server:
1. Go to the Command Prompt window where the server is running
2. Press `Ctrl + C`

To stop XAMPP services:
1. Open XAMPP Control Panel
2. Click "Stop" for Apache and MySQL

## Production Deployment

For production deployment:
1. Change `JWT_SECRET` to a strong random string
2. Set proper database credentials
3. Use a production web server (Apache/Nginx)
4. Enable HTTPS
5. Set up proper error logging
6. Configure firewall rules

## Need Help?

If you encounter any issues:
1. Check the XAMPP error logs in `C:\xampp\apache\logs\error.log`
2. Check PHP error logs
3. Verify all XAMPP services are running
4. Make sure your `.env` file is configured correctly

## Quick Start Summary

```bash
# 1. Start XAMPP (Apache + MySQL)
# 2. Create database 'auction_portal' in phpMyAdmin
# 3. Configure .env file
composer install
php database/migrate.php
php -S localhost:8000 -t public
# 4. Test: http://localhost:8000/api/health
```

Your auction portal backend is now running! 🚀
