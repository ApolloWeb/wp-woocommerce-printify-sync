#!/bin/bash

# Start MariaDB service
echo "Starting MariaDB..."
service mysql start

# Wait until MySQL is ready
until mysqladmin ping --silent; do
    echo "Waiting for MariaDB to start..."
    sleep 2
done

echo "MariaDB is up and running!"

# Set database details from environment variables
DB_NAME=${WP_DB_NAME:-wordpress}
DB_USER=${WP_DB_USER:-wordpress}
DB_PASSWORD=${WP_DB_PASSWORD:-yourpassword}

# Create WordPress database and user
echo "Creating WordPress database and user..."
mysql -u root -e "CREATE DATABASE IF NOT EXISTS $DB_NAME;"
mysql -u root -e "CREATE USER IF NOT EXISTS '$DB_USER'@'%' IDENTIFIED BY '$DB_PASSWORD';"
mysql -u root -e "GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'%';"
mysql -u root -e "FLUSH PRIVILEGES;"

echo "Database setup complete!"

# Start all services (Nginx, PHP-FPM, MariaDB)
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
