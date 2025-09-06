<?php

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

    // bootstrap.php dosyasının doğru yolunu sağla
    require_once '../app/bootstrap.php';

    // Core sınıfını başlat
    try {
        $init = new Core();
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
?>