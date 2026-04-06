# Postman API Testing Guide - Auction Portal

Yes, you can test all these endpoints in Postman. Below is the step-by-step guide on how to do it and a complete list of all endpoints.

## 🚀 How to Test in Postman

### 1. Set Up Your Environment
1.  **Open Postman** and create a **New Collection** named `Auction Portal`.
2.  **Add a Collection Variable**:
    - Click on the collection name -> **Variables** tab.
    - Add a variable named `baseUrl`.
    - Set the **Initial Value** and **Current Value** to `http://localhost:8000` (or your server URL).
3.  **Add a Token Variable**:
    - Add another variable named `token`. Leave it empty for now; we will fill it after logging in.

### 2. Authentication (Getting your Token)
Most routes require you to be logged in.
1.  Create a `POST` request to `{{baseUrl}}/api/users/login`.
2.  In the **Body** tab, select `raw` and `JSON`, and enter your credentials:
    ```json
    {
        "email": "your_email@example.com",
        "password": "your_password"
    }
    ```
3.  Click **Send**.
4.  Copy the `token` from the response.
5.  Go back to your Collection settings -> **Variables** tab and paste the token into the `token` variable's Current Value.

### 3. Automatically Authorize All Requests
1.  In your Collection settings, go to the **Authorization** tab.
2.  Select **Type**: `Bearer Token`.
3.  In the **Token** field, type `{{token}}`.
4.  Click **Save**. Now, every request in this collection will automatically use your token!

---

## 📡 List of All API Endpoints

### 👤 Public & User Management
| Method | Endpoint | Description | Auth Required |
| :--- | :--- | :--- | :---: |
| `GET` | `/health` | Check if API is running | ❌ |
| `GET` | `/` | API Root Info | ❌ |
| `POST` | `/api/users/register` | Register a new user | ❌ |
| `POST` | `/api/users/login` | Login and get token | ❌ |
| `GET` | `/api/users/profile` | Get your profile details | ✅ |
| `PUT` | `/api/users/profile` | Update your profile | ✅ |
| `GET` | `/api/users/{id}/public` | View someone else's public profile | ❌ |

### 🏷️ Items & Auctions
| Method | Endpoint | Description | Auth Required |
| :--- | :--- | :--- | :---: |
| `GET` | `/api/items` | List all active auction items | ❌ |
| `GET` | `/api/items/{id}` | Get details of a specific item | ❌ |
| `POST` | `/api/items` | Create a new auction (Seller) | ✅ |
| `GET` | `/api/auction-status/{id}` | Get real-time status/current bid | ❌ |
| `GET` | `/api/price-history/{id}` | Get price history for an item | ❌ |

### 🖼️ Item Images
| Method | Endpoint | Description | Auth Required |
| :--- | :--- | :--- | :---: |
| `GET` | `/api/items/{id}/images` | Get all images for an item | ❌ |
| `POST` | `/api/items/{id}/images` | Upload a single image | ✅ |
| `POST` | `/api/seller/items/{id}/images/bulk` | Bulk upload multiple images | ✅ |
| `DELETE` | `/api/images/{id}` | Delete an image | ✅ |

### 🔨 Bidding & Reviews
| Method | Endpoint | Description | Auth Required |
| :--- | :--- | :--- | :---: |
| `POST` | `/api/bids` | Place a bid on an item | ✅ |
| `GET` | `/api/bids/{id}` | Get bid history for an item | ❌ |
| `POST` | `/api/reviews` | Write a review for a seller | ✅ |
| `GET` | `/api/users/{id}/reviews` | View reviews for a user | ❌ |
| `GET` | `/api/users/{id}/rating` | Get average rating for a user | ❌ |

### ⭐ Watchlist
| Method | Endpoint | Description | Auth Required |
| :--- | :--- | :--- | :---: |
| `GET` | `/api/watchlist` | Get your saved items | ✅ |
| `POST` | `/api/watchlist` | Add an item to watchlist | ✅ |
| `DELETE` | `/api/watchlist/{id}` | Remove an item from watchlist | ✅ |
| `GET` | `/api/watchlist/check/{id}` | Check if you are watching an item | ✅ |

### 🛒 My Account (Buyer Side)
| Method | Endpoint | Description | Path Prefix: `/api/my` |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/my/bids` | List all auctions I bid on | ✅ |
| `GET` | `/api/my/notifications` | Get my notifications | ✅ |
| `PUT` | `/api/my/notifications/{id}/read` | Mark notification as read | ✅ |
| `POST` | `/api/my/payments/{id}/pay` | Pay for a won auction | ✅ |

### 👨‍💼 Seller Dashboard
| Method | Endpoint | Description | Path Prefix: `/api/seller` |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/seller/stats` | Sales and performance stats | ✅ |
| `GET` | `/api/seller/listings` | Manage my auction listings | ✅ |
| `PUT` | `/api/seller/items/{id}` | Edit an existing listing | ✅ |
| `GET` | `/api/seller/messages` | View all conversations | ✅ |
| `POST` | `/api/seller/messages` | Send a message to a buyer | ✅ |
| `POST` | `/api/seller/shipping/track` | Update shipping/tracking info | ✅ |
| `POST` | `/api/seller/payouts` | Request a payout of earnings | ✅ |

### 🛡️ Admin Panel
| Method | Endpoint | Description | Path Prefix: `/api/admin` |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/admin/stats` | Platform-wide statistics | ✅ |
| `GET` | `/api/admin/users` | List all users on platform | ✅ |
| `PUT` | `/api/admin/users/{id}/role` | Change user role (e.g. to admin) | ✅ |
| `POST` | `/api/admin/users/{id}/suspend`| Suspend a user account | ✅ |
| `DELETE` | `/api/admin/items/{id}` | Force delete an auction | ✅ |
| `GET` | `/api/admin/payouts` | Review pending payout requests | ✅ |
| `PUT` | `/api/admin/payouts/{id}` | Approve/Reject payout | ✅ |

---
*Generated for the Auction Portal Project.*
