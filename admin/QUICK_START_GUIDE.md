# Admin Panel - Quick Start Guide 🚀

## 1. Access the Admin Panel

**URL:** `http://localhost/admin/login.php`

**Default Credentials:**
- Email: `admin@auction.com`
- Password: `admin123`

⚠️ **IMPORTANT:** Change the default password immediately after first login!

## 2. Dashboard Overview

After logging in, you'll see the main dashboard with:

### Statistics Cards
- **Total Users** - Number of registered users
- **Total Items** - Number of auction items
- **Total Transactions** - Completed transactions
- **Platform Earnings** - Total commission earned

### Charts
- **Users by Role** - Distribution of users (Admin, Moderator, Seller, Buyer)
- **Items by Status** - Distribution of items (Active, Sold, Expired)

### Recent Activity
- Real-time feed of platform activities
- Auto-refreshes every 30 seconds

## 3. Navigation Menu

### For All Admins & Moderators:
- 🏠 **Dashboard** - Overview and statistics
- 👥 **Users** - Manage users and roles
- 🔨 **Auction Items** - Manage auction listings
- 🛒 **Transactions** - View all transactions
- ⭐ **Reviews** - Moderate user reviews

### For Admins Only:
- 💰 **Earnings** - View platform earnings
- ⚙️ **Settings** - Configure platform settings

## 4. User Management

### View Users
1. Click **Users** in the sidebar
2. See all users in a table format

### Filter Users
- **By Role:** Admin, Moderator, Seller, Buyer
- **By Status:** Active, Suspended, Banned
- **Search:** By name or email

### User Actions (Admin Only)
- **Change Role:** Click "Role" button → Enter new role
- **Suspend User:** Click "Suspend" → Enter suspension date (optional)
- **Ban User:** Click "Ban" → Confirm action
- **Reactivate:** Click "Reactivate" for suspended/banned users

### User Actions (Moderator)
- **Suspend User:** Temporary suspension
- **Reactivate:** Restore suspended users

## 5. Item Management

### View Items
1. Click **Auction Items** in the sidebar
2. See all auction items

### Filter Items
- **By Status:** Active, Sold, Expired
- **Search:** By item title

### Item Actions
- **View:** See item details
- **Delete:** Remove item (with confirmation)

## 6. Transaction Management

### View Transactions
1. Click **Transactions** in the sidebar
2. See all platform transactions

### Filter Transactions
- **By Status:** Completed, Pending, Failed
- **Date Range:** From/To dates
- **Search:** By item or user name

### Export Transactions
- Click "Export" button to download transaction report

## 7. Review Management

### View Reviews
1. Click **Reviews** in the sidebar
2. See all user reviews

### Filter Reviews
- **By Rating:** 1-5 stars
- **By Type:** Seller or Buyer reviews
- **Search:** Search review content

### Review Actions
- **View:** See full review details
- **Delete:** Remove inappropriate reviews

## 8. Earnings Dashboard (Admin Only)

### View Earnings
1. Click **Earnings** in the sidebar
2. See earnings summary and chart

### Earnings Summary
- Total Earnings
- Today's Earnings
- This Week
- This Month

### Filter Earnings
- Last 7 Days
- Last 30 Days
- Last 90 Days
- Last Year

### Export Report
- Click "Export Report" to download earnings data

## 9. Platform Settings (Admin Only)

### General Settings
- Platform Name
- Support Email
- Platform Status (Active/Maintenance)

### Commission Settings
- Default Commission Rate (%)
- Minimum Commission ($)

### Auction Settings
- Minimum/Maximum Duration
- Minimum Bid Increment
- Auto-extend Time

### Email Settings
- SMTP Configuration
- Email Notifications Toggle

### Security Settings
- Session Timeout
- Max Login Attempts
- Two-Factor Authentication
- Email Verification

### Maintenance Tools
- Clear Cache
- Optimize Database
- Export Backup
- View System Logs

## 10. Common Tasks

### Change User Role
1. Go to **Users**
2. Find the user
3. Click **Role** button
4. Enter new role: `buyer`, `seller`, `moderator`, or `admin`
5. Confirm

### Suspend a User
1. Go to **Users**
2. Find the user
3. Click **Suspend** button
4. Enter suspension end date (optional for indefinite)
5. Confirm

### Delete an Item
1. Go to **Auction Items**
2. Find the item
3. Click **Delete** button
4. Confirm deletion

### Delete a Review
1. Go to **Reviews**
2. Find the review
3. Click **Delete** button
4. Confirm deletion

### Update Platform Settings
1. Go to **Settings**
2. Find the section you want to update
3. Modify the values
4. Click **Save Changes**

## 11. Keyboard Shortcuts

- **Enter** in search fields - Apply filter
- **Esc** - Close modals
- **Ctrl/Cmd + R** - Refresh page

## 12. Mobile Usage

### Access on Mobile
- Fully responsive design
- Touch-friendly buttons
- Collapsible sidebar

### Mobile Navigation
1. Tap the **menu icon** (☰) to open sidebar
2. Tap outside sidebar to close
3. All features work on mobile

## 13. Tips & Best Practices

### Security
- ✅ Change default password immediately
- ✅ Use strong passwords
- ✅ Log out when finished
- ✅ Don't share admin credentials
- ✅ Review user actions regularly

### User Management
- ✅ Verify user identity before role changes
- ✅ Document suspension reasons
- ✅ Use temporary suspensions first
- ✅ Ban only for serious violations

### Content Moderation
- ✅ Review flagged items regularly
- ✅ Check reviews for inappropriate content
- ✅ Respond to user reports promptly
- ✅ Document moderation actions

### Platform Maintenance
- ✅ Monitor earnings regularly
- ✅ Check transaction status
- ✅ Review platform statistics
- ✅ Update settings as needed
- ✅ Clear cache periodically

## 14. Troubleshooting

### Can't Login
- Check credentials
- Verify you have admin/moderator role
- Clear browser cache
- Check backend API is running

### Statistics Not Loading
- Check backend API connection
- Verify API token is valid
- Check browser console for errors
- Refresh the page

### Actions Not Working
- Check your role permissions
- Verify API is responding
- Check network connection
- Try logging out and back in

### Charts Not Displaying
- Ensure Chart.js is loaded
- Check browser console
- Verify data is available
- Refresh the page

## 15. API Status Codes

- **200** - Success
- **400** - Bad Request
- **401** - Unauthorized
- **403** - Forbidden
- **404** - Not Found
- **500** - Server Error

## 16. Support

### Need Help?
1. Check this guide
2. Review README.md
3. Check FEATURES.md
4. Review API_DOCUMENTATION.md
5. Check browser console for errors

### Report Issues
- Document the issue
- Include error messages
- Note steps to reproduce
- Check API logs

## 17. Logout

### How to Logout
1. Click your name in the top right
2. Click **Logout** button
3. You'll be redirected to login page

### Auto Logout
- Sessions expire after inactivity
- You'll be redirected to login
- Login again to continue

## Quick Reference Card

```
┌─────────────────────────────────────────┐
│         ADMIN PANEL QUICK REF           │
├─────────────────────────────────────────┤
│ Login: /admin/login.php                 │
│ Default: admin@auction.com / admin123   │
├─────────────────────────────────────────┤
│ PAGES:                                  │
│ 🏠 Dashboard    - Statistics & Charts   │
│ 👥 Users        - User Management       │
│ 🔨 Items        - Auction Items         │
│ 🛒 Transactions - All Transactions      │
│ ⭐ Reviews      - User Reviews          │
│ 💰 Earnings     - Platform Earnings     │
│ ⚙️  Settings    - Platform Config       │
├─────────────────────────────────────────┤
│ USER ACTIONS:                           │
│ • Change Role   • Suspend               │
│ • Ban           • Reactivate            │
├─────────────────────────────────────────┤
│ FILTERS:                                │
│ • Role          • Status                │
│ • Date Range    • Search                │
├─────────────────────────────────────────┤
│ ROLES:                                  │
│ Admin      - Full Access                │
│ Moderator  - Limited Access             │
│ Seller     - Cannot Access              │
│ Buyer      - Cannot Access              │
└─────────────────────────────────────────┘
```

## That's It! 🎉

You're now ready to use the admin panel. Start by exploring the dashboard and familiarizing yourself with the interface.

**Remember:** Always log out when you're done!

---

**Need more help?** Check the full documentation in ADMIN_COMPLETE.md
