@echo off
echo ========================================
echo LCE 2.0 - Setup Checker
echo ========================================
echo.

echo Checking PHP installation...
where php >nul 2>&1
if %errorlevel% == 0 (
    php --version
    echo [OK] PHP is installed
) else (
    echo [ERROR] PHP is not found in PATH
    echo Please install PHP 8.2+ or add it to your PATH
    echo Download from: https://windows.php.net/download/
)
echo.

echo Checking Composer installation...
where composer >nul 2>&1
if %errorlevel% == 0 (
    composer --version
    echo [OK] Composer is installed
) else (
    echo [ERROR] Composer is not found in PATH
    echo Please install Composer
    echo Download from: https://getcomposer.org/download/
)
echo.

echo Checking MySQL installation...
where mysql >nul 2>&1
if %errorlevel% == 0 (
    mysql --version
    echo [OK] MySQL is installed
) else (
    echo [WARNING] MySQL is not found in PATH
    echo You can use SQLite instead (see QUICK_START.md)
)
echo.

echo ========================================
echo Next Steps:
echo ========================================
echo.
echo 1. If PHP and Composer are installed:
echo    - Run: composer install
echo    - Copy .env.example to .env
echo    - Run: php artisan key:generate
echo    - Run: php artisan migrate
echo    - Run: php artisan db:seed
echo    - Run: php artisan serve
echo.
echo 2. If NOT installed:
echo    - See QUICK_START.md for installation guides
echo    - Or use XAMPP/Laragon
echo.
echo ========================================
pause
