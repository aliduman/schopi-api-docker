<?php
require_once 'config/database.php';

header('Content-Type: application/json');

try {
    // Test database connection
    $connection_ok = testDatabaseConnection();
    
    if ($connection_ok) {
        $pdo = getDatabaseConnection();
        
        // Get database info
        $stmt = $pdo->query("SELECT DATABASE() as current_db, VERSION() as mysql_version");
        $db_info = $stmt->fetch();
        
        // Get tables count
        $stmt = $pdo->query("SELECT COUNT(*) as table_count FROM information_schema.tables WHERE table_schema = DATABASE()");
        $table_info = $stmt->fetch();
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Database connection successful',
            'database' => $db_info['current_db'],
            'mysql_version' => $db_info['mysql_version'],
            'table_count' => $table_info['table_count'],
            'timestamp' => date('c')
        ], JSON_PRETTY_PRINT);
        
    } else {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Database connection failed',
            'timestamp' => date('c')
        ], JSON_PRETTY_PRINT);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage(),
        'timestamp' => date('c')
    ], JSON_PRETTY_PRINT);
}
?>
