# 🚀 Internship Auction Portal: Setup & Execution Guide

This guide will help you set up and run the Internship Auction Portal on a new device.

---

## 🛠️ Prerequisites

Before you begin, ensure you have the following installed:
1.  **WAMP Server** (or any PHP 8.1+ environment with MySQL).
2.  **Composer** (PHP dependency manager).
3.  **Flutter SDK** (for the mobile application).
4.  **MySQL Database** (bundled with WAMP).

---

## 📂 1. Backend Setup

### Step A: Clone and Configure
1.  Copy the project folder to your `www` directory (e.g., `C:\wamp64\www\Internship-auction-portal`).
2.  Open `.env` in the root directory and update your database credentials:
    ```env
    DB_HOST=localhost
    DB_NAME=auction_portal
    DB_USER=root
    DB_PASSWORD=your_password
    ```

### Step B: Install Dependencies
Open a terminal in the project root and run:
```powershell
composer install
```

### Step C: Database Setup
1.  Create a database named `auction_portal` in phpMyAdmin.
2.  Run the migrations to create the tables:
    ```powershell
    php run_migrations.php
    ```

---

## 🌐 2. Starting the Servers

You need to run **three separate terminals** for the backend to function correctly:

### Terminal 1: API Server
This serves the frontend and mobile app requests.
```powershell
php -S localhost:8000 -t public public/index.php
```

### Terminal 2: Admin Panel
This serves the administrative interface.
```powershell
php -S localhost:8080 -t admin
```

### Terminal 3: WebSocket Server (Real-time)
This enables real-time bidding updates and notifications.
```powershell
php bin/websocket-server.php
```

---

## 📱 3. Flutter App Setup (BidOrbit)

### Step A: Configure IP Address
Since you are testing on another device (or emulator), update the IP address to your **PC's Local IP** so the app can find the server.

1.  Find your local IP by running `ipconfig` in CMD (e.g., `192.168.1.5`).
2.  Update [app_config.dart](file:///d:/wamp64/www/Internship-auction-portal/BidOrbit/bidorbit/lib/config/app_config.dart):
    ```dart
    static String get baseUrl => 'http://YOUR_IP_HERE:8000';
    ```
3.  Update [api_config.dart](file:///d:/wamp64/www/Internship-auction-portal/BidOrbit/bidorbit/lib/config/api_config.dart):
    ```dart
    static String get wsUrl => 'ws://YOUR_IP_HERE:8081';
    ```

### Step B: Run the App
Open a terminal in `BidOrbit/bidorbit` and run:
```powershell
flutter pub get
flutter run
```

---

## ✅ Troubleshooting

- **Port Conflicts**: If port 8080 or 8000 is taken, change it in the start command and update your app config.
- **WebSocket Connection**: If the app fails to connect to WebSocket, ensure port 8081 is open in your Windows Firewall.
- **WAMP**: Ensure WAMP is running and "Online" (Green icon).
