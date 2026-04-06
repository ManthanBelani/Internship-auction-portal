# 🚀 Complete Testing Guide - BidOrbit Auction App

## ✅ Current Status

### Backend Server
- **Status**: ✅ RUNNING on http://localhost:8000
- **Database**: SQLite (auction_portal.sqlite)
- **Health Check**: ✅ Passed

### Test Accounts Created
1. **Seller Account**
   - Email: `seller@test.com`
   - Password: `password123`
   - Role: Seller

2. **Buyer Account**
   - Email: `buyer@test.com`
   - Password: `password123`
   - Role: Buyer

### Flutter App
- **Status**: Ready to run
- **API Configuration**: ✅ Updated to use localhost
- **Code Errors**: ✅ Fixed
- **Dependencies**: ✅ Installed

---

## 📱 Step 1: Run the Flutter App

Open a **NEW terminal** (keep the backend server running) and run:

```bash
cd BidOrbit/bidorbit
flutter run -d windows
```

**Alternative options:**
- For Android Emulator: `flutter run -d emulator-5554`
- For Chrome: `flutter run -d chrome`
- List available devices: `flutter devices`

---

## 🧪 Step 2: Test Seller Flow (Create Product)

### 2.1 Login as Seller
1. When the app opens, you'll see the **Login Screen**
2. Click **"Sign Up"** at the bottom
3. Fill in the registration form:
   - **Name**: Test Seller 2
   - **Email**: seller2@test.com
   - **Phone**: 1234567890
   - **Password**: password123
   - **Confirm Password**: password123
   - **Role**: Select **"Seller"** from dropdown
   - Check the terms checkbox
4. Click **"Sign Up"**

**Expected Result**: You should be redirected to the Seller Dashboard

### 2.2 Create a New Auction Item
1. In the Seller Dashboard, look for **"Add Item"** or **"+"** button
2. Click to open the **Add Item Screen**
3. Fill in the item details:

   **Photos**:
   - Click "ADD" to select images
   - Add at least 1 image (up to 10)

   **Basic Information**:
   - **Title**: Vintage Camera 1980s
   - **Category**: Electronics
   - **Condition**: Good
   - **Description**: A classic 1980s film camera in excellent working condition. Comes with original lens and case.

   **Pricing**:
   - **Starting Bid**: 50
   - **Reserve Price**: (optional) 100
   - **Buy It Now**: (leave empty)

   **Schedule**:
   - **Start Date**: Today's date
   - **Start Time**: Current time
   - **End Date**: 7 days from now
   - **End Time**: 23:59

4. Click **"List Item for Auction"**

**Expected Result**: 
- Success message: "Item listed successfully!"
- You'll be taken back to the dashboard
- The item should appear in your inventory

### 2.3 Verify Item Creation
1. Go to **Inventory** tab in seller dashboard
2. You should see your newly created item
3. Note the item details (price, title, etc.)

---

## 🛒 Step 3: Test Buyer Flow (Place Bid)

### 3.1 Logout and Login as Buyer
1. Click the **Profile** icon/menu
2. Click **"Logout"**
3. You'll be back at the Login Screen
4. Click **"Sign Up"** to create a new buyer account:
   - **Name**: Test Buyer 2
   - **Email**: buyer2@test.com
   - **Phone**: 0987654321
   - **Password**: password123
   - **Confirm Password**: password123
   - **Role**: Select **"Buyer"** from dropdown
   - Check the terms checkbox
5. Click **"Sign Up"**

**Expected Result**: You should see the **Home Screen** with auction items

### 3.2 Browse and View Item
1. On the Home Screen, you should see the item you created as a seller
2. Verify the item displays:
   - ✅ Image
   - ✅ Title: "Vintage Camera 1980s"
   - ✅ Current Price: $50.00
   - ✅ Bid count: 0 bids
   - ✅ Time remaining countdown
3. **Tap on the item** to view details

**Expected Result**: Item Details Screen opens showing:
- Full item information
- Countdown timer
- Current bid: $50.00
- Description
- Specifications
- "Place Bid" button

### 3.3 Place a Bid
1. On the Item Details Screen, click **"Place Bid"** button
2. A bottom sheet will appear showing:
   - Current Bid: $50.00
   - Minimum Next Bid: $60.00
   - Bid amount input (default: $60)
3. You can:
   - Use quick bid buttons (+$10, +$50, +$100)
   - Or manually enter amount
4. Enter bid amount: **$75**
5. Check the **terms checkbox**
6. Click **"Confirm Bid"**

**Expected Result**:
- Success message: "Bid placed successfully for $75!"
- Bottom sheet closes
- Item details refresh showing:
  - Current Bid: $75.00
  - Bid count: 1 bid
  - Your bid appears in bid history

### 3.4 Verify Bid
1. Go back to **Home Screen**
2. The item should now show:
   - Current Price: $75.00
   - Bid count: 1 bid
3. Go to **"Bids"** tab in bottom navigation
4. You should see your bid listed

---

## 🔍 Step 4: Verify End-to-End Flow

### 4.1 As Buyer - Place Another Bid
1. Go back to the item details
2. Place another bid with a higher amount (e.g., $90)
3. Verify the price updates

### 4.2 As Seller - View Bids
1. Logout from buyer account
2. Login as seller (seller@test.com / password123)
3. Go to **Active Auctions** or **Dashboard**
4. You should see:
   - Your item with updated current price
   - Number of bids received
   - Current highest bid

### 4.3 Test Search
1. Login as buyer
2. On Home Screen, use the **search bar**
3. Type "camera"
4. The item should appear in search results

### 4.4 Test Favorites (if implemented)
1. On any item card, click the **heart icon**
2. Go to **"Favourite"** tab
3. The item should appear in your favorites

---

## ✅ Expected Behaviors

### Authentication
- ✅ Registration creates account and logs in automatically
- ✅ Login redirects based on role (buyer → home, seller → dashboard)
- ✅ Token is saved and persists across app restarts
- ✅ Logout clears session

### Items Display
- ✅ Items load from backend
- ✅ Images display correctly
- ✅ Prices format as currency
- ✅ Countdown timers update
- ✅ Pull-to-refresh works
- ✅ Infinite scroll loads more items

### Bidding
- ✅ Bid amount must be higher than current price
- ✅ Bid updates item price immediately
- ✅ Bid count increments
- ✅ Bid history shows all bids
- ✅ Cannot bid on own items (seller)

### Seller Functions
- ✅ Can create new auction items
- ✅ Can upload multiple images
- ✅ Can set starting price and end time
- ✅ Can view own inventory
- ✅ Can see bids on own items

---

## 🐛 Troubleshooting

### Problem: "No internet connection" error
**Solution**:
- Verify backend server is running (check terminal)
- Check API URL in `lib/config/api_config.dart`
- For Android emulator, the app uses `10.0.2.2:8000`
- For Windows/Desktop, it uses `localhost:8000`

### Problem: Images don't display
**Solution**:
- Check that image URLs are complete (include http://)
- Verify backend returns full image URLs
- Check network permissions in AndroidManifest.xml

### Problem: Login fails
**Solution**:
- Verify credentials are correct
- Check backend logs for errors
- Ensure database has the user record

### Problem: Bid placement fails
**Solution**:
- Ensure you're logged in as a buyer
- Verify bid amount is higher than current price
- Check that auction hasn't ended
- Review backend logs for errors

### Problem: App crashes on startup
**Solution**:
- Run `flutter clean`
- Run `flutter pub get`
- Rebuild the app

---

## 📊 Backend API Endpoints Being Used

| Endpoint | Method | Purpose | Screen |
|----------|--------|---------|--------|
| `/api/users/register` | POST | Create account | RegisterScreen |
| `/api/users/login` | POST | Login | LoginScreen |
| `/api/users/profile` | GET | Get user info | AuthProvider |
| `/api/items` | GET | List items | HomeScreen |
| `/api/items/{id}` | GET | Item details | ItemDetailsScreen |
| `/api/items` | POST | Create item | AddItemScreen |
| `/api/bids` | POST | Place bid | ItemDetailsScreen |
| `/api/bids/{itemId}` | GET | Bid history | ItemDetailsScreen |

---

## 📝 Test Checklist

Use this checklist to verify all functionality:

### Authentication
- [ ] Register as seller
- [ ] Register as buyer
- [ ] Login as seller
- [ ] Login as buyer
- [ ] Logout
- [ ] Token persists after app restart

### Seller Functions
- [ ] Create auction item
- [ ] Upload images
- [ ] Set pricing
- [ ] Set schedule
- [ ] View inventory
- [ ] View active auctions
- [ ] See bid notifications

### Buyer Functions
- [ ] Browse items
- [ ] Search items
- [ ] View item details
- [ ] Place bid
- [ ] View bid history
- [ ] Add to favorites
- [ ] View my bids

### UI/UX
- [ ] Loading states show
- [ ] Error messages display
- [ ] Success messages show
- [ ] Images load correctly
- [ ] Countdown timers work
- [ ] Pull-to-refresh works
- [ ] Infinite scroll works
- [ ] Navigation works smoothly

---

## 🎯 Success Criteria

The integration is successful if:
1. ✅ You can register and login as both seller and buyer
2. ✅ Seller can create an auction item with images
3. ✅ Buyer can see the item on home screen
4. ✅ Buyer can view item details
5. ✅ Buyer can place a bid
6. ✅ Bid updates are reflected immediately
7. ✅ Seller can see the bid in their dashboard

---

## 🔄 Next Steps After Testing

Once basic flow works:
1. Test edge cases (invalid inputs, network errors)
2. Test concurrent bidding (multiple buyers)
3. Test auction ending scenarios
4. Implement watchlist functionality
5. Add real-time updates with WebSocket
6. Improve error handling
7. Add loading skeletons
8. Implement offline support
9. Add push notifications
10. Performance optimization

---

## 📞 Need Help?

If you encounter issues:
1. Check the backend server logs (terminal where PHP server is running)
2. Check Flutter app logs (terminal where flutter run is executed)
3. Use Flutter DevTools for debugging
4. Check network requests in browser DevTools (if running on web)

---

## 🎉 You're All Set!

The backend is running, test accounts are created, and the app is ready to run.

**Just run**: `flutter run -d windows` in the BidOrbit/bidorbit directory and start testing!

Good luck! 🚀
