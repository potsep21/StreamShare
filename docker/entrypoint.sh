#!/bin/sh
set -e

# Wait for MySQL to be ready
echo "Waiting for MySQL database to be ready..."
max_tries=30
tries=0

while [ $tries -lt $max_tries ]; do
  if php -r "try { new PDO('mysql:host=db;dbname=streamshare', 'streamshare', 'streamshare_password'); echo 'success'; } catch (\Exception \$e) { exit(1); }" > /dev/null 2>&1; then
    echo "MySQL is ready!"
    break
  fi
  tries=$((tries + 1))
  echo "Waiting for MySQL... ($tries/$max_tries)"
  sleep 2
done

if [ $tries -eq $max_tries ]; then
  echo "Error: MySQL did not become ready in time"
  exit 1
fi

# Run the database initialization script
echo "Initializing database..."
php /var/www/html/init-db.php

# Start Apache in foreground
echo "Starting Apache..."
exec apache2-foreground 