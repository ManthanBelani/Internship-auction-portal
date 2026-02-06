# Auction Portal API Documentation & Guide

## 🚀 How to Run the Server

### 1. Prerequisites
- **PHP 8.1+**
- **MySQL 5.7+**
- **Composer** (Dependency Manager)

### 2. Installation
1.  **Install Dependencies**:
    ```bash
    composer install
    ```
    *Note: If you encounter autoload issues, the project includes a custom fallback autoloader.*

2.  **Database Setup**:
    - Create a database (e.g., `auction_portal`).
    - Run the migration SQL files located in `database/migrations/` in order.

3.  **Environment Configuration**:
    - Copy `.env.example` to `.env`.
    - Update database credentials (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`).

### 3. Running with XAMPP (Windows)

If you have XAMPP installed, you can use it to run the server instead of the built-in PHP command.

#### A. Database Setup
1.  Open **XAMPP Control Panel** and start **Apache** and **MySQL**.
2.  Go to [http://localhost/phpmyadmin](http://localhost/phpmyadmin).
3.  Click **New**, create a database named `auction_portal`.
4.  Select the database and go to the **Import** tab.
5.  Import the SQL files from the `database/migrations/` folder **in order** (001... to 011...).

#### B. Link Project to XAMPP
The easiest way is to create a "Symbolic Link" so XAMPP can serve your project without moving it.

1.  Open **Command Prompt** as **Administrator**.
2.  Run the following command (replace `[YOUR_PROJECT_PATH]` with your actual path):
    ```cmd
    mklink /J "C:\xampp\htdocs\auction-api" "c:\Users\manth\OneDrive\Desktop\Final Internship Project\public"
    ```
    *Note: This makes your API accessible at `http://localhost/auction-api`.*

#### C. Configure Environment
1.  Open your `.env` file.
2.  Update database settings for XAMPP defaults:
    ```ini
    DB_HOST=localhost
    DB_NAME=auction_portal
    DB_USER=root
    DB_PASSWORD=
    ```

### 4. Start the Server (Built-in PHP)
If you are NOT using XAMPP, run the built-in server:
```bash
php -S localhost:8000 -t public
```
The API is now accessible at `http://localhost:8000`.

### 5. Admin Dashboard Setup (XAMPP)

The Admin Dashboard is a separate web interface located in the `admin/` folder.

#### A. Link Admin Folder
Run this command in **Administrator Command Prompt**:
```cmd
mklink /J "C:\xampp\htdocs\auction-admin" "c:\Users\manth\OneDrive\Desktop\Final Internship Project\admin"
```
*Access URL: `http://localhost/auction-admin`*

#### B. Configure API Paths
Since XAMPP changes the URL structure, you need to update the API calls in the admin JavaScript files.

1.  Navigate to `admin/assets/js/`.
2.  Open `dashboard.js`, `users.js`, `items.js`.
3.  Change `const API_BASE = '/api';` to:
    ```javascript
    const API_BASE = '/auction-api/api';
    ```
4.  Open `login.js`.
5.  Change `fetch('/api/users/login'` to `fetch('/auction-api/api/users/login'`.

#### C. Default Credentials
- **Login URL**: `http://localhost/auction-admin/login.php`
- **Email**: `admin@auction.com`
- **Password**: `admin123`

*Note: If you run the API via `php -S localhost:8000`, the dashboard (if also run via `php -S localhost:8080`) would expect the API at `localhost:8000`. You might need to adjust paths based on your setup.*

---

## 🧪 How to Test with Postman

### 1. Setup Environment
- Create a **New Collection** in Postman called "Auction Portal".
- Set a collection variable `{{baseUrl}}` to `http://localhost:8000`.

### 2. Authentication Flow
1.  **Register/Login**: Make a `POST` request to `/api/users/login`.
2.  **Capture Token**: Copy the `token` string from the JSON response.
3.  **Authorize Requests**:
    - For protected routes, go to the **Authorization** tab.
    - Type: **Bearer Token**.
    - Token: Paste your token (or use a variable `{{token}}`).

---

## 📡 API Endpoints

### 👤 User (Buyer) Endpoints

| Method | Endpoint | Description | Auth Required | Body/Params |
| :--- | :--- | :--- | :---: | :--- |
| **POST** | `/api/users/register` | Register new account | ❌ | `email`, `password`, `name`, `role` (optional) |
| **POST** | `/api/users/login` | Login | ❌ | `email`, `password` |
| **GET** | `/api/users/profile` | Get my profile | ✅ | - |
| **GET** | `/api/items` | Browse active items | ❌ | Query: `search` |
| **GET** | `/api/items/{id}` | Get item details | ❌ | - |
| **POST** | `/api/bids` | Place a bid | ✅ | `itemId`, `amount` |
| **GET** | `/api/bids/{itemId}` | View bid history | ❌ | - |
| **POST** | `/api/watchlist` | Add item to watchlist | ✅ | `itemId` |
| **DELETE** | `/api/watchlist/{itemId}` | Remove from watchlist | ✅ | - |
| **GET** | `/api/watchlist` | Get my watchlist | ✅ | - |

### 💼 Seller Endpoints

| Method | Endpoint | Description | Auth Required | Body/Params |
| :--- | :--- | :--- | :---: | :--- |
| **POST** | `/api/items` | Create new auction | ✅ | `title`, `description`, `startingPrice`, `endTime` |
| **POST** | `/api/items/{id}/images` | Upload item images | ✅ | Form-Data: `image` (File) |
| **GET** | `/api/items` | View my listings | ❌ | Query: `sellerId={myId}` |
| **GET** | `/api/transactions` | View sales history | ✅ | - |
| **GET** | `/api/users/{id}/reviews` | View my reviews | ❌ | - |

### 🔧 System & Utility

| Method | Endpoint | Description |
| :--- | :--- | :--- |
| **GET** | `/health` | Check API status |
| **GET** | `/` | API Root info |

---

## 📥 Example Payloads

**Register User (Buyer)**
```json
POST /api/users/register
{
    "email": "john@example.com",
    "password": "password123",
    "name": "John Doe",
    "role": "buyer"
}
```

**Create Item (Seller)**
```json
POST /api/items
{
    "title": "Vintage Camera",
    "description": "A classic 1980s film camera in good condition.",
    "startingPrice": 50.00,
    "endTime": "2026-12-31 23:59:59"
}
```

**Place Bid**
```json
POST /api/bids
{
    "itemId": 1,
    "amount": 55.00
}
```
