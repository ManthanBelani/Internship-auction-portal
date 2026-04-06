@echo off
REM BidOrbit Startup Script
REM Run both backend and Flutter app on IP

echo ============================================
echo Starting BidOrbit Backend and App
echo ============================================

REM Auto-detect local IP address
for /f "tokens=2 delims=:" %%a in ('ipconfig ^| findstr /c:"IPv4 Address"') do (
    for /f "tokens=*" %%b in ("%%a") do (
        set IP=%%b
    )
)

REM Trim leading space
set IP=%IP: =%

REM Fallback IP if detection fails
if "%IP%"=="" set IP=10.0.2.2

echo Using IP: %IP%

echo.
echo Starting PHP Backend Server on 0.0.0.0:8000...
start "PHP Backend" php -S 0.0.0.0:8000 -t public

echo.
echo Waiting for backend to start...
timeout /t 2 /nobreak > nul

echo.
echo Starting Flutter App with DEV_SERVER_IP=%IP%...
cd BidOrbit\bidorbit
flutter run --dart-define=DEV_SERVER_IP=%IP%

echo.
echo ============================================
echo BidOrbit is now running!
echo Backend: http://%IP%:8000/api
echo ============================================
pause
