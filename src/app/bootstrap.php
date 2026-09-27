<?php

/***
	 * NAP - Vanilla PHP REST-API Boilerplate
	 * https://github.com/frozeeen/Nap-PHP
	 ***/

	/** API Boilerplate Configurations */
	define('APPROOT', dirname( __FILE__ ) . '/');
	
	/*** 
	 * Once the API is loaded, this bootstrap will start the sequence
	 * by calling the core constructor which will load the required controller
	 * based on the client request
	 ***/
	require_once APPROOT . 'setup/helpers/Env.php';
	require_once APPROOT . 'setup/helpers/HttpSecurity.php';
	require_once APPROOT . 'setup/helpers/PasswordPolicy.php';
	require_once APPROOT . 'setup/helpers/ShareLinkPolicy.php';
	// Kimlik Bearer JWT ile doğrulanır. CSRF yardımcıları oturum kullanır ama
	// hiçbir route onları çağırmaz; PHPSESSID üretilmesin diye session_start yok.
	HttpSecurity::configureSessionCookies();
	require_once APPROOT . 'setup/configs/config.php';
	require_once APPROOT . 'setup/helpers/Utilities.php';

	/** Error Reporting */
	if( ENVIRONMENT != "DEV" ){
		error_reporting(1);
		set_error_handler(function($errno, $errstr, $errfile, $errline){
			http_response_code(500);
			echo json_encode([
				"status" => false,
				"message" => "Something went wrong under the hood",
                "error" => [
                    "type" => $errno,
                    "message" => $errstr,
                    "file" => $errfile,
                    "line" => $errline
                ]
			]); exit;
		});
	}else{
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

    }

	/** Load primary cores */
	$DRIVERS = [

		# Required Drivers
		'Api',
		'Core',
		'Database',
		'Model',
		'Routing',

		# Optional Drivers
		'Validate',
		'File',
        'InvalidSignatureException',
        'Jwt'
	];

	foreach ($DRIVERS as $value){
		require_once __DIR__.'/private/cores/' . $value . '.php';
	}
?>