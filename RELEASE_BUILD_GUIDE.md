# BidOrbit - Release Build Guide

## 📱 Building Release APK/App Bundle

This guide covers building production-ready releases for Android and iOS.

---

## 🤖 Android Release Build

### Prerequisites
- Flutter SDK installed
- Android SDK installed
- Java JDK installed
- Android Studio (optional but recommended)

### Step 1: Update App Configuration

#### Update `android/app/build.gradle`
```gradle
android {
    namespace "com.bidorbit.app"
    compileSdkVersion 34
    
    defaultConfig {
        applicationId "com.bidorbit.app"
        minSdkVersion 21
        targetSdkVersion 34
        versionCode 1
        versionName "1.0.0"
    }
    
    buildTypes {
        release {
            signingConfig signingConfigs.release
            minifyEnabled true
            shrinkResources true
            proguardFiles getDefaultProguardFile('proguard-android-optimize.txt'), 'proguard-rules.pro'
        }
    }
}
```

### Step 2: Generate Signing Key

```bash
# Generate keystore (run once)
keytool -genkey -v -keystore ~/bidorbit-release-key.jks -keyalg RSA -keysize 2048 -validity 10000 -alias bidorbit

# You'll be prompted for:
# - Keystore password (remember this!)
# - Key password (remember this!)
# - Your name, organization, etc.
```

### Step 3: Configure Signing

Create `android/key.properties`:
```properties
storePassword=YOUR_KEYSTORE_PASSWORD
keyPassword=YOUR_KEY_PASSWORD
keyAlias=bidorbit
storeFile=/path/to/bidorbit-release-key.jks
```

Update `android/app/build.gradle` to use signing config:
```gradle
def keystoreProperties = new Properties()
def keystorePropertiesFile = rootProject.file('key.properties')
if (keystorePropertiesFile.exists()) {
    keystoreProperties.load(new FileInputStream(keystorePropertiesFile))
}

android {
    // ... existing config
    
    signingConfigs {
        release {
            keyAlias keystoreProperties['keyAlias']
            keyPassword keystoreProperties['keyPassword']
            storeFile keystoreProperties['storeFile'] ? file(keystoreProperties['storeFile']) : null
            storePassword keystoreProperties['storePassword']
        }
    }
}
```

### Step 4: Build Release APK

```bash
# Navigate to Flutter project
cd BidOrbit/bidorbit

# Clean previous builds
flutter clean

# Get dependencies
flutter pub get

# Build release APK
flutter build apk --release

# Or build App Bundle (recommended for Play Store)
flutter build appbundle --release

# Build split APKs (smaller size)
flutter build apk --split-per-abi --release
```

### Output Locations
- **APK:** `build/app/outputs/flutter-apk/app-release.apk`
- **App Bundle:** `build/app/outputs/bundle/release/app-release.aab`
- **Split APKs:** `build/app/outputs/flutter-apk/app-armeabi-v7a-release.apk`, etc.

---

## 🍎 iOS Release Build

### Prerequisites
- macOS with Xcode installed
- Apple Developer Account
- CocoaPods installed

### Step 1: Update iOS Configuration

Update `ios/Runner/Info.plist`:
```xml
<key>CFBundleVersion</key>
<string>1</string>
<key>CFBundleShortVersionString</key>
<string>1.0.0</string>
<key>CFBundleDisplayName</key>
<string>BidOrbit</string>
```

### Step 2: Configure Signing

1. Open `ios/Runner.xcworkspace` in Xcode
2. Select Runner target
3. Go to Signing & Capabilities
4. Select your Team
5. Ensure "Automatically manage signing" is checked

### Step 3: Build Release IPA

```bash
# Navigate to Flutter project
cd BidOrbit/bidorbit

# Clean previous builds
flutter clean

# Get dependencies
flutter pub get

# Build iOS release
flutter build ios --release

# Or build IPA for distribution
flutter build ipa --release
```

### Output Location
- **IPA:** `build/ios/ipa/bidorbit.ipa`

---

## 🔧 Pre-Release Checklist

### Code Quality
- [ ] All diagnostics resolved
- [ ] No debug print statements
- [ ] Error handling implemented
- [ ] Loading states added
- [ ] Empty states added

### Configuration
- [ ] API base URL set to production
- [ ] Debug mode disabled
- [ ] Logging configured for production
- [ ] Analytics configured
- [ ] Crash reporting configured

### Testing
- [ ] Test on real devices
- [ ] Test all user flows
- [ ] Test payment integration
- [ ] Test order flow
- [ ] Test seller features
- [ ] Test offline scenarios
- [ ] Test different screen sizes

### Assets
- [ ] App icon configured
- [ ] Splash screen configured
- [ ] All images optimized
- [ ] Fonts included

### Legal
- [ ] Privacy policy added
- [ ] Terms of service added
- [ ] Permissions documented
- [ ] Third-party licenses included

---

## 📦 Build Commands Quick Reference

### Android
```bash
# Standard release APK
flutter build apk --release

# App Bundle (Play Store)
flutter build appbundle --release

# Split APKs (smaller)
flutter build apk --split-per-abi --release

# Debug build
flutter build apk --debug
```

### iOS
```bash
# Release build
flutter build ios --release

# IPA for distribution
flutter build ipa --release

# Debug build
flutter build ios --debug
```

### Both Platforms
```bash
# Clean build
flutter clean && flutter pub get

# Analyze code
flutter analyze

# Run tests
flutter test

# Check for updates
flutter doctor
```

---

## 🚀 Distribution

### Google Play Store
1. Create app in Play Console
2. Upload App Bundle (`.aab`)
3. Fill in store listing
4. Set up pricing & distribution
5. Submit for review

### Apple App Store
1. Create app in App Store Connect
2. Upload IPA via Xcode or Transporter
3. Fill in app information
4. Submit for review

### Direct Distribution
- Share APK file directly
- Host on website
- Use Firebase App Distribution
- Use TestFlight (iOS)

---

## 🔍 Troubleshooting

### Common Issues

**Build fails with "Gradle error"**
```bash
cd android
./gradlew clean
cd ..
flutter clean
flutter pub get
flutter build apk --release
```

**iOS build fails**
```bash
cd ios
pod deintegrate
pod install
cd ..
flutter clean
flutter pub get
flutter build ios --release
```

**App crashes on release**
- Check ProGuard rules
- Enable minification logs
- Test on multiple devices
- Check crash reports

**Large APK size**
- Use split APKs
- Remove unused resources
- Optimize images
- Enable R8/ProGuard

---

## 📊 Build Optimization

### Reduce APK Size
```bash
# Split by ABI
flutter build apk --split-per-abi --release

# Analyze size
flutter build apk --analyze-size --release
```

### Performance
- Enable R8 optimization
- Use ProGuard rules
- Optimize images
- Remove debug code
- Use const constructors

### Security
- Obfuscate code
- Enable minification
- Secure API keys
- Use HTTPS only
- Implement certificate pinning

---

## 📝 Version Management

### Semantic Versioning
- **Major.Minor.Patch** (e.g., 1.0.0)
- Major: Breaking changes
- Minor: New features
- Patch: Bug fixes

### Update Version
```yaml
# pubspec.yaml
version: 1.0.0+1
# Format: version+buildNumber
```

### Build Numbers
- Android: `versionCode` in build.gradle
- iOS: `CFBundleVersion` in Info.plist
- Increment for each release

---

## 🎯 Release Checklist

### Before Building
- [ ] Update version number
- [ ] Update changelog
- [ ] Test thoroughly
- [ ] Update API endpoints
- [ ] Configure signing
- [ ] Update app icons
- [ ] Update splash screen

### After Building
- [ ] Test release build
- [ ] Verify signing
- [ ] Check APK/IPA size
- [ ] Test on multiple devices
- [ ] Verify all features work
- [ ] Check performance
- [ ] Review crash reports

### Before Publishing
- [ ] Prepare store listing
- [ ] Create screenshots
- [ ] Write description
- [ ] Set pricing
- [ ] Configure distribution
- [ ] Submit for review

---

## 📱 App Information

### BidOrbit v1.0.0
- **Package Name:** com.bidorbit.app
- **Version Code:** 1
- **Version Name:** 1.0.0
- **Min SDK:** 21 (Android 5.0)
- **Target SDK:** 34 (Android 14)
- **iOS Deployment Target:** 12.0

---

## 🔗 Useful Links

- [Flutter Build Documentation](https://docs.flutter.dev/deployment)
- [Android App Signing](https://developer.android.com/studio/publish/app-signing)
- [iOS App Distribution](https://developer.apple.com/documentation/xcode/distributing-your-app-for-beta-testing-and-releases)
- [Play Store Guidelines](https://play.google.com/console/about/guides/)
- [App Store Guidelines](https://developer.apple.com/app-store/review/guidelines/)

---

**Last Updated:** February 22, 2026  
**Status:** Ready for Release Build
