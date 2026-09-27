<?php

require_once __DIR__ . '/../helpers/Env.php';

# |===============================================
# | DEVELOPMENT ENVIRONMENT
# | Auto move to production environment when not running on local network
# |===============================================
$devEnvironments = ['localhost', '127.0.0.1', '::1'];
$remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';
if (in_array($remoteAddr, $devEnvironments, true) === true) {

    # MYSQL Database Configuration — yerel varsayılanlar, üretim sırrı değil
    define('DB_HOSTNAME', Env::first(['DB_HOSTNAME', 'DB_HOST'], 'localhost'));
    define('DB_USERNAME', Env::first(['DB_USERNAME', 'DB_USER'], 'root'));
    define('DB_PASSWORD', Env::get('DB_PASSWORD', 'root'));
    define('DB_NAME', Env::get('DB_NAME', 'schopi_api'));
    define('DB_ERROR', true);
    define('ENVIRONMENT', 'DEV');

# |===============================================
# | PRODUCTION CONFIGURATION
# |===============================================
} else {
    define('DB_HOSTNAME', Env::require(['DB_HOSTNAME', 'DB_HOST']));
    define('DB_USERNAME', Env::require(['DB_USERNAME', 'DB_USER']));
    define('DB_PASSWORD', Env::require('DB_PASSWORD'));
    define('DB_NAME', Env::require('DB_NAME'));
    define('DB_ERROR', false);
    define('ENVIRONMENT', 'PROD');
}

return [
    'onesignal_app_id' => Env::get('ONESIGNAL_APP_ID', ''),
    'onesignal_api_key' => Env::get('ONESIGNAL_API_KEY', ''),
];
