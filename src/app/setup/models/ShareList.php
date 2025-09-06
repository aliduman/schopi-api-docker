<?php

class ShareList extends Model {

    public function __construct() { 
        $this->table_name = 'share_list';
        $this->table_columns = [
            'id', 
            'list_id', 
            'inviter_id', 
            'invitee_email', 
            'inviter_permission',
            'status',
            'token',
            'created_at',
            'expired_date'
        ];
    }

    // Liste paylaşılmış mı kontrolü
    public function check_existing_share($list_id, $invitee_email) {
        $sql = "SELECT * FROM " . $this->table_name . " 
                WHERE list_id = :list_id AND invitee_email = :invitee_email 
                AND status IN ('pending', 'accepted')";
        
        $this->query($sql);
        $this->bind(':list_id', $list_id);
        $this->bind(':invitee_email', $invitee_email);
        
        $result = $this->resultSingle();
        return $result;
    }

    // List id kontrolü
    public function list_exists($list_id) {
        //print("BAŞLADI - Liste kontrol ID: $list_id\n");
        
        $sql = "SELECT id FROM lists WHERE id = :list_id LIMIT 1";
        //print("SQL: $sql\n");
        
        $this->query($sql);
        $this->bind(':list_id', $list_id);
        $result = $this->resultSingle();
        
        //print("Liste kontrol - ID: $list_id, Sonuç: " . ($result ? 'BULUNDU' : 'BULUNAMADI') . "\n");
        
        return $result;
    }

    // Get user by email
    public function get_user_by_id($id) {
        $sql = "SELECT id,name,surname,email,profile_image,role,token,is_deleted FROM users WHERE id = :id LIMIT 1";
        $this->query($sql);
        $this->bind(':id', $id);

        $result = $this->resultSingle();

        if ($result) {
            return (array)$result;
        } else {
            return null; // Kullanıcı bulunamadı
        }
    }
    // Kişi listeyi paylaştığında bu istek atılır.
    public function share_list(array $data){
        // Liste var mı kontrol et
        $listExists = $this->list_exists($data['list_id']);
   
        if (!$listExists) {
            return [
                false
            ];
        }

        // Önce mevcut paylaşım var mı kontrol et
        $existingShare = $this->check_existing_share($data['list_id'], $data['invitee_email']);
        
        if ($existingShare) {
            // Eğer pending veya accepted durumunda paylaşım varsa hata döndür
            return [
                false
            ];
        }
        
        // Mevcut paylaşım yoksa yeni paylaşım oluştur
        $shareList = $this->insert($data);
        // önce user bilgisini al sonrasında $shareList içine inviter(user) bilgilerini ekle
        $user = $this->get_user_by_id($data['inviter_id']);
        $shareList->inviter = $user;
        return (array)$shareList;
    }



    /*public function share_list(array $data){
        $shareList = $this->insert($data);
        # Return list data
        return (array)$shareList;
    }*/

    // Get all share list entries
    public function get_all_share_lists() {
        $shareLists = $this->getAll();
        return (array)$shareLists;
    }

    public function get_share_list($inviter_id) {
        $sql = "SELECT s.*, u.name, u.surname, u.email, u.profile_image, l.name as list_name
        FROM " . $this->table_name . " s
        JOIN users u ON s.inviter_id = u.id
        LEFT JOIN lists l ON s.list_id = l.id
        WHERE s.inviter_id = :inviter_id
        ORDER BY s.created_at DESC
        LIMIT 1";
        
        $this->query($sql);
        $this->bind(':inviter_id', $inviter_id);

        $rows = $this->resultSingle();
        
        // Sonuç varsa
        if ($rows) {
            return [
                'status' => 'success',
                'data' => $rows
            ];
        } else {
            // Sonuç yoksa
            return [
                'status' => 'error',
                'message' => 'inviter_id ile eşleşen paylaşım bulunamadı.'
            ];
        }
    }

    // Sadece belirli bir liste için davetlileri çek
    public function get_invitees_by_list($list_id) {
        $sql = "SELECT s.*, 
                    u.name as invitee_name, 
                    u.surname as invitee_surname, 
                    u.email as invitee_email, 
                    u.profile_image as invitee_profile_image,
                    l.name as list_name,
                    inviter.name as inviter_name,
                    inviter.surname as inviter_surname,
                    inviter.email as inviter_email,
                    inviter.profile_image as inviter_profile_image
                FROM " . $this->table_name . " s
                LEFT JOIN users u ON s.invitee_email = u.email
                LEFT JOIN users inviter ON s.inviter_id = inviter.id
                LEFT JOIN lists l ON s.list_id = l.id
                WHERE s.list_id = :list_id
                ORDER BY s.created_at DESC";
        
        $this->query($sql);
        $this->bind(':list_id', $list_id);
        $rows = $this->resultSet();
        
        if ($rows) {
            return ['status' => 'success', 'data' => $rows];
        } else {
            return ['status' => 'error', 'message' => 'Bu liste ile davet bulunamadı.'];
        }
    }

    // Get a new share list entry
    public function create_share_list(array $data) {
        $createdSharedList = $this->insert($data);
        return (array)$createdSharedList;
    }

    // Update a share list entry
    public function update_share_list($data, $id) {
        $updatedShareList = $this->update($data, ['id' => $id]);
        return (array)$updatedShareList;
    }

    // Delete a share list entry
    public function delete_share_list($id) {
        $deleteShareList = $this->delete($id);
        return (array)$deleteShareList;
    }

    // Update status of a specific share list entry
    /*public function update_invite_status($id, array $data) {
        $updatedStatus = $this->update($data, ['id' => $id]);
        return (array)$updatedStatus;
    }*/

    public function update_invite_status_and_list($id, array $data) {
        // Önce share_list kaydını bul
        $sql = "SELECT list_id FROM " . $this->table_name . " WHERE id = :id";
        $this->query($sql);
        $this->bind(':id', $id);
        $share_record = $this->resultSingle();
        
        // Debug için
        error_log("Share record: " . print_r($share_record, true));
        error_log("Share record type: " . gettype($share_record));
        
        if (!$share_record) {
            return ['status' => 'error', 'message' => 'Paylaşım kaydı bulunamadı'];
        }
        
        // 1. share_list tablosunu güncelle
        $this->update($data, ['id' => $id]);
        
        // 2. lists tablosunda shared sütununu 1 yap - Burada obje olarak erişim dene
        $update_list_sql = "UPDATE lists SET shared = 1 WHERE id = :list_id";
        $this->query($update_list_sql);
        $this->bind(':list_id', $share_record->list_id); // Array yerine obje erişimi
        $this->execute();
        
        return ['status' => 'success', 'message' => 'Davet kabul edildi'];
    }

    // Get share list entries by email
    public function get_invites_by_email($email) {
        $sql = "SELECT * FROM " . $this->table_name . " WHERE invitee_email = :email";
        $this->query($sql);
        $this->bind(':email', $email);
        $results = $this->resultset();
        return (array)$results;
    }

    public function get_user_by_email_with_player_id($email) {
        $sql = "SELECT id, email, player_id FROM users WHERE email = :email LIMIT 1";
        $this->query($sql);
        $this->bind(':email', $email);
        $result = $this->resultSingle();
        return $result ? (array)$result : null;
    } 

    public function get_share_with_list_data($share_id) {
        $sql = "SELECT 
                    s.*,
                    l.name as list_name,
                    l.image as list_image, 
                    l.theme_name as list_theme_name,
                    l.isTick as list_is_tick,
                    l.time as list_time,
                    u.name as inviter_name,
                    u.email as inviter_email,
                    u.profile_image as inviter_profile_image
                FROM share_list s
                LEFT JOIN lists l ON s.list_id = l.id  
                LEFT JOIN users u ON s.inviter_id = u.id
                WHERE s.id = :share_id";
        
        $this->query($sql);
        $this->bind(':share_id', $share_id);
        $result = $this->resultSingle();
        
        return $result ? (array)$result : null;
    }

     // Validate Token
    public function is_token_expired($token) {
        $sql = "SELECT * FROM " . $this->table_name . " WHERE token = :token AND expired_date > NOW()";
        $this->query($sql);
        $this->bind(':token', $token);
        $results = $this->resultset();
        
        if ($results) {
            return $results;
        } else {
            return ['status' => false];
        }
    } 
}

 /*public function get_share_list($inviter_id) {
        $sql = "SELECT s.*, u.name, u.surname, u.email, u.profile_image
            FROM " . $this->table_name . " s
            JOIN users u ON s.inviter_id = u.id
            WHERE s.inviter_id = :inviter_id";
        
        $this->query($sql);
        $this->bind(':inviter_id', $inviter_id);
        $rows = $this->resultSingle();
        
        // Sonuç varsa
        if ($rows) {
            return [
                'status' => 'success',
                'data' => $rows
            ];
        } else {
            // Sonuç yoksa
            return [
                'status' => 'error',
                'message' => 'inviter_id ile eşleşen paylaşım bulunamadı.'
            ];
        }
    }*/
    
    // Get a share list entry by ID
    /*public function get_share_list($data) {
        $shareList = $this->get($data);
        return (array)$shareList;
    }*/