#!/bin/bash

# Set up environment
echo "Starting PHP server on port ${PORT:-8080}"

# Log environment for debugging
echo "Environment variables:"
env | grep -v PASSWORD | sort

# Check if files are in the expected locations
echo "Checking file structure:"
ls -la /var/www/html/
ls -la /var/www/html/public || echo "public directory not found!"

# Start the PHP built-in web server
# -t specifies the document root
# 0.0.0.0 listens on all interfaces
php -S 0.0.0.0:${PORT:-8080} -t /var/www/html/public /var/www/html/public/index.php
