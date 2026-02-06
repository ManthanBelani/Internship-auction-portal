# Servers Running

## Backend API Server
- **Status**: ✅ Running
- **Port**: 8000
- **URL**: http://10.241.248.238:8000
- **Local URL**: http://localhost:8000
- **API Endpoint**: http://10.241.248.238:8000/api
- **Process ID**: 4

## Admin Panel Server
- **Status**: ✅ Running
- **Port**: 8080
- **URL**: http://10.241.248.238:8080
- **Local URL**: http://localhost:8080
- **Process ID**: 5

## Network Configuration
- **PC IP Address**: 10.241.248.238
- **Listening on**: 0.0.0.0 (all network interfaces)
- **Accessible from**: Local network devices and emulators

## Flutter App Configuration
The Flutter app is configured to use: `http://10.241.248.238:8000`

This allows the app to connect from:
- Android Emulator
- iOS Simulator
- Physical devices on the same network

## Admin Login
- **URL**: http://localhost:8080/login.php (or http://10.241.248.238:8080/login.php)
- **Email**: admin@auction.com
- **Password**: admin123

## Notes
- Servers are running in background processes
- To stop servers, use the process IDs listed above
- Make sure Windows Firewall allows connections on ports 8000 and 8080
