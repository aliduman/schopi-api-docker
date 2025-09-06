<?php
header('Content-Type: application/json');
http_response_code(200);
echo json_encode([
    'status' => 'healthy',
    'timestamp' => date('c'),
    'version' => '1.0.0'
]);
