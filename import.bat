@echo off
cd /d C:\laragon\www\sport-db
php artisan matches:import --all >> storage\logs\import.log 2>&1
