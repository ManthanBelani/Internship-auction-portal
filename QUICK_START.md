# ⚡ Quick Start - Run BidOrbit Now!

## Current Status
✅ Backend server is RUNNING on http://localhost:8000
✅ Test accounts created (seller@test.com & buyer@test.com)
✅ Flutter app is ready to run

---

## 🚀 Run the Flutter App (Choose One)

### Option 1: Windows Desktop
```bash
cd BidOrbit/bidorbit
flutter run -d windows
```

### Option 2: Android Emulator
```bash
cd BidOrbit/bidorbit
flutter run
```

### Option 3: Chrome Browser
```bash
cd BidOrbit/bidorbit
flutter run -d chrome
```

---

## 🧪 Quick Test Flow

### 1. Create Product (as Seller)
- Sign up with email: `seller2@test.com`, password: `password123`, role: **Seller**
- Add a new item with title "Vintage Camera", price $50
- Upload an image
- Set end date 7 days from now

### 2. Place Bid (as Buyer)
- Logout
- Sign up with email: `buyer2@test.com`, password: `password123`, role: **Buyer**
- Find the "Vintage Camera" item on home screen
- Tap to view details
- Click "Place Bid"
- Enter $75
- Confirm bid

### 3. Verify
- Check that price updated to $75
- Check bid count shows "1 bid"
- Go to "Bids" tab to see your bid

---

## 🔧 If Backend Stopped

If you closed the terminal or backend stopped, restart it:

```bash
php -S localhost:8000 -t public
```

---

## 📱 Test Accounts

**Seller Account:**
- Email: seller@test.com
- Password: password123

**Buyer Account:**
- Email: buyer@test.com
- Password: password123

---

## ✅ What Should Work

- ✅ Register & Login (both roles)
- ✅ Create auction items (seller)
- ✅ Browse items (buyer)
- ✅ View item details
- ✅ Place bids
- ✅ Real-time price updates
- ✅ Image upload
- ✅ Search functionality
- ✅ Countdown timers

---

## 🐛 Quick Fixes

**App won't connect to backend?**
- Check backend is running: http://localhost:8000/health
- Restart backend: `php -S localhost:8000 -t public`

**Images not showing?**
- Make sure you selected images when creating item
- Check backend has write permissions

**Can't place bid?**
- Make sure you're logged in as buyer (not seller)
- Bid amount must be higher than current price

---

## 📖 Full Documentation

For detailed testing guide, see: `RUN_AND_TEST_GUIDE.md`

---

**Ready? Run the app and start testing! 🎉**
