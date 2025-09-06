<?php
/**
* List Class
* - middleware
*/

class Lists extends Model
{
    public $userId;
    public $server_id;
    public $user_id;
    public function __construct()
    {
        $this->table_name = "lists";
        $this->table_columns = [
            'id',
            'server_id',
            'core_list_id',
            'user_id',
            'image',
            'name',
            'isTick',
            'theme_name',
            'isSynced',
            'time',
            'created_date',
            'updated_date',
            'cover_image',
            'active',
            'shared',
        ];
    }

    // Get One List
    public function get_list($data){
        $list = $this->get($data);
        return (array)$list;
    }

    // Get All list
    public function get_all_list(){
        return $this->getAll();
    }

    public function get_all_user_list($userId)
    {
        $query = "select * from users_lists join lists on users_lists.list_id = lists.id where users_lists.user_id = :userId";
        $this->query($query);
        $this->bind(":userId", $userId);
        $this->execute();
        return $this->resultSet();
    }

    public function create_user_list($lastInsertId, $userId, $core_list_id){
        $query = "INSERT INTO users_lists (user_id, list_id, created_date, updated_date, core_list_id) values (:user_id, :list_id, :created_date, :updated_date, :core_list_id)";
        $this->query($query);
        $this->bind(":user_id",$userId);
        $this->bind(":list_id", $lastInsertId);
        $this->bind("core_list_id", $core_list_id);
        $this->bind(":created_date", date('Y-m-d H:i:s'));
        $this->bind(":updated_date", date('Y-m-d H:i:s'));

        $this->execute();
    }

    // Create list
    public function created_list(array $data , $userId = null){
        $create_list = $this->insert($data);

        $this->update(['server_id' => $create_list->id], ['id' => $create_list->id]);
        //$this->update($create_list->id, ['server_id' => $create_list->id]);
        
        $core_list_id = isset($data['core_list_id']) ? $data['core_list_id'] : null;
        if($userId) {
            $this->create_user_list($create_list->id, $userId, $core_list_id);
        }
        $updated_list = $this->get(['id' => $create_list->id]);
        # Return list data
        return (array)$updated_list;
    }

    // Update product
    public function update_list($data,$id)
    {
        $updated_list = $this->update($data, ['id' => $id]);

        # Return product data
        return (array)$updated_list;
    }

    public function update_list_with_server_id($data, $server_id) {
        // SQL güncellemesi
        $query = "UPDATE lists 
                  SET name = :name, image = :image, isTick = :isTick, updated_date = :updated_date 
                  WHERE server_id = :server_id";
    
        $this->query($query);
        $this->bind(":name", $data['name']);
        $this->bind(":image", $data['image']);
        $this->bind(":isTick", $data['isTick']);
        $this->bind(":updated_date", $data['updated_date']);
        $this->bind(":server_id", $server_id);
        $this->execute();
    
        $response = [
            "status" => true,
            "data" => [
                "name" => $data["name"],
                "image" => $data["image"],
                "isTick" => $data["isTick"],
                "updated_date" => $data["updated_date"],
                "server_id" => $server_id
            ]
        ];
    
        // Yanıtı dön
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
    }

    // Delete list
    /*public function delete_list($server_id) {
        $deleted_list = $this->delete($server_id);
        $query = "DELETE FROM lists WHERE server_id = :server_id";
        $this->query($query);
        $this->bind(":server_id", $server_id);
        $this->execute();
    
        return (array)$deleted_list;
    }*/

    public function delete_list($server_id) {
        // 1. Users_lists tablosundaki ilişkili kayıtları sil
        $query = "DELETE FROM users_lists WHERE list_id = (
        SELECT id FROM lists WHERE server_id = :server_id
        )";
        $this->query($query);
        $this->bind(":server_id", $server_id);
        $this->execute();

        // 2. Listeyi sil
        $deleted_list = $this->delete($server_id);
        $query = "DELETE FROM lists WHERE server_id = :server_id";
        $this->query($query);
        $this->bind(":server_id", $server_id);
        $this->execute();

        return (array)$deleted_list;
    }

    public function delete_user_list($server_id, $user_id) {
        $deleted_list = $this->delete($server_id);
    
        $query = "DELETE lists, users_lists 
                FROM lists
                JOIN users_lists ON lists.server_id = users_lists.list_id
                WHERE lists.server_id = :server_id 
                AND users_lists.user_id = :user_id";
        
        $this->query($query);
        $this->bind(":server_id", $server_id);
        $this->bind(":user_id", $user_id);
        $this->execute();
        
        return (array)$deleted_list;
    }

    public function update_is_packet($list_id, $product_id, $is_packet){
        $query = "update list_products set is_packet = :is_packet where product_id = :product_id and list_id = :list_id";
        $this->query($query);
        $this->bind(":list_id", $list_id);
        $this->bind(":product_id", $product_id);
        $this->bind(":is_packet", $is_packet);
        $this->execute();
        return [
            "status" => true,
            "message" => "Product updated in list",
            "data" => (array)$this->rowCount()
        ];
    }

    public function get_shared_users($list_id)
    {
        $query = "select * from users_lists as ul join users as u on ul.user_id = u.id where ul.list_id = :list_id";
        $this->query($query);
        $this->bind(":list_id", $list_id);
        $shared_users = $this->resultset();
        return (array)$shared_users;
    }

    public function get_server_id($user_id) {
        try {
            // users_lists tablosu üzerinden join yaparak doğru sorguyu çalıştır
            $query = "SELECT l.server_id 
                    FROM lists l 
                    JOIN users_lists ul ON l.id = ul.list_id 
                    WHERE ul.user_id = :user_id
                    ORDER BY l.server_id DESC 
                    LIMIT 1";

            $this->query($query);
            $this->bind(":user_id", $user_id);
            $result = $this->resultSingle();

            error_log("User ID: " . $user_id);
            error_log("Query result: " . print_r($result, true));

            if ($result && isset($result->server_id)) {
                return $result->server_id;
            }

            return null;

        } catch (Exception $e) {
            error_log("Database error: " . $e->getMessage());
            return null;
        }
    }
}
?>