<?php
/**
 * Ana Giriş Noktası - Tüm istekler buradan yönlendirilir
 */

// Request URI'yi al
$request_uri = $_SERVER['REQUEST_URI'];
$script_name = $_SERVER['SCRIPT_NAME'];

// Base path'i hesapla
$base_path = dirname($script_name);
if ($base_path === '/') {
    $base_path = '';
}

// Request path'i temizle
$request_path = $request_uri;
if ($base_path !== '') {
    $request_path = substr($request_uri, strlen($base_path));
}

// Query string'i kaldır
$request_path = strtok($request_path, '?');

// Özel route'ları kontrol et
$special_routes = [
    '/health' => 'public/health.php',
    '/health.php' => 'public/health.php',
    '/db-test' => 'public/db-test.php',
    '/db-test.php' => 'public/db-test.php',
];

// Özel route'ları kontrol et
if (isset($special_routes[$request_path])) {
    // Database config'i yükle
    require_once __DIR__ . '/config/database.php';
    
    // Özel dosyayı include et
    include __DIR__ . '/' . $special_routes[$request_path];
} else {
    // Ana uygulamayı başlat (REST API)
    require_once __DIR__ . '/bootstrap.php';
    
    // Core'u başlat
    $core = new Core();
}
?>
