<?php

class NotificationsAPI extends API {
    public Notifications $Notifications;
    public $id;
    public $user_id;
    public $list_id;

    public Authentication $Authentication;

    function __construct(){
         /* Login gerektiren sınıflarda bu alanın eklenmesi gerekiyor */
        $this->Authentication = $this->model("Authentication");
        if (!$this->Authentication->authenticateJWTToken()) {
            exit;
        }
        // JWT içinden aldığımız kullanıcı id
        $this->user_id = $this->Authentication->userData['id'];

        // Notification
		$this->Notifications = $this->model("Notifications");
	}

    public function get_all_notifications() {
        $return = $this->Notifications->get_notification_by_user($this->user_id);
        $this->json($return);
    }

     public function mark_as_read() {
        $id = $this->request->id ?? null;
        if (!$id) {
            $this->json(['success' => false, 'message' => 'ID gerekli']);
            return;
        }

        $result = $this->Notifications->mark_as_read($id);
        $this->json(['success' => $result]);
    }

    public function create_notification() {
        // Gerekli alanları kontrol et
        $required_fields = ['title', 'description', 'type'];
        foreach ($required_fields as $field) {
            if (!isset($this->request->$field) || empty(trim($this->request->$field))) {
                $this->json(['success' => false, 'message' => $field . ' alanı gerekli']);
                return;
            }
        }


        $data = [
            'title' => $this->request->title ?? '',
            'sub_title' => $this->request->sub_title ?? '',
            'description' => $this->request->description ?? '',
            'image' => $this->request->image ?? '',
            'list_id' => $this->request->list_id,
            'user_id' => $this->user_id, // JWT'den alınan user_id
            'type' => $this->request->type ?? '',
            'core_list_id' => $this->request->core_list_id ?? '',
            'is_read' => $this->request->is_read ?? false,
            'scheduled_date' => $this->request->scheduled_date ?? '',
            'created_date' => $this->request->created_date ?? date('Y-m-d H:i:s'),
            'updated_date' => $this->request->updated_date ?? date('Y-m-d H:i:s'),
        ];

        // Bildirim tipini kontrol et
        $allowed_types = ['list_invite', 'reminder', 'system'];
        if (!in_array($data['type'], $allowed_types)) {
            $this->json(array(false));
            return;
        }

        $result = $this->Notifications->create_notification($data);
        
        if ($result) {
            $this->json($result);
        } else {
            $this->json(array(false));
        }
    }

    public function get_unread_count() {
        $count = $this->Notifications->get_unread_count($this->user_id);
        $this->json(['count' => $count]);
    }

    public function delete_notification() {
        $result = $this->Notifications->delete_by_id($this->id);
        $this->json((array)$result);
    }
}