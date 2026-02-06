# Auction Portal - Flutter Mobile App

A premium Flutter mobile application for real estate auctions with real-time bidding, user authentication, and a beautiful modern UI.

## 🚀 Features

- **User Authentication**: Login/Register with JWT token-based authentication
- **Auction Listings**: Browse active auctions with beautiful card layouts
- **Property Details**: View detailed information about auction items
- **Real-time Updates**: Live auction status and countdown timers
- **Bidding System**: Place bids on auction items
- **Dark Mode Support**: Automatic theme switching
- **Responsive Design**: Optimized for all screen sizes

## 📱 Screens

1. **Splash Screen**: Initial loading screen with auto-login
2. **Login Screen**: User authentication with email/password
3. **Home Screen**: Browse auction listings with categories
4. **Property Details**: Detailed view with bidding information
5. **Favorites**: Save favorite auction items (Coming Soon)
6. **My Bids**: Track your bidding history (Coming Soon)
7. **Profile**: User profile management (Coming Soon)

## 🛠️ Tech Stack

- **Framework**: Flutter 3.0+
- **State Management**: Provider
- **HTTP Client**: http package
- **Image Caching**: cached_network_image
- **Storage**: shared_preferences
- **JWT Handling**: jwt_decoder
- **Fonts**: Google Fonts (Manrope)

## 📦 Dependencies

```yaml
dependencies:
  flutter:
    sdk: flutter
  
  # UI & Icons
  cupertino_icons: ^1.0.6
  google_fonts: ^6.1.0
  
  # State Management
  provider: ^6.1.1
  
  # HTTP & API
  http: ^1.1.2
  web_socket_channel: ^2.4.0
  
  # Storage
  shared_preferences: ^2.2.2
  
  # Image Handling
  cached_network_image: ^3.3.1
  image_picker: ^1.0.7
  
  # Utils
  intl: ^0.19.0
  timeago: ^3.6.0
  
  # JWT
  jwt_decoder: ^2.0.1
```

## 🔧 Setup Instructions

### Prerequisites

- Flutter SDK (3.0 or higher)
- Dart SDK
- Android Studio / VS Code
- Android Emulator or Physical Device

### Installation

1. **Clone the repository**
   ```bash
   cd flutter_auction_app
   ```

2. **Install dependencies**
   ```bash
   flutter pub get
   ```

3. **Configure Backend URL**
   
   Edit `lib/config/app_config.dart`:
   ```dart
   static String get baseUrl {
     // For Android Emulator, use: 'http://10.0.2.2:8000'
     // For iOS Simulator/Physical Device, use: 'http://localhost:8000'
     // For Production, use your actual domain
     return 'http://localhost:8000';
   }
   ```

4. **Run the app**
   ```bash
   flutter run
   ```

## 🌐 Backend Integration

This app connects to the PHP backend auction portal. Make sure the backend is running on `http://localhost:8000` (or update the URL in `app_config.dart`).

### API Endpoints Used

- `POST /api/users/login` - User authentication
- `GET /api/items` - Fetch auction listings
- `GET /api/items/:id` - Get item details
- `POST /api/bids` - Place a bid
- `GET /api/users/profile` - Get user profile

### Authentication Flow

1. User enters credentials on Login Screen
2. App sends POST request to `/api/users/login`
3. Backend returns JWT token
4. Token is stored in SharedPreferences
5. Token is included in Authorization header for subsequent requests

## 📂 Project Structure

```
lib/
├── config/
│   └── app_config.dart          # App configuration & constants
├── providers/
│   ├── auth_provider.dart       # Authentication state management
│   ├── auction_provider.dart    # Auction data management
│   └── theme_provider.dart      # Theme state management
├── screens/
│   ├── auth/
│   │   └── login_screen.dart    # Login screen
│   ├── home/
│   │   ├── home_screen.dart     # Main home screen
│   │   └── main_navigation.dart # Bottom navigation
│   ├── details/
│   │   └── property_details_screen.dart  # Property details
│   └── splash_screen.dart       # Splash screen
└── main.dart                    # App entry point
```

## 🎨 Design System

### Colors

- **Primary**: `#2094F3` (Blue)
- **Background Light**: `#F5F7F8`
- **Background Dark**: `#101A22`

### Typography

- **Font Family**: Manrope
- **Weights**: 400 (Regular), 500 (Medium), 700 (Bold), 800 (Extra Bold)

### Spacing

- Small: 8px
- Medium: 16px
- Large: 24px
- XLarge: 32px

## 🔐 Security

- JWT tokens are stored securely in SharedPreferences
- Tokens are validated on app startup
- Expired tokens trigger automatic logout
- All API requests use HTTPS in production

## 🧪 Testing

Run tests with:
```bash
flutter test
```

## 📱 Platform Support

- ✅ Android
- ✅ iOS
- ⚠️ Web (Limited support)
- ⚠️ Desktop (Limited support)

## 🚧 Known Issues

- Image placeholders need to be added to `assets/images/`
- WebSocket real-time updates not yet implemented
- Some screens are placeholders (Favorites, My Bids, Profile)

## 🔜 Upcoming Features

- [ ] Real-time bidding with WebSockets
- [ ] Push notifications for auction updates
- [ ] Favorites/Watchlist functionality
- [ ] Bid history tracking
- [ ] User profile management
- [ ] Image upload for sellers
- [ ] Advanced search and filters
- [ ] Payment integration

## 📄 License

This project is part of an internship assignment.

## 👥 Contributors

- Manthan Belani

## 📞 Support

For issues or questions, please contact the development team.

---

**Note**: This app is designed to work with the PHP backend auction portal. Ensure the backend is running before using the app.
