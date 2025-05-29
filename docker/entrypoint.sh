#!/bin/bash
set -e

# Wait for MySQL to be ready
echo "Waiting for MySQL database to be ready..."
until php -r "try { new PDO('mysql:host=${DB_HOST};dbname=${DB_NAME}', '${DB_USER}', '${DB_PASS}'); echo 'Connected to MySQL successfully!'; } catch (PDOException \$e) { echo \$e->getMessage(); exit(1); }" > /dev/null 2>&1
do
  echo -n "."
  sleep 1
done

echo "MySQL is ready, initializing database..."

# Run the database initialization script
php /var/www/html/php/init_db.php > /dev/null 2>&1
echo "Database initialization completed."

# Start Apache in foreground
echo "Starting Apache..."
exec "$@" 