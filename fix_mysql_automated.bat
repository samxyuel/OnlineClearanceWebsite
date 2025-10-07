@echo off
echo Fixing MySQL startup issues...
echo.

echo Stopping MySQL service...
net stop mysql 2>nul
taskkill /F /IM mysqld.exe 2>nul

echo.
echo Deleting problematic Aria log files...
del "C:\xampp\mysql\data\aria_log.*" 2>nul

echo.
echo Running MySQL upgrade...
cd /d "C:\xampp\mysql\bin"
mysql_upgrade.exe --force

echo.
echo Starting MySQL service...
net start mysql 2>nul

echo.
echo Fix completed. Check XAMPP Control Panel for MySQL status.
pause
