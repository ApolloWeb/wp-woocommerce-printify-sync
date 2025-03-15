#!/bin/bash

echo "Starting MariaDB..."
mkdir -p /var/run/mysqld
chown -R mysql:mysql /var/run/mysqld
chmod 777 /var/run/mysqld

# Read database details from environment variables
DB_NAME=${WP_DB_NAME:-wordpress}
DB_USER=${WP_DB_USER:-wordpress}
DB_PASSWORD=${WP_DB_PASSWORD:-changeme}

# Initialize MariaDB if empty
if [ ! -d "/var/lib/mysql/mysql" ]; then
    echo "Initializing MariaDB..."
    mysqld --initialize-insecure --user=$DB_USER --datadir=/var/lib/mysql
fi

# Start MariaDB in the background
mysqld_safe --skip-networking --socket=/var/run/mysqld/mysqld.sock &
MYSQL_PID=$!

# Wait for MariaDB to be ready
until mysqladmin ping --silent; do
    echo "Waiting for MariaDB to start..."
    sleep 2
done

echo "MariaDB is up and running!"

# Create WordPress database and user
echo "Creating WordPress database and user..."
mysql -u root -e "CREATE DATABASE IF NOT EXISTS $DB_NAME;"
mysql -u root -e "CREATE USER IF NOT EXISTS '$DB_USER'@'%' IDENTIFIED BY '$DB_PASSWORD';"
mysql -u root -e "GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'%';"
mysql -u root -e "FLUSH PRIVILEGES;"

echo "Database setup complete!"

# Start Supervisor to manage Nginx, PHP-FPM, and MariaDB
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
