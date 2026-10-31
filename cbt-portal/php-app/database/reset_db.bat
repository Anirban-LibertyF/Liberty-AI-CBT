@echo off
REM Rebuilds cbt_portal from scratch so every machine ends up with the exact same
REM data (presets, demo paper) as the committed schema.sql + seed.sql — no
REM leftover rows from an older setup, no duplicate-key errors on re-import.
REM Run from anywhere; edit MYSQL if XAMPP isn't at the default path.

set MYSQL=C:\xampp\mysql\bin\mysql.exe
set DIR=%~dp0

echo Dropping and recreating cbt_portal...
"%MYSQL%" -u root -e "DROP DATABASE IF EXISTS cbt_portal; CREATE DATABASE cbt_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
if errorlevel 1 goto :error

echo Importing schema...
"%MYSQL%" -u root cbt_portal < "%DIR%schema.sql"
if errorlevel 1 goto :error

echo Importing seed data (presets + demo paper)...
"%MYSQL%" -u root cbt_portal < "%DIR%seed.sql"
if errorlevel 1 goto :error

echo Importing ready-made exam papers (RRB NTPC, WBCS, WBJEE, JENPAS-UG, SMFWBEE)...
"%MYSQL%" -u root cbt_portal < "%DIR%rrb_ntpc.sql"
if errorlevel 1 goto :error
"%MYSQL%" -u root cbt_portal < "%DIR%wbcs_prelims.sql"
if errorlevel 1 goto :error
"%MYSQL%" -u root cbt_portal < "%DIR%wbjee.sql"
if errorlevel 1 goto :error
"%MYSQL%" -u root cbt_portal < "%DIR%jenpas.sql"
if errorlevel 1 goto :error
"%MYSQL%" -u root cbt_portal < "%DIR%smfwbee.sql"
if errorlevel 1 goto :error

echo.
echo Done. cbt_portal now matches schema.sql + seed.sql + the ready-made papers exactly.
goto :eof

:error
echo.
echo Something failed above — check that MySQL is running and the path to mysql.exe is correct.
exit /b 1
