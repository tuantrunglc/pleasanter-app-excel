@echo off
echo Preparing environment for Docker...

echo Creating necessary directories...
mkdir storage\framework\cache 2>nul
mkdir storage\framework\sessions 2>nul
mkdir storage\framework\views 2>nul
mkdir bootstrap\cache 2>nul

echo Setting permissions...
icacls storage /grant Everyone:F /T
icacls bootstrap\cache /grant Everyone:F /T
icacls .env /grant Everyone:F

echo Updating composer dependencies...
composer update

echo Environment prepared successfully!
echo You can now run: docker-compose up -d --build
echo After containers are running, execute: docker-compose exec -u root app bash /var/www/html/docker-fix-permissions.sh