<?php
/**
 * Ana Giriş Noktası - Tüm istekler buradan yönlendirilir
 */

// REQUEST_URI'yi doğru şekilde işle ve $_GET['url'] değişkenini oluştur
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

// $_GET['url'] değişkenini oluştur - API routing için gerekli
$_GET['url'] = trim($request_path, '/');

// Özel route'ları kontrol et
$special_routes = [
    '/health' => 'health.php',
    '/health.php' => 'health.php',
    '/db-test' => 'db-test.php',
    '/db-test.php' => 'db-test.php',
];

// Özel route'ları kontrol et
if (isset($special_routes[$request_path])) {
    // Database config'i yükle - göreceli yol yerine mutlak yol kullan
    require_once __DIR__ . '/../app/config/database.php';

    // Özel dosyayı include et
    include __DIR__ . '/' . $special_routes[$request_path];
} else {
    // CORS için başlıklar
    header('Access-Control-Allow-Origin: *');
    header('Content-Type: application/json');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 1000');
    header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization, Access-Control-Max-Age, Access-Control-Allow-Credentials, Access-Control-Allow-Methods, Access-Control-Allow-Origin, Access-Control-Allow-Headers');
    header('HTTP/1.1 200');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        return 0;
    }

    // Ana uygulamayı başlat (REST API)
    // Doğru yolu kullanarak bootstrap.php dosyasını dahil et
    require_once __DIR__ . '/../app/bootstrap.php';

    // Core sınıfını başlat
    try {
        $init = new Core();
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}
?>