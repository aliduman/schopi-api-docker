<?php
/**
 * Database Configuration for Cloud SQL
 */

// Cloud SQL connection parameters
$db_config = [
    'host' => $_ENV['DB_HOST'] ?? 'localhost',
    'port' => $_ENV['DB_PORT'] ?? '3306',
    'database' => $_ENV['DB_NAME'] ?? 'schopi-api',
    'username' => $_ENV['DB_USER'] ?? 'root',
    'password' => $_ENV['DB_PASSWORD'] ?? '',
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];

// Cloud SQL Unix socket path (for Cloud Run)
$unix_socket = $_ENV['DB_SOCKET'] ?? null;

/**
 * Create database connection
 */
function getDatabaseConnection() {
    global $db_config, $unix_socket;
    
    try {
        if ($unix_socket) {
            // Use Unix socket for Cloud Run
            $dsn = "mysql:unix_socket={$unix_socket};dbname={$db_config['database']};charset={$db_config['charset']}";
        } else {
            // Use TCP connection for local development
            $dsn = "mysql:host={$db_config['host']};port={$db_config['port']};dbname={$db_config['database']};charset={$db_config['charset']}";
        }
        
        $pdo = new PDO($dsn, $db_config['username'], $db_config['password'], $db_config['options']);
        return $pdo;
        
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        throw new Exception("Database connection failed");
    }
}

/**
 * Test database connection
 */
function testDatabaseConnection() {
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->query("SELECT 1 as test");
        $result = $stmt->fetch();
        return $result['test'] === 1;
    } catch (Exception $e) {
        return false;
    }
}
?>
