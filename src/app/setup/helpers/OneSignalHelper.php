<?php

require_once __DIR__ . '/Env.php';

class OneSignalHelper {
    public static function sendNotification($playerId, $title, $subtitle, $message, $inviter_id, $list_id, $token) {
        $appId = Env::get('ONESIGNAL_APP_ID', '');
        $apiKey = Env::get('ONESIGNAL_API_KEY', '');
        if ($appId === '' || $apiKey === '') {
            error_log('OneSignal credentials are not configured');
            return false;
        }

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
}
