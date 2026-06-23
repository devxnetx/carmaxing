@echo off
setlocal

cd /d "%~dp0"

echo ========================================
echo Bid.cars worker
echo ========================================
echo.

where node >nul 2>nul
if errorlevel 1 (
    echo ERROR: Node.js is not installed or not in PATH.
    echo Install Node.js from https://nodejs.org
    pause
    exit /b 1
)

if not exist "node_modules\playwright" (
    echo Installing dependencies...
    call npm install
    if errorlevel 1 (
        echo ERROR: npm install failed.
        pause
        exit /b 1
    )
)

if not exist "import.config.json" (
    echo ERROR: import.config.json is missing.
    pause
    exit /b 1
)

set BID_CARS_HEADLESS=0
node worker.mjs

echo.
pause