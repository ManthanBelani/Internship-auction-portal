# Refresh Token Implementation

## Overview
Implemented a complete refresh token system for enhanced security and better user experience. Access tokens expire after 1 hour, while refresh tokens last 30 days, allowing seamless token renewal without requiring users to re-login.

## Backend Implementation

### 1. Token Generation (src/Utils/Auth.php)

**Access Token:**
- Expiry: 1 hour (configurable via JWT_EXPIRES_IN env variable)
- Used for API authentication
- Short-lived for security

**Refresh Token:**
- Expiry: 30 days (configurable via JWT_REFRESH_EXPIRES_IN env variable)
- Used only to obtain new access tokens
- Different secret key for added security
- Includes 'type: refresh' in payload

### 2. User Service (src/Services/UserService.php)

**Updated Methods:**
- `registerUser()`: Returns both access and refresh tokens
- `authenticateUser()`: Returns both access and refresh tokens
- `refreshAccessToken()`: New method to refresh tokens

**Refresh Token Flow:**
1. Verify refresh token validity
2. Check if user still exists
3. Generate new access token
4. Generate new refresh token (token rotation)
5. Return both tokens

### 3. User Controller (src/Controllers/UserController.php)

**New Endpoint:**
- `POST /api/users/refresh`
- Accepts: `{"refreshToken": "..."}`
- Returns: `{"token": "...", "refreshToken": "..."}`


## Flutter App Implementation

### 1. API Config (lib/config/api_config.dart)

**Added:**
- `refreshToken` endpoint constant
- `refreshTokenKey` storage key constant

### 2. Auth Service (lib/services/auth_service.dart)

**New Methods:**
- `saveRefreshToken()`: Store refresh token securely
- `getRefreshToken()`: Retrieve stored refresh token
- `deleteRefreshToken()`: Remove refresh token
- `refreshAccessToken()`: Call backend to refresh tokens

**Updated Methods:**
- `login()`: Now saves both tokens
- `register()`: Now saves both tokens
- `logout()`: Clears both tokens

### 3. API Service (lib/services/api_service.dart)

**Automatic Token Refresh:**
- `_makeRequestWithRetry()`: Wraps all API calls
- `_tryRefreshToken()`: Attempts token refresh on 401 errors
- Automatically retries failed request with new token
- Seamless for the user - no interruption

**Flow:**
1. API request fails with 401 Unauthorized
2. Automatically calls refresh endpoint
3. Saves new tokens
4. Retries original request
5. Returns result to caller

### 4. Token Rotation

**Security Feature:**
- Each refresh generates a NEW refresh token
- Old refresh token becomes invalid
- Prevents token replay attacks
- Limits damage if token is compromised


## Security Benefits

1. **Short-Lived Access Tokens**: Reduces window of vulnerability if token is stolen
2. **Long-Lived Refresh Tokens**: Better UX without compromising security
3. **Token Rotation**: New refresh token on each use prevents reuse
4. **Separate Secrets**: Different keys for access and refresh tokens
5. **Automatic Cleanup**: Failed refresh clears all tokens

## User Experience

### Seamless Token Refresh
- User never sees token expiration
- No interruption to app usage
- Automatic background refresh
- Only re-login after 30 days of inactivity

### Example Scenario
1. User logs in → Gets 1-hour access token + 30-day refresh token
2. After 1 hour, access token expires
3. User makes API request → Gets 401 error
4. App automatically uses refresh token → Gets new tokens
5. Original request retries and succeeds
6. User never notices anything happened

## Configuration

### Backend (.env file)
```env
JWT_SECRET=your-secret-key
JWT_EXPIRES_IN=3600          # 1 hour in seconds
JWT_REFRESH_EXPIRES_IN=2592000  # 30 days in seconds
```

### Recommended Settings
- **Development**: Shorter expiry for testing (e.g., 5 minutes)
- **Production**: 1 hour access, 30 days refresh
- **High Security**: 15 minutes access, 7 days refresh


## Testing Instructions

### Test Token Expiration & Refresh

1. **Set Short Expiry for Testing**
   - Update `.env`: `JWT_EXPIRES_IN=60` (1 minute)
   - Restart PHP server

2. **Login and Wait**
   - Login to the app
   - Use the app normally for 30 seconds
   - Wait 1 minute for token to expire

3. **Make API Request**
   - Navigate to different screens
   - Try to place a bid or add to watchlist
   - ✅ Should work seamlessly (auto-refresh happens)

4. **Check Logs**
   - Watch network requests in Flutter DevTools
   - Should see refresh endpoint called automatically
   - Original request retries after refresh

### Test Refresh Token Expiration

1. **Set Short Refresh Expiry**
   - Update `.env`: `JWT_REFRESH_EXPIRES_IN=120` (2 minutes)
   - Restart server

2. **Login and Wait**
   - Login to the app
   - Close app completely
   - Wait 3 minutes

3. **Reopen App**
   - ✅ Should redirect to login screen
   - Refresh token expired, can't auto-login

### Test Token Rotation

1. Login to the app
2. Check stored refresh token (using Flutter DevTools)
3. Wait for access token to expire
4. Make an API request
5. Check refresh token again
6. ✅ Should be different (rotated)


## Files Modified

### Backend
1. `src/Utils/Auth.php` - Added refresh token generation and verification
2. `src/Services/UserService.php` - Updated login/register, added refresh method
3. `src/Controllers/UserController.php` - Added refresh endpoint, updated responses
4. `public/index.php` - Added `/api/users/refresh` route

### Flutter
1. `lib/config/api_config.dart` - Added refresh token constants
2. `lib/services/auth_service.dart` - Added refresh token storage and refresh method
3. `lib/services/api_service.dart` - Added automatic token refresh on 401 errors

## API Endpoints

### POST /api/users/login
**Request:**
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**Response:**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "refreshToken": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "user": {
    "userId": 1,
    "email": "user@example.com",
    "name": "John Doe",
    "role": "buyer"
  }
}
```

### POST /api/users/refresh
**Request:**
```json
{
  "refreshToken": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

**Response:**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "refreshToken": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

## Summary

✅ Access tokens expire after 1 hour for security
✅ Refresh tokens last 30 days for convenience
✅ Automatic token refresh on expiration
✅ Token rotation prevents replay attacks
✅ Seamless user experience
✅ No code changes needed in existing API calls
