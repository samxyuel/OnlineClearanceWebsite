@echo off
echo Restoring database from manual backup...
echo.

echo Stopping MySQL...
net stop mysql 2>nul
taskkill /F /IM mysqld.exe 2>nul

echo.
echo Removing existing database directory...
rmdir /S /Q "C:\xampp\mysql\data\online_clearance_db" 2>nul

echo.
echo Restoring database files...
xcopy "database_backups_manual\online_clearance_db_2025-09-18_08-05-19" "C:\xampp\mysql\data\online_clearance_db" /E /I /Y

echo.
echo Starting MySQL...
net start mysql 2>nul

echo.
echo Database restore completed!
pause
