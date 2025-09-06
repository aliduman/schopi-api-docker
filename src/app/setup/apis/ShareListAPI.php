<?php
require_once __DIR__ . '/../helpers/OneSignalHelper.php';

class ShareListAPI extends Api {

    public ShareList $ShareList;
    public int $id;
    public int $list_id;
    public int $inviter_id;
    public string $invitee_email;
    public $inviter_permission;
    public $request;
    public $token;
    public $status;
    public $created_at;
    public $updated_at;
    public $email;
    public $expired_date;

    public Authentication $Authentication;
    
    function __construct() {
        /* Login gerektiren sınıflarda bu alanın eklenmesi gerekiyor */
        $this->Authentication = $this->model("Authentication");
        if (!$this->Authentication->authenticateJWTToken()) {
            exit;
        }
        // JWT içinden aldığımız kullanıcı id
        //$this->userId = $this->Authentication->userData['id'];

        $this->ShareList = $this->model('ShareList');
    }
    
    // Share List Generate Token
    function generateShareToken($list_id, $inviter_id, $invitee_email) {
        $JwtCtrl = new Jwt("Sdw1");
        $issueAt = time();
        $expirationTime = $issueAt + (60 * 60 * 24);

        $payload = [
            'list_id' => $list_id,
            'inviter_id' => $inviter_id,
            'invitee_email' => $invitee_email,
            'iat' => $issueAt,
            'exp' => $expirationTime
        ];
        return $JwtCtrl->encode($payload);
    }

    // Share List
    public function share_list() {
        $token = $this->generateShareToken(
            $this->request->list_id,
            $this->request->inviter_id,
            $this->request->invitee_email,
        );

        $return = $this->ShareList->share_list([
            'list_id' => $this->request->list_id,
            'inviter_id' => $this->request->inviter_id,
            'invitee_email' => $this->request->invitee_email,
            'inviter_permission' => $this->request->inviter_permission,
            'token' => $token,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
            'expired_date' => date('Y-m-d H:i:s', strtotime('+24 hours'))
        ]);

        // HATA KONTROLÜ 
        if (!$return || (isset($return[0]) && $return[0] === false)) {
            // Liste bulunamadı veya zaten paylaşım var
            $errorResponse = [
                'status' => 'error',
                false
            ];

            $this->json($errorResponse);
            return; // Fonksiyondan çık - bildirim oluşturma
        }

        // Davet edilen kişinin bilgilerini al. 
        $userInvites = $this->ShareList->get_user_by_email_with_player_id($this->request->invitee_email);
            if (!$userInvites) {
            // Kullanıcı bulunamadı
            $errorResponse = [
                false
            ];
            $this->json($errorResponse);
            return;
        }

        // Bildirim oluştur.
        $this->createShareNotifications();

        if (!empty($userInvites) && !empty($userInvites['player_id'])) {
            //echo "Player ID: " . $userInvites['player_id'];
            $this->sendPushNotification(
                $userInvites['player_id'],
                'Schopi',
                'Yeni Liste Daveti',
                'Bir kullanıcı sizinle bir liste paylaştı.',
                $this->request->inviter_id,
                $this->request->list_id,
                $token
            );
        }
        $this->json($return);
    }

    // Bildirim oluştur
    public function createShareNotifications() {
        // Paylaşımı yapan kişinin bilgilerini al
        $data = $this->ShareList->get_share_list($this->request->inviter_id);
        $inviterProfileImage = isset($data['data']->profile_image) ? $data['data']->profile_image : '';

        // Davet edilen kişinin user_id'sini al
        $user = $this->ShareList->get_user_by_email_with_player_id($this->request->invitee_email);
        
        // Veri yapısını kontrol et - array mı object mi?
        $userId = null;
        if (is_array($user) && isset($user['id'])) {
            $userId = $user['id'];
        } elseif (is_object($user) && isset($user->id)) {
            $userId = $user->id;
        }

        // Davet edilen kişi kayıtlı değilse bildirim oluşturma.
        if (!$userId) {
            echo "Davet edilen kişi sistemde kayıtlı değil! : " . $this->request->invitee_email;
            return;
        }

        // Notifications modelin yükle
        $Notifications = $this->model("Notifications");

        // Önce aynı liste için bu kullanıcıya daha önce bildirim gönderilmiş mi kontrol et
        $existingNotification = $Notifications->get_notification_by_list_and_user(
            $this->request->list_id, 
            $userId, 
            'list_invite'
        );

        if ($existingNotification) {
            echo "Bu liste için bu kullanıcıya zaten bildirim gönderilmiş. Liste ID: " . $this->request->list_id . ", User ID: " . $userId;
            return; // Bildirim zaten var, yeni oluşturma
        }

        $coreListId = $this->request->list_id;
        if (property_exists($this->request, 'core_list_id') && 
            !empty($this->request->core_list_id) && 
            $this->request->core_list_id > 0) {
            $coreListId = $this->request->core_list_id;
        }

        // Liste adını al (request'te yoksa default döner)
        $listName = isset($data['data']->list_name) ? $data['data']->list_name : 'Paylaşılan Liste';


        // Bildirim verilerini hazırla
        $notificationData = [
            'title' => 'Yeni Liste Daveti',
            'sub_title' => $listName,
            'description' => 'Bir kullanıcı sizinle liste paylaştı.',
            'image' => $inviterProfileImage,
            'list_id' => $this->request->list_id,
            'user_id' => $userId,
            'type' => 'list_invite',
            'core_list_id' => $coreListId,
            'is_read' => 0,
            'scheduled_date' => date('Y-m-d H:i:s'),
            'created_date' => date('Y-m-d H:i:s'),
            'updated_date' => date('Y-m-d H:i:s'),
        ];

        // Bildirim oluştur
        $result = $Notifications->create_notification($notificationData);
    }


    public function sendPushNotification($player_id, $title, $subtitle, $message, $inviter_id, $list_id, $token) {
        OneSignalHelper::sendNotification($player_id, $title, $subtitle, $message, $inviter_id, $list_id, $token);
    }

    /*public function sendPushNotification($player_id, $title, $subtitle, $message, $data = []) {
        OneSignalHelper::sendNotification($player_id, $title, $message, $data);
    }*/

    public function get_share_list() {
        // inviter_id parametresinin olup olmadığını kontrol ediyoruz
        if (!isset($_GET['inviter_id']) || empty($_GET['inviter_id'])) {
            return [
                'status' => 'error',
                'data' => 'inviter_id parametresi eksik.'
            ];
        }
    
        // URL parametresinden inviter_id'yi alıyoruz
        $inviter_id = $_GET['inviter_id'];
    
        // ShareList sınıfındaki get_share_list metodunu çağırıyoruz
        $result = $this->ShareList->get_share_list($inviter_id);
        /*echo json_encode($result); // ← test için bunu koy
        exit;*/
    
        // Sonuçları kontrol ediyoruz
        if ($result && $result['status'] === 'success') {
            echo json_encode($result);
        } else {
            echo json_encode([
                'status' => 'error',
                'data' => 'inviter_id ile eşleşen paylaşım bulunamadı.'
            ]);
        }
        exit;
    }

    // Accepted Invite
    /*public function accepted_invite(): void
    {
        $return = $this->ShareList->update_invite_status($this->id, [
            'status' => 'accepted',
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $this->json($return);
    }*/
    
    /*public function accepted_invite(): void
    {
        $id = $_GET['id'] ?? $this->id ?? null;
        
        if (empty($id)) {
            $this->json(['status' => 'error', 'message' => 'ID eksik']);
            return;
        }
        
        $return = $this->ShareList->update_invite_status_and_list($id, [
            'status' => 'accepted',
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $this->json($return);
    }*/

    public function get_invitees_by_list() {
        if (!isset($_GET['list_id']) || empty($_GET['list_id'])) {
            echo json_encode(['status' => 'error', 'message' => 'list_id parametresi eksik.']);
            exit;
        }

        $list_id = $_GET['list_id'];
        $result = $this->ShareList->get_invitees_by_list($list_id);
        echo json_encode($result);
        exit;
    }

    public function accepted_invite(): void
    {
        $id = $_GET['id'] ?? $this->id ?? null;
        
        if (empty($id)) {
            $this->json(['status' => 'error', 'message' => 'ID eksik']);
            return;
        }
        
        // 1. Share kaydını bul ve paylaşılan liste bilgilerini getir
        $shareData = $this->ShareList->get_share_with_list_data($id);
        
        if (!$shareData) {
            $this->json(['status' => 'error', 'message' => 'Paylaşım kaydı bulunamadı']);
            return;
        }
        
        // 2. Normal güncelleme işlemlerini yap
        $updateResult = $this->ShareList->update_invite_status_and_list($id, [
            'status' => 'accepted',
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        if ($updateResult['status'] === 'error') {
            $this->json($updateResult);
            return;
        }
        
        // 3. Swift'e gönderilecek liste verisini hazırla
        $response = [
            'status' => 'success',
            'message' => 'Davet kabul edildi',
            'shared_list_data' => [
                'server_id' => $shareData['list_id'], // Sunucudaki liste ID'si
                'name' => $shareData['list_name'],
                'image' => $shareData['list_image'],
                'theme_name' => $shareData['list_theme_name'],
                'isTick' => $shareData['list_is_tick'],
                'time' => $shareData['list_time'],
                'shared' => 1, // Paylaşılan liste olduğunu belirt
                'inviter_info' => [
                    'name' => $shareData['inviter_name'],
                    'email' => $shareData['inviter_email'],
                    'profile_image' => $shareData['inviter_profile_image']
                ]
            ],
            'core_data_action' => 'add_shared_list' // Swift'e ne yapması gerektiğini söyle
        ];
        
        $this->json($response);
    }

    // Rejected Invite
    public function decline_invite() {
        $return = $this->ShareList->update_invite_status($this->id, [
            'status' => 'declined',
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $this->json($return);
    }

    // Get the lists the user was invited to
    public function get_user_invites() {
        $email = $this->request->invitee_email;
        $this->json($this->ShareList->get_invites_by_email($email));
    }

    // Delete any share list
    public function delete_invite() {
        $this->json($this->ShareList->delete_share_list($this->id));
    }

    // Get share list entries by email
    public function get_invites_by_email() {
        $email = $_GET['email'] ?? null;
       $this->json($this->ShareList->get_invites_by_email($email));
    }

    public function is_token_check() {
        $return = $this->ShareList->is_token_expired($this->request->token);
        $this->json($return);
    }
}

/*public function share_list() {
        $return = $this->ShareList->share_list([
            'list_id' => $this->request->list_id,
            'inviter_id' => $this->request->inviter_id,
            'invitee_email' => $this->request->invitee_email,
            'inviter_permission' => $this->request->inviter_permission,
            'token' => bin2hex(random_bytes(16)),
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
            'expired_date' => date('Y-m-d H:i:s', strtotime('+24 hours'))
        ]);

        $this->json($return);
    }*/