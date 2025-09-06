<?php
class Notifications extends Model {
    public function __construct() {
        $this->table_name = 'notifications';
        $this->table_columns = [
            'id',
            'title',
            'sub_title',
            'description',
            'image',
            'list_id',
            'user_id',
            'type',
            'core_list_id',
            'is_read',
            'scheduled_date',
            'created_date',
            'updated_date'
        ];
    }

    // Yeni sistem bildirimi, hatırlatırıc yada liste daveti gibi bildirim oluştur.
    public function create_notification($data) {
        $createdNotification = $this->insert($data);
        return (array)$createdNotification;
    }

    // Tüm bildirimleri çek
    public function get_notification_by_user($user_id) {
        $sql = "SELECT * FROM " . $this->table_name . " WHERE user_id = :user_id ORDER BY created_date DESC";
        $this->query($sql);
        $this->bind(':user_id', $user_id);
        return $this->resultSet();
    }

    // Bildirime tıklandığında is_read = true yap (Okundu - Okunmadı) bilgisi.
    public function mark_as_read($id) {
        $sql = "UPDATE " . $this->table_name . " SET is_read = 1, updated_date = NOW() WHERE id = :id";
        $this->query($sql);
        $this->bind(':id', $id);
        return $this->execute();
    }

    // Kullanıcının kaç tane okunmamış bildirimi var? (Badge için)
    public function get_unread_count($user_id) {
        $sql = "SELECT COUNT(*) as count FROM " . $this->table_name . " WHERE user_id = :user_id AND is_read = 0";
        $this->query($sql);
        $this->bind(':user_id', $user_id);
        $result = $this->resultSingle();
        return $result->count;
    }

    // Bildirimleri sil.
    public function delete_by_id($id) {
        $delete = $this->delete($id);
        return  (array)$delete;
    } 

     // Belirli bir share_list kaydı için bildirim var mı kontrol et
    // Notifications.php modelinde eklenecek
    public function get_notification_by_list_and_user($list_id, $user_id, $type) {
        $sql = "SELECT * FROM notifications 
                WHERE list_id = :list_id 
                AND user_id = :user_id 
                AND type = :type 
                ORDER BY created_date DESC
                LIMIT 1";
        
        $this->query($sql);
        $this->bind(':list_id', $list_id);
        $this->bind(':user_id', $user_id);
        $this->bind(':type', $type);
        
        $result = $this->resultSingle();
        
        // Debug için
        error_log("Notification check - List ID: $list_id, User ID: $user_id, Type: $type");
        error_log("Existing notification: " . ($result ? 'BULUNDU' : 'BULUNAMADI'));
        
        return $result;
    }
    /*public function get_notification_by_list_and_user($list_id, $user_id, $type = 'list_invite') {
        $sql = "SELECT * FROM " . $this->table_name . " WHERE list_id = :list_id AND user_id = :user_id AND type = :type";
        $this->query($sql);
        $this->bind(':list_id', $list_id);
        $this->bind(':user_id', $user_id);
        $this->bind(':type', $type);
        return $this->resultSingle();
    }*/
}