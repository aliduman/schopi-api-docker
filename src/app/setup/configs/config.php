<?php
	
	# |===============================================
	# | DEVELOPMENT ENVIRONMENT
	# | Auto move to production environment when not running on local network
	# |===============================================
	$devEnvironments = ['localhost', '127.0.0.1', '::1'];
	if( in_array($_SERVER['REMOTE_ADDR'], $devEnvironments) === TRUE){

		# MYSQL Database Configuration
		define('DB_HOSTNAME', 'localhost');
		define('DB_USERNAME', 'root');
		define('DB_PASSWORD', 'root');
		define('DB_NAME', 'schopi_api');
		define('DB_ERROR', true);
		define("ENVIRONMENT", "DEV");

	# |===============================================
	# | PRODUCTION CONFIGURATION
	# |===============================================
	}else{
		# MYSQL Database Configuration
		define('DB_HOSTNAME', 'wyqk6x041tfxg39e.chr7pe7iynqr.eu-west-1.rds.amazonaws.com');
		define('DB_USERNAME', 'zdbwj4tfkemnh3o7');
		define('DB_PASSWORD', 'df8cblz2r2tuntcm');
		define('DB_NAME', 'itm0e2wljblq22b9');
		define('DB_ERROR', false);
		define('ENVIRONMENT', "PROD");
	}

	// One Signal Key - ID
	$appId = 'ca71a197-8325-4981-97a4-bfda14f1c4b5';
    $apiKey = 'os_v2_app_zjy2df4deveydf5ex7nbj4oewxhnkwj7g66uyau77imnfudozv3rhqdxbzhv6beghfjgylduwbqqqaq7qefpw74vuxmwvurz5cbl5ga';

	return [
		'onesignal_app_id' => $appId,
		'onesignal_api_key' => $apiKey,
	];

?>