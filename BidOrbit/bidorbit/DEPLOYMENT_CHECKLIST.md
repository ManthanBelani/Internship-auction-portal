# Deployment Checklist

Use this checklist to prepare your Flutter Auction App for production deployment.

## Pre-Deployment Checklist

### 1. Configuration ✓

- [ ] Update API base URL in `lib/config/api_config.dart`
  - [ ] Production API URL configured
  - [ ] WebSocket URL configured
  - [ ] HTTPS enabled for production

- [ ] Update app name in `pubspec.yaml`
  ```yaml
  name: your_app_name
  description: Your app description
  ```

- [ ] Update app version
  ```yaml
  version: 1.0.0+1
  ```

### 2. Branding ✓

- [ ] Replace app icon
  - [ ] Android: `android/app/src/main/res/mipmap-*/ic_launcher.png`
  - [ ] iOS: `ios/Runner/Assets.xcassets/AppIcon.appiconset/`
  - [ ] Or use `flutter_launcher_icons` package

- [ ] Update splash screen
  - [ ] Android: `android/app/src/main/res/drawable/launch_background.xml`
  - [ ] iOS: `ios/Runner/Assets.xcassets/LaunchImage.imageset/`

- [ ] Update app name
  - [ ] Android: `android/app/src/main/AndroidManifest.xml`
  - [ ] iOS: `ios/Runner/Info.plist`

### 3. Security ✓

- [ ] Remove debug prints
  - [ ] Search for `print()` statements
  - [ ] Remove or replace with proper logging

- [ ] Verify API security
  - [ ] HTTPS enabled
  - [ ] JWT token expiration configured
  - [ ] Secure token storage verified

- [ ] Update API keys
  - [ ] Remove any hardcoded API keys
  - [ ] Use environment variables

- [ ] Enable ProGuard (Android)
  - [ ] Configure `android/app/build.gradle`

### 4. Testing ✓

- [ ] Test on physical devices
  - [ ] Android device
  - [ ] iOS device (if applicable)

- [ ] Test all features
  - [ ] Login/Registration
  - [ ] Property browsing
  - [ ] Search and filters
  - [ ] Property details
  - [ ] Bid placement
  - [ ] Favorites
  - [ ] My Bids
  - [ ] Notifications
  - [ ] Real-time updates

- [ ] Test edge cases
  - [ ] No internet connection
  - [ ] Server errors
  - [ ] Invalid inputs
  - [ ] Empty states

- [ ] Test on different screen sizes
  - [ ] Small phones
  - [ ] Large phones
  - [ ] Tablets

- [ ] Test dark mode
  - [ ] All screens in dark mode
  - [ ] Proper contrast

### 5. Performance ✓

- [ ] Run performance tests
  ```bash
  flutter run --profile
  ```

- [ ] Check app size
  ```bash
  flutter build apk --analyze-size
  ```

- [ ] Optimize images
  - [ ] Compress images
  - [ ] Use appropriate formats

- [ ] Test memory usage
  - [ ] Check for memory leaks
  - [ ] Profile memory usage

### 6. Code Quality ✓

- [ ] Run analyzer
  ```bash
  flutter analyze
  ```

- [ ] Format code
  ```bash
  flutter format .
  ```

- [ ] Remove unused imports
- [ ] Remove commented code
- [ ] Update documentation

### 7. Android Specific ✓

- [ ] Update package name
  - [ ] `android/app/build.gradle`
  - [ ] `android/app/src/main/AndroidManifest.xml`

- [ ] Configure signing
  - [ ] Create keystore
  - [ ] Update `android/key.properties`
  - [ ] Update `android/app/build.gradle`

- [ ] Update permissions in `AndroidManifest.xml`
  ```xml
  <uses-permission android:name="android.permission.INTERNET"/>
  ```

- [ ] Set minimum SDK version
  ```gradle
  minSdkVersion 21
  ```

- [ ] Update app version
  ```gradle
  versionCode 1
  versionName "1.0.0"
  ```

### 8. iOS Specific ✓

- [ ] Update bundle identifier
  - [ ] `ios/Runner.xcodeproj/project.pbxproj`
  - [ ] Xcode project settings

- [ ] Configure signing
  - [ ] Open in Xcode
  - [ ] Select development team
  - [ ] Configure provisioning profile

- [ ] Update Info.plist
  - [ ] App name
  - [ ] Version
  - [ ] Permissions

- [ ] Set deployment target
  ```
  iOS 11.0 or higher
  ```

### 9. Backend Integration ✓

- [ ] Verify all API endpoints
  - [ ] Authentication endpoints
  - [ ] Items endpoints
  - [ ] Bids endpoints
  - [ ] Watchlist endpoints
  - [ ] Notifications endpoints

- [ ] Test WebSocket connection
  - [ ] Connection established
  - [ ] Real-time updates working
  - [ ] Auto-reconnect working

- [ ] Verify error handling
  - [ ] Network errors
  - [ ] Server errors
  - [ ] Validation errors

### 10. Legal & Compliance ✓

- [ ] Add privacy policy
  - [ ] Create privacy policy page
  - [ ] Link in app

- [ ] Add terms of service
  - [ ] Create terms page
  - [ ] Link in app

- [ ] Add licenses
  - [ ] Include open source licenses
  - [ ] Flutter license page

- [ ] GDPR compliance (if applicable)
  - [ ] Data collection disclosure
  - [ ] User consent

## Build Process

### Android Build

1. **Build APK for testing:**
   ```bash
   flutter build apk --release
   ```

2. **Build App Bundle for Play Store:**
   ```bash
   flutter build appbundle --release
   ```

3. **Locate build files:**
   - APK: `build/app/outputs/flutter-apk/app-release.apk`
   - AAB: `build/app/outputs/bundle/release/app-release.aab`

### iOS Build

1. **Build for iOS:**
   ```bash
   flutter build ios --release
   ```

2. **Open in Xcode:**
   ```bash
   open ios/Runner.xcworkspace
   ```

3. **Archive and upload:**
   - Product > Archive
   - Distribute App
   - Upload to App Store Connect

## App Store Submission

### Google Play Store

- [ ] Create developer account
- [ ] Prepare store listing
  - [ ] App title
  - [ ] Short description
  - [ ] Full description
  - [ ] Screenshots (phone, tablet)
  - [ ] Feature graphic
  - [ ] App icon

- [ ] Set up app in Play Console
  - [ ] Create app
  - [ ] Fill in store listing
  - [ ] Upload screenshots
  - [ ] Set content rating
  - [ ] Set pricing

- [ ] Upload app bundle
  - [ ] Create release
  - [ ] Upload AAB file
  - [ ] Add release notes

- [ ] Submit for review

### Apple App Store

- [ ] Create developer account
- [ ] Prepare store listing
  - [ ] App name
  - [ ] Subtitle
  - [ ] Description
  - [ ] Keywords
  - [ ] Screenshots (all sizes)
  - [ ] App icon

- [ ] Set up app in App Store Connect
  - [ ] Create app
  - [ ] Fill in app information
  - [ ] Upload screenshots
  - [ ] Set pricing
  - [ ] Set availability

- [ ] Upload build
  - [ ] Archive in Xcode
  - [ ] Upload to App Store Connect
  - [ ] Select build for release

- [ ] Submit for review

## Post-Deployment

### Monitoring

- [ ] Set up analytics
  - [ ] Firebase Analytics
  - [ ] Google Analytics
  - [ ] Custom analytics

- [ ] Set up crash reporting
  - [ ] Firebase Crashlytics
  - [ ] Sentry

- [ ] Monitor app performance
  - [ ] Load times
  - [ ] API response times
  - [ ] Error rates

### Maintenance

- [ ] Monitor user feedback
  - [ ] App store reviews
  - [ ] Support emails
  - [ ] Social media

- [ ] Plan updates
  - [ ] Bug fixes
  - [ ] New features
  - [ ] Performance improvements

- [ ] Keep dependencies updated
  ```bash
  flutter pub outdated
  flutter pub upgrade
  ```

## Version Control

- [ ] Tag release
  ```bash
  git tag -a v1.0.0 -m "Version 1.0.0"
  git push origin v1.0.0
  ```

- [ ] Create release branch
  ```bash
  git checkout -b release/1.0.0
  ```

- [ ] Update changelog
  - [ ] Document changes
  - [ ] List new features
  - [ ] List bug fixes

## Backup

- [ ] Backup source code
- [ ] Backup signing keys
- [ ] Backup API credentials
- [ ] Document deployment process

## Final Checks

- [ ] All features working
- [ ] No critical bugs
- [ ] Performance acceptable
- [ ] UI/UX polished
- [ ] Documentation complete
- [ ] Legal requirements met
- [ ] Store listings ready
- [ ] Builds successful

## Launch Day

- [ ] Submit to app stores
- [ ] Announce on social media
- [ ] Send to beta testers
- [ ] Monitor for issues
- [ ] Respond to feedback
- [ ] Celebrate! 🎉

---

## Quick Commands Reference

```bash
# Clean build
flutter clean

# Get dependencies
flutter pub get

# Run analyzer
flutter analyze

# Format code
flutter format .

# Build Android APK
flutter build apk --release

# Build Android App Bundle
flutter build appbundle --release

# Build iOS
flutter build ios --release

# Check app size
flutter build apk --analyze-size

# Run in profile mode
flutter run --profile

# Run in release mode
flutter run --release
```

## Troubleshooting

### Build Fails

1. Clean and rebuild:
   ```bash
   flutter clean
   flutter pub get
   flutter build apk
   ```

2. Check for errors:
   ```bash
   flutter doctor
   flutter analyze
   ```

### Signing Issues (Android)

1. Verify keystore exists
2. Check `key.properties` file
3. Verify `build.gradle` configuration

### Signing Issues (iOS)

1. Open in Xcode
2. Check signing settings
3. Verify provisioning profile
4. Check bundle identifier

---

**Good luck with your deployment! 🚀**
