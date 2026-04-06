# Backend Testing Guide

## Server Status
✅ Backend server is running on http://localhost:8000

## Test the Backend

### 1. Check Server Health
Open your browser or use curl:
```
http://localhost:8000/health
```

### 2. Create Test Accounts

#### Create Seller Account
```bash
curl -X POST http://localhost:8000/api/users/register ^
  -H "Content-Type: application/json" ^
  -d "{\"email\":\"seller@test.com\",\"password\":\"password123\",\"name\":\"Test Seller\",\"role\":\"seller\"}"
```

#### Create Buyer Account
```bash
curl -X POST http://localhost:8000/api/users/register ^
  -H "Content-Type: application/json" ^
  -d "{\"email\":\"buyer@test.com\",\"password\":\"password123\",\"name\":\"Test Buyer\",\"role\":\"buyer\"}"
```

### 3. Login as Seller
```bash
curl -X POST http://localhost:8000/api/users/login ^
  -H "Content-Type: application/json" ^
  -d "{\"email\":\"seller@test.com\",\"password\":\"password123\"}"
```

Copy the token from the response.

### 4. Create Test Item (as Seller)
```bash
curl -X POST http://localhost:8000/api/items ^
  -H "Content-Type: application/json" ^
  -H "Authorization: Bearer YOUR_TOKEN_HERE" ^
  -d "{\"title\":\"Vintage Watch\",\"description\":\"Beautiful vintage watch from 1960s\",\"startingPrice\":100,\"endTime\":\"2026-03-01 23:59:59\"}"
```

### 5. View All Items
```bash
curl http://localhost:8000/api/items
```

## Flutter App Testing Steps

### Step 1: Run the Flutter App
Since I cannot start the Flutter app directly, please run it manually:

```bash
cd BidOrbit/bidorbit
flutter run -d windows
```

Or if you have an Android emulator:
```bash
flutter run -d emulator-5554
```

### Step 2: Test Seller Flow
1. Open the app
2. Click "Sign Up"
3. Fill in the form:
   - Name: Test Seller
   - Email: seller2@test.com
   - Phone: 1234567890
   - Password: password123
   - Role: Select "Seller"
4. Click "Sign Up"
5. You should be redirected to the Seller Dashboard
6. Click on "Add Item" or navigate to add item screen
7. Fill in the item details:
   - Title: Vintage Camera
   - Description: Classic 1980s film camera
   - Category: Electronics
   - Condition: Good
   - Starting Bid: $50
   - Select start and end dates
   - Add at least one image
8. Click "List Item for Auction"

### Step 3: Test Buyer Flow
1. Logout from seller account
2. Click "Sign Up" again
3. Fill in the form:
   - Name: Test Buyer
   - Email: buyer2@test.com
   - Phone: 0987654321
   - Password: password123
   - Role: Select "Buyer"
4. Click "Sign Up"
5. You should see the home screen with items
6. Browse the items (you should see the item created by the seller)
7. Click on an item to view details
8. Click "Place Bid"
9. Enter a bid amount (must be higher than current price)
10. Check the terms checkbox
11. Click "Confirm Bid"

### Step 4: Verify
1. Go back to home screen
2. The item should show updated bid count
3. The current price should be updated

## Troubleshooting

### If you see "No internet connection" error:
- Make sure the backend server is running (check the terminal)
- Verify the API URL in `lib/config/api_config.dart`
- For Android emulator, use `10.0.2.2:8000` instead of `localhost:8000`

### If images don't upload:
- Check that the backend has write permissions to the uploads directory
- Verify the multipart/form-data is being sent correctly

### If authentication fails:
- Check that the token is being saved in secure storage
- Verify the Authorization header is being sent with requests

## Current Configuration

- Backend API: http://localhost:8000/api
- Database: SQLite (./database/auction_portal.sqlite)
- JWT Secret: your-secret-key-here-change-in-production

## Next Steps After Testing

1. Test all CRUD operations
2. Test bid placement and updates
3. Test image upload
4. Test search and filtering
5. Test pagination
6. Add error handling improvements
7. Add loading states
8. Implement watchlist functionality
9. Add real-time updates with WebSocket
