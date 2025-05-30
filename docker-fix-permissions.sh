#!/bin/bash
echo "Fixing permissions in Docker container..."

# Ensure the script is run as root
if [ "$(id -u)" != "0" ]; then
   echo "This script must be run as root" 1>&2
   exit 1
fi

# Fix permissions for Laravel directories
chmod -R 777 /var/www/html/storage
chmod -R 777 /var/www/html/bootstrap/cache
chmod -R 777 /var/www/html/vendor
chmod 666 /var/www/html/.env

# Make sure www user owns all files
chown -R www:www /var/www/html

# Create a helper script for running composer as www user
cat > /usr/local/bin/composer-www << 'EOL'
#!/bin/bash
sudo -u www composer "$@"
EOL

chmod +x /usr/local/bin/composer-www

echo "Permissions fixed successfully!"
echo "To run composer as www user, use: composer-www install"