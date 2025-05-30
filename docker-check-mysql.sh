#!/bin/bash
echo "Checking MySQL connection..."

# Wait for MySQL to be ready
echo "Waiting for MySQL to be ready..."
sleep 10

# Try to ping MySQL
echo "Pinging MySQL..."
ping -c 3 mysql

# Try to connect to MySQL using the mysql client
echo "Connecting to MySQL using mysql client..."
mysql -h mysql -u sail -ppassword -e "SELECT 1"

# Try to connect to MySQL using PHP
echo "Connecting to MySQL using PHP..."
php -r "
try {
    \$pdo = new PDO('mysql:host=mysql;dbname=laravel', 'sail', 'password');
    echo \"Connection successful!\n\";
} catch (PDOException \$e) {
    echo \"Connection failed: \" . \$e->getMessage() . \"\n\";
}
"

echo "MySQL connection check completed."