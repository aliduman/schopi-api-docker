FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libmcrypt-dev \
    libgd-dev \
    libmemcached-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd

RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    zip \
    sockets

RUN pecl install memcached && docker-php-ext-enable memcached
RUN pecl install ev && docker-php-ext-enable ev

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy application files - create the proper directory structure
COPY src/ /var/www/html/
WORKDIR /var/www/html/public
RUN pwd && ls -la

# Create app directory and copy app files
RUN mkdir -p /var/www/html/app
RUN if [ -d "/var/www/html/src/app" ]; then cp -rf /var/www/html/src/app/* /var/www/html/app/; fi
RUN ls -la /var/www/html/app || echo "app directory not accessible"

# Fix permissions
RUN chmod -R 755 /var/www/html

# Create a robust index.php directly in the Dockerfile
RUN echo '<?php
// Enable error reporting for debugging
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);

// Debug logging
function debug_log($message) {
    error_log("[INDEX] " . $message);
}

// Check if this is a health check request
if (strpos($_SERVER["REQUEST_URI"], "/_healthz") !== false || 
    strpos($_SERVER["REQUEST_URI"], "/health") !== false) {
    header("Content-Type: application/json");
    echo json_encode([
        "status" => "healthy",
        "timestamp" => date("c"),
        "server" => "PHP/" . phpversion()
    ]);
    exit;
}

// Request URI
$request_uri = $_SERVER["REQUEST_URI"];
$script_name = $_SERVER["SCRIPT_NAME"];

// Base path
$base_path = dirname($script_name);
if ($base_path === "/") {
    $base_path = "";
}

// Request path
$request_path = $request_uri;
if ($base_path !== "") {
    $request_path = substr($request_uri, strlen($base_path));
}

// Query string
$request_path = strtok($request_path, "?");

// Special routes
$special_routes = [
    "/health" => "health.php",
    "/health.php" => "health.php",
    "/db-test" => "db-test.php",
    "/db-test.php" => "db-test.php",
];

// CORS headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Max-Age: 1000");
header("Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");

// Handle OPTIONS requests
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

// Handle special routes
if (isset($special_routes[$request_path])) {
    $special_file = __DIR__ . "/" . $special_routes[$request_path];
    if (file_exists($special_file)) {
        // Try to include database config if needed
        $db_config_paths = [
            "../app/config/database.php",
            "/var/www/html/app/config/database.php",
            dirname(__DIR__) . "/app/config/database.php"
        ];
        
        foreach ($db_config_paths as $db_path) {
            if (file_exists($db_path)) {
                require_once $db_path;
                break;
            }
        }
        
        include $special_file;
        exit;
    }
}

// Try to include bootstrap file
$bootstrap_paths = [
    "../app/bootstrap.php",
    "/var/www/html/app/bootstrap.php",
    dirname(__DIR__) . "/app/bootstrap.php"
];

$bootstrap_loaded = false;
foreach ($bootstrap_paths as $bootstrap_path) {
    if (file_exists($bootstrap_path)) {
        try {
            require_once $bootstrap_path;
            $bootstrap_loaded = true;
            break;
        } catch (Exception $e) {
            debug_log("Error loading bootstrap from $bootstrap_path: " . $e->getMessage());
        }
    }
}

// If bootstrap was loaded, initialize Core
if ($bootstrap_loaded) {
    try {
        $init = new Core();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            "status" => false,
            "message" => "Application error",
            "error" => $e->getMessage()
        ]);
        exit;
    }
} else {
    // Bootstrap not found, return error
    http_response_code(500);
    echo json_encode([
        "status" => false,
        "message" => "Application bootstrap not found",
        "error" => "The application bootstrap file could not be loaded",
        "debug" => [
            "searched_paths" => $bootstrap_paths,
            "current_dir" => __DIR__,
            "parent_dir" => dirname(__DIR__),
            "request_path" => $request_path
        ]
    ]);
    exit;
}
?>' > /var/www/html/public/index.php

# Create a health check file
RUN echo '<?php header("Content-Type: application/json"); echo json_encode(["status"=>"healthy"]);' > /var/www/html/public/_healthz

# Create a startup script
COPY start.sh /var/www/html/start.sh
RUN chmod +x /var/www/html/start.sh

# Add fallback bootstrap file if needed
RUN echo '<?php
/**
 * Fallback bootstrap file
 */
define("APPROOT", dirname(__FILE__) . "/");

// Debug function
function debug_log($message) {
    error_log("[BOOTSTRAP DEBUG] " . $message);
}

debug_log("Using fallback bootstrap.php");

// Set up minimal functionality
class Core {
    public function __construct() {
        http_response_code(500);
        echo json_encode([
            "status" => false,
            "message" => "Bootstrap error: Application files not found or incorrectly structured",
            "error" => [
                "type" => "Bootstrap Error",
                "details" => "The application bootstrap file is missing or inaccessible"
            ],
            "debug" => [
                "current_dir" => dirname(__FILE__),
                "app_root" => APPROOT
            ]
        ]);
        exit;
    }
}

// Minimal routing functionality
class Routing {
    public $routes = [];
    
    public function get($path, $handler) {
        $this->routes["GET"][$path] = ["class" => $handler];
    }
    
    public function post($path, $handler) {
        $this->routes["POST"][$path] = ["class" => $handler];
    }
    
    public function put($path, $handler) {
        $this->routes["PUT"][$path] = ["class" => $handler];
    }
    
    public function delete($path, $handler) {
        $this->routes["DELETE"][$path] = ["class" => $handler];
    }
    
    public function auto($path, $handler) {
        $this->get($path, $handler . "::get");
        $this->post($path, $handler . "::post");
        $this->put($path, $handler . "::put");
        $this->delete($path, $handler . "::delete");
    }
}

// Required classes for the application to initialize
class Api {}
class Database {}
class Model {}
class File {}
class Validate {}
class InvalidSignatureException extends Exception {}
class Jwt {}' > /var/www/html/app/bootstrap.php.fallback

RUN if [ ! -f "/var/www/html/app/bootstrap.php" ]; then \
    echo "Using fallback bootstrap.php"; \
    cp /var/www/html/app/bootstrap.php.fallback /var/www/html/app/bootstrap.php; \
fi

# Create symlinks to fix the directory structure issues
RUN mkdir -p /var/www/html/public/../app
RUN ln -sf /var/www/html/app /var/www/html/public/../app
RUN ls -la /var/www/html/public/../

# Set environment variable for Cloud Run
ENV PORT=8080

EXPOSE 8080

# Use PHP built-in web server for Cloud Run
CMD ["/var/www/html/start.sh"]
