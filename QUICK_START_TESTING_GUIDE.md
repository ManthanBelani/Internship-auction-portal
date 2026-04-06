# 🚀 Quick Start Testing Guide

## Getting Started with the Complete Buyer Side

---

## 📋 Prerequisites

1. PHP 8.0+ installed
2. Flutter SDK installed
3. Android Studio / Xcode (for mobile testing)
4. Postman (optional, for API testing)

---

## 🔧 Backend Setup

### 1. Run Database Migration

```bash
cd "Final Internship Project"

# Connect to SQLite database
sqlite3 database/auction_portal.sqlite

# Run the migration
.read database/migrations/add_payment_shipping_orders.sql

# Verify tables were created
.tables

# Exit SQLite
.exit
```

### 2. Start PHP Server

```bash
# Start on your local IP (replace with your IP)
php -S 10.205.162.238:8000 -t public

# Or start on localhost
php -S localhost:8000 -t public
```

You should see:
```
[Sat Feb 22 10:00:00 2026] PHP 8.x Development Server (http://10.205.162.238:8000) started
```

### 3. Test Backend Endpoints

Open browser or Postman and test:

```
http://10.205.162.238:8000/
```

Should return:
```json
{
  "message": "Auction Portal API",
  "version": "1.1.0",
  "technology": "PHP + SQLite"
}
```

---

## 📱 Flutter App Setup

### 1. Install Dependencies

```bash
cd BidOrbit/bidorbit

# Get Flutter packages
flutter pub get
```

### 2. Update API Configuration

The API config is already set to your IP: `10.205.162.238`

File: `lib/config/api_config.dart`

### 3. Run the App

```bash
# For Android device/emulator
flutter run

# For iOS simulator
flutter run -d ios

# For web
flutter run -d chrome
```

---

## 🧪 Testing the Complete Flow

### Test Scenario 1: User Registration & Login

1. **Register a new buyer account**
   - Open app
   - Click "Register"
   - Fill in details (role: buyer)
   - Submit

2. **Login**
   - Use registered credentials
   - Should auto-navigate to home screen

### Test Scenario 2: Browse & Bid

1. **Browse items**
   - View items on home screen
   - Use search functionality
   - Filter by category

2. **Place a bid**
   - Click on an item
   - View item details
   - Click "Place Bid"
   - Enter bid amount
   - Confirm bid

3. **Add to watchlist**
   - Click heart icon on any item
   - Navigate to Favourite tab
   - Verify item appears

### Test Scenario 3: Win Auction & Checkout

1. **Simulate winning an auction**
   - Use admin panel or database to end an auction where you're the highest bidder
   - Or wait for auction to end naturally

2. **View won items**
   - Navigate to Profile → Won Items
   - Should see the won item

3. **Add shipping address**
   - Click on won item
   - Click "Add Shipping Address"
   - Fill in address details
   - Save

4. **Add payment method**
   - In checkout screen
   - Click "Add Payment Method"
   - Enter card details (test card):
     - Number: 4242 4242 4242 4242
     - Expiry: 12/25
     - CVV: 123
   - Save

5. **Complete checkout**
   - Review order summary
   - Click "Pay $XX.XX"
   - Should see order confirmation

### Test Scenario 4: Order Management

1. **View orders**
   - Navigate to Profile → Orders
   - Should see the order in "Paid" tab

2. **View order details**
   - Click on the order
   - View timeline, shipping address, price breakdown

3. **Cancel order** (if pending)
   - Click "Cancel Order"
   - Confirm cancellation

### Test Scenario 5: Profile Management

1. **Edit profile**
   - Navigate to Profile
   - Click "Edit Profile"
   - Update name/email
   - Save changes

2. **Manage addresses**
   - Navigate to Settings → Shipping Addresses
   - Add new address
   - Edit existing address
   - Delete address
   - Set default address

3. **Manage payment methods**
   - Navigate to Settings → Payment Methods
   - Add new card
   - Delete card

---

## 🔍 API Testing with Postman

### Import Collection

Use the file: `Auction_Portal_Postman_Collection.json`

### Test New Endpoints

#### 1. Create Payment Intent
```
POST http://10.205.162.238:8000/api/payments/create-intent
Headers:
  Authorization: Bearer YOUR_TOKEN
Body:
{
  "itemId": 1,
  "amount": 100.00
}
```

#### 2. Get Shipping Addresses
```
GET http://10.205.162.238:8000/api/shipping/addresses
Headers:
  Authorization: Bearer YOUR_TOKEN
```

#### 3. Create Order
```
POST http://10.205.162.238:8000/api/orders/create
Headers:
  Authorization: Bearer YOUR_TOKEN
Body:
{
  "itemId": 1,
  "shippingAddressId": 1
}
```

#### 4. Get Orders
```
GET http://10.205.162.238:8000/api/orders
Headers:
  Authorization: Bearer YOUR_TOKEN
```

---

## 🐛 Troubleshooting

### Backend Issues

**Problem:** Database tables not found
```bash
# Solution: Run migration again
sqlite3 database/auction_portal.sqlite < database/migrations/add_payment_shipping_orders.sql
```

**Problem:** CORS errors
```bash
# Solution: Check CorsMiddleware in public/index.php
# Should allow your Flutter app's origin
```

**Problem:** 401 Unauthorized
```bash
# Solution: Check JWT token
# Login again to get fresh token
```

### Flutter Issues

**Problem:** Connection timeout
```bash
# Solution: Check IP address in api_config.dart
# Make sure backend server is running
# Make sure device is on same network
```

**Problem:** Image not loading
```bash
# Solution: Check image URLs in database
# Should be full URLs like: http://10.205.162.238:8000/uploads/image.jpg
```

**Problem:** Provider not found
```bash
# Solution: Make sure all providers are registered in main.dart
# Run: flutter clean && flutter pub get
```

---

## 📊 Database Queries for Testing

### Check Payment Methods
```sql
SELECT * FROM payment_methods WHERE userId = 1;
```

### Check Shipping Addresses
```sql
SELECT * FROM shipping_addresses WHERE userId = 1;
```

### Check Orders
```sql
SELECT * FROM orders WHERE buyerId = 1;
```

### Simulate Won Auction
```sql
-- End an auction where user 1 is highest bidder
UPDATE items SET status = 'ended' WHERE id = 1;
```

### Check Transactions
```sql
SELECT * FROM transactions WHERE buyerId = 1;
```

---

## ✅ Testing Checklist

### Backend
- [ ] Server starts without errors
- [ ] All new endpoints return 200 OK
- [ ] Database tables created successfully
- [ ] JWT authentication works
- [ ] Payment intent creation works
- [ ] Order creation works

### Flutter App
- [ ] App builds without errors
- [ ] All providers load correctly
- [ ] Navigation works smoothly
- [ ] Forms validate correctly
- [ ] API calls succeed
- [ ] Error messages display properly
- [ ] Loading states show correctly

### User Flows
- [ ] Registration works
- [ ] Login works
- [ ] Browse items works
- [ ] Place bid works
- [ ] Add to watchlist works
- [ ] View won items works
- [ ] Add shipping address works
- [ ] Add payment method works
- [ ] Checkout works
- [ ] View orders works
- [ ] Order details works
- [ ] Edit profile works
- [ ] Settings work

---

## 🎯 Expected Results

### After Registration
- User should be logged in automatically
- Should navigate to home screen (buyer) or dashboard (seller)

### After Placing Bid
- Bid should appear in "My Bids" screen
- Notification should be created
- Item's current price should update

### After Winning Auction
- Item should appear in "Won Items" screen
- Should be able to proceed to checkout

### After Checkout
- Order should be created with status "paid"
- Transaction record should be created
- Item status should change to "sold"
- Seller should receive notification

### After Adding Address
- Address should appear in address list
- Should be selectable in checkout

### After Adding Payment Method
- Card should appear in payment methods list
- Should be selectable in checkout

---

## 📝 Test Data

### Test User Credentials
```
Email: buyer@test.com
Password: password123
Role: buyer
```

### Test Card Numbers
```
Visa: 4242 4242 4242 4242
Mastercard: 5555 5555 5555 4444
Amex: 3782 822463 10005
```

### Test Address
```
Full Name: John Doe
Address: 123 Main Street
City: New York
State: NY
ZIP: 10001
Country: United States
Phone: +1 234 567 8900
```

---

## 🚀 Performance Testing

### Load Testing
```bash
# Test with multiple concurrent requests
ab -n 1000 -c 10 http://10.205.162.238:8000/api/items
```

### Response Time
- API endpoints should respond in < 200ms
- Image loading should be < 1s
- Screen navigation should be instant

---

## 📞 Support

If you encounter issues:

1. Check server logs in terminal
2. Check Flutter console for errors
3. Verify database schema
4. Test API endpoints with Postman
5. Check network connectivity

---

## 🎉 Success Indicators

You'll know everything is working when:

✅ Backend server runs without errors
✅ All API endpoints return valid responses
✅ Flutter app builds and runs
✅ User can register and login
✅ User can browse and bid on items
✅ User can complete checkout flow
✅ Orders are created successfully
✅ All screens navigate smoothly
✅ No console errors

---

**Happy Testing! 🚀**

---

**Last Updated:** February 22, 2026  
**Version:** 1.0.0
