@echo off
chcp 65001 >nul
title Cookie Cozy - Local PHP Server
echo =========================================================
echo       🍪 COOKIE COZY - LOCAL BAKERY WEB SERVER 🍪
echo =========================================================
echo.
echo  กำลังเริ่มต้นเซิร์ฟเวอร์ PHP บนเครื่องของคุณ...
echo.

set "PHP_PATH=C:\Users\Smokey\php8"
set "PATH=%PHP_PATH%;%PATH%"

where php >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo [ERROR] ไม่พบ php.exe กรุณาตรวจสอบว่ามีโฟลเดอร์ C:\Users\Smokey\php8 หรือไม่
    pause
    exit /b 1
)

echo  [✓] พบโปรแกรม PHP เรียบร้อยแล้ว:
php -v | findstr "PHP"
echo.
echo  [✓] โฟลเดอร์เก็บไฟล์อีเมลขาออก: sent_emails\
echo.
echo  [✓] กำลังเปิดเว็บเบราว์เซอร์ไปยัง: http://localhost:8000
echo.
echo  ---------------------------------------------------------
echo   กด Ctrl + C ในหน้านี้เมื่อต้องการหยุดการทำงานของเซิร์ฟเวอร์
echo  ---------------------------------------------------------
echo.

start http://localhost:8000

php -S localhost:8000
pause
