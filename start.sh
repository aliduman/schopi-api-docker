#!/bin/bash

# Set up environment
echo "Starting PHP server on port ${PORT:-8080}"

# Log environment for debugging
echo "Environment variables:"
env | grep -v PASSWORD | sort

# Move to the public directory and check structure
cd /var/www/html
echo "Current working directory: $(pwd)"

# Check if we have a proper directory structure
echo "Checking file structure:"
ls -la .
echo "Checking public directory:"
ls -la public || echo "public directory not found!"
echo "Checking app directory:"
ls -la app || echo "app directory not found!"
echo "Checking bootstrap.php locations:"
find . -name "bootstrap.php" || echo "bootstrap.php not found!"

# If the structure is not correct, try to fix it
if [ ! -d "app" ] && [ -d "src/app" ]; then
  echo "Copying app directory from src..."
  mkdir -p app
  cp -rf src/app/* app/
  echo "Directory structure after fix:"
  ls -la app || echo "app directory still not accessible"
fi

# Create a simplified bootstrap file if missing
if [ ! -f "app/bootstrap.php" ]; then
  echo "Creating simplified bootstrap.php..."
  mkdir -p app
    cat > app/bootstrap.php <<'EOF'
  <?php
  define("APPROOT", dirname(__FILE__) . "/");
  
  class Core {
      public function __construct() {
          echo json_encode([
              "status" => false,
              "message" => "Bootstrap file missing"
          ]);
      }
  }
  
  class Routing {
      public $routes = [];
      public function get($p, $h) {}
      public function post($p, $h) {}
      public function put($p, $h) {}
      public function delete($p, $h) {}
      public function auto($p, $h) {}
  }
  
  class Api {}
  class Database {}
  class Model {}
  class File {}
  class Validate {}
  class InvalidSignatureException extends Exception {}
  class Jwt {}
  EOF
  echo "Created simplified bootstrap.php"
fi

# Start the PHP built-in web server
echo "Starting PHP server..."
cd public
echo "Serving from: $(pwd)"
php -S 0.0.0.0:${PORT:-8080} -t .
