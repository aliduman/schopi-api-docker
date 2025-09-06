<?php

	/**
	 * Testing List API
	 */
	class ListsAPI extends API{

        public Lists $Lists;
        public $id; // QueryParam id
        public $user_id;
        public $product_id;
        public $server_id;

        public Authentication $Authentication;
		
		function __construct(){
            /* Login gerektiren sınıflarda bu alanın eklenmesi gerekiyor */
            $this->Authentication = $this->model("Authentication");
            if (!$this->Authentication->authenticateJWTToken()) {
                exit;
            }
            // JWT içinden aldığımız kullanıcı id
            $this->user_id = $this->Authentication->userData['id'];

            // Lists ver
			$this->Lists = $this->model("Lists");
		}

        // Get all list
        public function get_all_list(){
            $return = $this->Lists->get_all_list();
            $this->json($return);
        }

        public function get_all_user_list(){
            $return = $this->Lists->get_all_user_list($this->user_id);
            $this->json($return);
        }

        // Get list by id
        public function get_list(){
            $this->json($this->Lists->get_list(['id' => $this->id]));
        }

        // Create list
        public function created_list(){
            $return = $this->Lists->created_list([
                'server_id' => 0,
                'core_list_id' => $this->request->core_list_id ?? '',
                'user_id' => $this->user_id,
                'image' => $this->request->image ?? '',
                'name' => $this->request->name ?? '',
                'isTick' => $this->request->isTick ?? 0,
                'theme_name' =>$this->request->theme_name ?? '',
                'isSynced' => $this->request->isSynced ?? 0,
                'time' => $this->request->created_date ?? '',
                'cover_image' => $this->request->cover_image ?? '',
                'created_date' => $this->request->created_date ?? '',
                'updated_date' => $this->request->updated_date ?? '',
                'active' => $this->request->active ?? 1,
                'shared' => $this->request->shared ?? 0
            ], $this->user_id);

            # Return response
            $this->json($return);
        }

        // Update list
        public function update_list(){
            $return = $this->Lists->update_list([
                'name' => $this->request->name,
                'updated_date' => date('Y-m-d H:i:s'),
                'image' => $this->request->image,
                'isTick' => $this->request->isTick,
                'isSynced' => $this->request->isSynced,
                'server_id' => $this->request->server_id
            ],$this->id);

            $this->json($return);
        }

        public function update_list_with_server_id() {
            // Güncelleme işlemini yap
            $return = $this->Lists->update_list_with_server_id([
                'name' => $this->request->name,
                'image' => $this->request->image,
                'isTick' => $this->request->isTick,
                'updated_date' => $this->request->updated_date,
                'server_id' => $this->request->server_id
            ], $this->server_id);
        
            if ($return) {
                $this->json([
                    'status' => true,
                    $return
                    
                ]);
            }
            return $return;
        }

        public function delete_list() {
            $this->json($this->Lists->delete_list($this->server_id));
        }

        // is_packet update function
        public function update_is_packet(){
            $return = $this->Lists->update_is_packet($this->request->list_id, $this->request->product_id, $this->request->is_packet);
            $this->json($return);
        }

        // Get shared users
        public function get_shared_users(){
            $return = $this->Lists->get_shared_users($this->id);
            $this->json($return);
        }

        public function get_server_id() {
            // JWT'den gelen user_id'yi kullan, URL parametresinden değil
            $serverId = $this->Lists->get_server_id($this->user_id);

            $this->json([
                'user_id' => $this->user_id, // Debug için
                'server_id' => $serverId
            ]);
        }
	}

?>