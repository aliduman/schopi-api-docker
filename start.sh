#!/bin/bash

# Set up environment
echo "Starting PHP server on port ${PORT:-8080}"

# Log environment for debugging
echo "Environment variables:"
env | grep -v PASSWORD | sort

# Check if files are in the expected locations
echo "Checking file structure:"
ls -la /var/www/html/
echo "Checking public directory:"
ls -la /var/www/html/public || echo "public directory not found!"
echo "Checking app directory:"
ls -la /var/www/html/app || echo "app directory not found!"
echo "Checking bootstrap.php locations:"
find /var/www/html -name "bootstrap.php" || echo "bootstrap.php not found!"

# Create a symbolic link to fix relative paths
if [ ! -L "/var/www/html/public/../app" ]; then
  echo "Creating symbolic link for app directory..."
  # Remove any existing directory first
  rm -rf /var/www/html/public/../app 2>/dev/null
  # Create the symbolic link
  ln -sf /var/www/html/app /var/www/html/public/../app
  echo "Symbolic link created"
fi

# Debug: check if the symbolic link worked
echo "Checking symbolic link:"
ls -la /var/www/html/app || echo "Symbolic link failed!"

# Start the PHP built-in web server
echo "Starting PHP server..."
cd /var/www/html/public
php -S 0.0.0.0:${PORT:-8080} -t . 2>&1 | tee /var/www/html/php_server.log
