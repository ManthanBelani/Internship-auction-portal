@echo off
echo ========================================
echo BidOrbit Development Server Startup
echo ========================================
echo.

REM Check if PHP is available
where php >nul 2>nul
if %errorlevel% neq 0 (
    echo ERROR: PHP is not installed or not in PATH
    pause
    exit /b 1
)

echo [1/3] Starting PHP Backend Server (Port 8000)...
start "BidOrbit API Server" cmd /k "php -S 0.0.0.0:8000 -t public"
timeout /t 2 /nobreak >nul

echo [2/3] Starting WebSocket Server (Port 8081)...
start "BidOrbit WebSocket Server" cmd /k "php websocket_server.php"
timeout /t 2 /nobreak >nul

echo [3/3] Checking server status...
timeout /t 3 /nobreak >nul

echo.
echo ========================================
echo Servers Started Successfully!
echo ========================================
echo.
echo Backend API:    http://localhost:8000
echo WebSocket:      ws://localhost:8081
echo.
echo For mobile devices, use your computer's IP:
echo Backend API:    http://10.23.134.238:8000
echo WebSocket:      ws://10.23.134.238:8081
echo.
echo Press any key to open API test...
pause >nul

REM Test the API
echo Testing API health endpoint...
curl http://localhost:8000/health
echo.
echo.
echo Development environment is ready!
echo.
