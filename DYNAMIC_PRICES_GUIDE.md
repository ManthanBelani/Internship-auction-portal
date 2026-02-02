# Dynamic Price Updates - Complete Guide

## ✅ YES! The backend has full support for dynamic bid prices!

---

## 🎯 Available Features

### 1. **Real-Time Price Updates**
Every time a bid is placed, the item's `currentPrice` is automatically updated in the database and reflected in all API responses.

### 2. **Bid History Tracking**
Complete history of all bids with timestamps, allowing you to see price progression over time.

### 3. **Multiple Monitoring Endpoints**
Several endpoints to get price information in different formats.

---

## 📡 API Endpoints for Dynamic Prices

### **Basic Endpoints (Already Existing)**

#### 1. Get All Active Items with Current Prices
```http
GET /api/items
```

**Response:**
```json
{
  "items": [
    {
      "itemId": 1,
      "title": "Auction Item",
      "currentPrice": 250.00,
      "startingPrice": 100.00,
      "bidCount": 5,
      "status": "active",
      "endTime": "2026-02-02 15:30:00"
    }
  ]
}
```

#### 2. Get Single Item with Current Price
```http
GET /api/items/:itemId
```

**Response:**
```json
{
  "itemId": 1,
  "title": "Auction Item",
  "currentPrice": 250.00,
  "startingPrice": 100.00,
  "highestBidderId": 5,
  "bidCount": 5,
  "status": "active"
}
```

#### 3. Get Complete Bid History
```http
GET /api/bids/:itemId
```

**Response:**
```json
{
  "bids": [
    {
      "bidId": 5,
      "bidderId": 3,
      "bidderName": "John Doe",
      "amount": 250.00,
      "timestamp": "2026-02-01 15:25:00"
    },
    {
      "bidId": 4,
      "bidderId": 2,
      "bidderName": "Jane Smith",
      "amount": 200.00,
      "timestamp": "2026-02-01 15:20:00"
    }
  ]
}
```

---

### **Enhanced Endpoints (Newly Added)**

#### 4. Get Real-Time Auction Status
```http
GET /api/auction-status/:itemId
```

**Perfect for:** Polling every few seconds for real-time updates

**Response:**
```json
{
  "itemId": 1,
  "title": "Auction Item",
  "status": "active",
  "currentPrice": 250.00,
  "startingPrice": 100.00,
  "highestBidderId": 5,
  "bidCount": 5,
  "endTime": "2026-02-02 15:30:00",
  "timeRemaining": {
    "expired": false,
    "seconds": 3600,
    "days": 0,
    "hours": 1,
    "minutes": 0,
    "formatted": "1h"
  },
  "isActive": true,
  "latestBids": [
    {
      "bidId": 5,
      "amount": 250.00,
      "bidderName": "John Doe",
      "timestamp": "2026-02-01 15:25:00"
    }
  ],
  "priceIncrease": 150.00,
  "priceIncreasePercentage": 150.00,
  "timestamp": "2026-02-01 15:30:00"
}
```

#### 5. Get Multiple Auction Statuses at Once
```http
GET /api/auction-status/multiple?itemIds=1,2,3
```

**Perfect for:** Dashboard showing multiple auctions

**Response:**
```json
{
  "items": [
    {
      "itemId": 1,
      "currentPrice": 250.00,
      "bidCount": 5,
      "status": "active",
      "timeRemaining": {
        "formatted": "1h 30m"
      },
      "isActive": true
    },
    {
      "itemId": 2,
      "currentPrice": 180.00,
      "bidCount": 3,
      "status": "active",
      "timeRemaining": {
        "formatted": "2h 15m"
      },
      "isActive": true
    }
  ],
  "timestamp": "2026-02-01 15:30:00"
}
```

#### 6. Get Complete Price History
```http
GET /api/price-history/:itemId
```

**Perfect for:** Showing price progression chart

**Response:**
```json
{
  "itemId": 1,
  "title": "Auction Item",
  "currentPrice": 250.00,
  "priceHistory": [
    {
      "timestamp": "2026-02-01 14:00:00",
      "price": 100.00,
      "type": "starting_price",
      "bidderName": null
    },
    {
      "timestamp": "2026-02-01 14:15:00",
      "price": 150.00,
      "type": "bid",
      "bidderName": "Jane Smith"
    },
    {
      "timestamp": "2026-02-01 14:30:00",
      "price": 200.00,
      "type": "bid",
      "bidderName": "John Doe"
    },
    {
      "timestamp": "2026-02-01 15:25:00",
      "price": 250.00,
      "type": "bid",
      "bidderName": "Jane Smith"
    }
  ],
  "totalBids": 3
}
```

---

## 🔄 How Dynamic Prices Work

### 1. **Automatic Updates**
When a bid is placed via `POST /api/bids`:
```javascript
// Bid is placed
POST /api/bids
{
  "itemId": 1,
  "amount": 250.00
}

// Backend automatically:
// 1. Creates bid record
// 2. Updates item.currentPrice = 250.00
// 3. Updates item.highestBidderId = bidder's ID
// 4. Returns updated bid information
```

### 2. **Real-Time Retrieval**
Any subsequent GET request immediately shows the updated price:
```javascript
GET /api/items/1
// Returns currentPrice: 250.00 (updated!)
```

---

## 📱 Implementation Examples

### **Flutter/Mobile App - Polling Approach**

```dart
// Poll for updates every 3 seconds
Timer.periodic(Duration(seconds: 3), (timer) async {
  final response = await http.get(
    Uri.parse('http://your-api.com/api/auction-status/$itemId')
  );
  
  if (response.statusCode == 200) {
    final data = jsonDecode(response.body);
    setState(() {
      currentPrice = data['currentPrice'];
      bidCount = data['bidCount'];
      timeRemaining = data['timeRemaining']['formatted'];
    });
  }
});
```

### **JavaScript/Web App - Polling Approach**

```javascript
// Update prices every 5 seconds
setInterval(async () => {
  const response = await fetch(`/api/auction-status/${itemId}`);
  const data = await response.json();
  
  document.getElementById('current-price').textContent = `$${data.currentPrice}`;
  document.getElementById('bid-count').textContent = data.bidCount;
  document.getElementById('time-remaining').textContent = data.timeRemaining.formatted;
}, 5000);
```

### **Dashboard - Multiple Items**

```javascript
// Update multiple auctions at once
const itemIds = [1, 2, 3, 4, 5];
const response = await fetch(`/api/auction-status/multiple?itemIds=${itemIds.join(',')}`);
const data = await response.json();

data.items.forEach(item => {
  updateAuctionCard(item.itemId, item.currentPrice, item.bidCount);
});
```

---

## 📊 Use Cases

### 1. **Live Auction Page**
- Poll `/api/auction-status/:itemId` every 3-5 seconds
- Show current price, bid count, time remaining
- Display latest 5 bids

### 2. **Auction Dashboard**
- Poll `/api/auction-status/multiple` every 10 seconds
- Show multiple auctions with current prices
- Highlight recently updated items

### 3. **Price History Chart**
- Fetch `/api/price-history/:itemId` once
- Display line chart showing price progression
- Update when new bid notification received

### 4. **Bid Notifications**
- After placing bid, immediately fetch `/api/auction-status/:itemId`
- Show confirmation with updated price
- Display if you're still the highest bidder

---

## 🚀 Performance Tips

### 1. **Polling Intervals**
- Active bidding: 3-5 seconds
- Normal viewing: 10-15 seconds
- Dashboard: 15-30 seconds

### 2. **Efficient Queries**
- Use `/api/auction-status/multiple` for dashboards
- Cache results for short periods (2-3 seconds)
- Only poll active auctions

### 3. **Conditional Updates**
- Compare `timestamp` field to detect changes
- Only update UI if data actually changed
- Use `bidCount` to detect new bids

---

## 🎨 UI/UX Recommendations

### **Show Dynamic Updates:**
- ✅ Current price (large, prominent)
- ✅ Starting price (for comparison)
- ✅ Price increase amount and percentage
- ✅ Number of bids
- ✅ Time remaining (countdown)
- ✅ Latest bidder name
- ✅ "You're winning!" indicator

### **Visual Feedback:**
- 🔴 Flash/highlight when price changes
- 📈 Show price trend (up arrow)
- ⏰ Countdown timer
- 🏆 Badge for highest bidder
- 📊 Mini price history chart

---

## 🔧 Advanced Features (Future Enhancements)

### **WebSocket Support (Optional)**
For true real-time updates without polling:
```javascript
// Instead of polling, receive push notifications
const ws = new WebSocket('ws://your-api.com/auction-updates');
ws.onmessage = (event) => {
  const update = JSON.parse(event.data);
  if (update.itemId === currentItemId) {
    updatePrice(update.currentPrice);
  }
};
```

### **Server-Sent Events (SSE)**
```javascript
const eventSource = new EventSource(`/api/auction-stream/${itemId}`);
eventSource.onmessage = (event) => {
  const data = JSON.parse(event.data);
  updateAuctionDisplay(data);
};
```

---

## ✅ Summary

**YES, the backend fully supports dynamic bid prices with:**

✅ **Automatic price updates** when bids are placed  
✅ **Real-time price retrieval** via multiple endpoints  
✅ **Complete bid history** with timestamps  
✅ **Price progression tracking**  
✅ **Time remaining calculations**  
✅ **Multiple auction monitoring**  
✅ **Price increase statistics**  
✅ **Latest bids display**  

**All you need to do is:**
1. Poll the appropriate endpoint every few seconds
2. Update your UI with the returned data
3. Show users the current price and bid count

**The backend handles all the heavy lifting!** 🚀
