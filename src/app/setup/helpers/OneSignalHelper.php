<?php

class OneSignalHelper {
    public static function sendNotification($playerId, $title, $subtitle, $message, $inviter_id, $list_id, $token) {
        $appId = 'ca71a197-8325-4981-97a4-bfda14f1c4b5';
        $apiKey = 'os_v2_app_zjy2df4deveydf5ex7nbj4oewxhnkwj7g66uyau77imnfudozv3rhqdxbzhv6beghfjgylduwbqqqaq7qefpw74vuxmwvurz5cbl5ga';
    
        $content = [
            "en" => $message,
            "tr" => $message
        ];
    
        $heading = [
            "en" => $title,
            "tr" => $title
        ];
    
        $subtitleArr = [
            "en" => $subtitle,
            "tr" => $subtitle
        ];
    
        $fields = [
            'app_id' => $appId,
            'include_player_ids' => [$playerId],
            'headings' => $heading,
            'subtitle' => $subtitleArr,
            'contents' => $content,
            'data' => [
                'inviter_id' => $inviter_id,
                'list_id' => $list_id,
                'token' => $token
            ]
        ];
        
        error_log("NOTIFICATION FIELDS: " . json_encode($fields));
    
        // JSON encode check
        $fieldsJson = json_encode($fields);
        if ($fieldsJson === false) {
            echo 'json_encode error: ' . json_last_error_msg();
            http_response_code(500);
            exit;
        }
    
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json; charset=utf-8',
            'Authorization: Basic ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fieldsJson);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
        $response = curl_exec($ch);
    
        if ($response === false) {
            echo 'Curl error: ' . curl_error($ch);
            http_response_code(500);
            exit;
        }
    
        curl_close($ch);
    
        return $response;
    }
    
    /*public static function sendNotification($playerId, $title, $subtitle, $message, $data = []) {
        //global $config;
       // $config = require_once('/app/setup/configs/config.php');
        
        $appId = 'ca71a197-8325-4981-97a4-bfda14f1c4b5'; //$config['onesignal_app_id'];
        $apiKey = 'os_v2_app_zjy2df4deveydf5ex7nbj4oewxhnkwj7g66uyau77imnfudozv3rhqdxbzhv6beghfjgylduwbqqqaq7qefpw74vuxmwvurz5cbl5ga'; //$config['onesignal_api_key'];

        $content = [
            "en" => $message,
            "tr" => $message
        ];

        $heading = [
            "en" => $title,
            "tr" => $title
        ];

        $subtitle = [
            "en" => $subtitle,
            "tr" => $subtitle
        ];

        $jsonData = !empty($data) && is_array($data) ? json_encode($data) : '{}';

        $fields = [
            'app_id' => $appId,
            'include_player_ids' => [$playerId],
            'headings' => $heading,
            'subtitle' => $subtitle,
            'contents' => $content,
            'data' => json_decode(($jsonData)) //!empty($data) ? json_encode($data) : new stdClass() //$data
        ];

        $fieldsJson = json_encode($fields);

        // cURL işlemi
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json; charset=utf-8',
            "Authorization: Basic $apiKey"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fieldsJson);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    }*/
}

        // LOG tut
        // LOG tut – ekrana yazdır
        /*ob_start();
        error_log("=== OneSignal Push Log ===");
        error_log("Player ID: $playerId");
        error_log("Title: $title");
        error_log("Message: $message");
        error_log("Payload: $fieldsJson");
        error_log("Response: $response");
        error_log("cURL Error: $curlError");
        error_log("==========================");

        echo "</pre>";

        echo "Test mesajı"; 
        ob_end_flush();*/

/*$logMessage = "=== OneSignal Push Log ===\n";
$logMessage .= "Player ID: $playerId\n";
$logMessage .= "Title: $title\n";
$logMessage .= "Message: $message\n";
$logMessage .= "Payload: $fieldsJson\n";
$logMessage .= "Response: $response\n";
$logMessage .= "cURL Error: $curlError\n";
$logMessage .= "==========================\n";

file_put_contents(__DIR__ . '/../../logs/onesignal_log.txt', $logMessage, FILE_APPEND);*/

 //$config = require __DIR__ . '/../configs/config.php';
/*class OneSignalHelper {
    public static function sendNotification($playerId, $title, $message, $data = []) {
        global $config;
        /*$appId = config('onesignal.onesignal_app_id');
        $apiKey = config('onesignal.onesignal_api_key');*
        $appId = $config['onesignal']['onesignal_app_id'];
        $apiKey = $config['onesignal']['onesignal_api_key'];

        $content = [
            "en" => $message,
            "tr" => $message
        ];

        $heading = [
            "en" => $title,
            "tr" => $title
        ];

        $fields = [
            'app_id' => $appId, 
            'include_player_ids' => [$playerId],
            'headings' => $heading,
            'contents' => $content,
            'data' => $data
        ];

        $fields = json_encode($fields);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json; charset=utf-8',
            'Authorization: Basic ' . $apiKey 
        ]);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);

        $response = curl_exec($ch);
        curl_close($ch);

        // API yanıtını logla
        error_log("OneSignal API Response: " . $response);

        return $response;
    }
}*/
