#!/bin/bash
echo "Preparing environment for Docker..."

echo "Creating necessary directories..."
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p bootstrap/cache

echo "Setting permissions..."
chmod -R 777 storage
chmod -R 777 bootstrap/cache
chmod 666 .env

echo "Updating composer dependencies..."
composer update

echo "Environment prepared successfully!"
echo "You can now run: docker-compose up -d --build"
echo "After containers are running, execute: docker-compose exec -u root app bash /var/www/html/docker-fix-permissions.sh"