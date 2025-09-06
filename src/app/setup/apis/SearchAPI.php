<?php
	class SearchAPI extends Api{

        public $user_id;
        public $product_id;
        public Search $Search;
        public Authentication $Authentication;

        function __construct(){
            /* Login gerektiren sınıflarda bu alanın eklenmesi gerekiyor */
            $this->Authentication = $this->model("Authentication");
            if (!$this->Authentication->authenticateJWTToken()) {
                exit;
            }
            // JWT içinden aldığımız kullanıcı id
            $this->userId = $this->Authentication->userData['id'];

            $this->Search = $this->model('Search');
		}

        // Save search history product
        public function save_search_history(){
            $data = [
                'user_id' => $this->request->user_id,
                'product_id' => $this->request->product_id
            ];
            $this->json($this->Search->save_search_history($data));
        }

        // Remove all search history by user
        public function remove_all_search_history_by_user(){
            $this->json($this->Search->remove_all_search_history_by_user([
                'user_id' => $this->user_id
            ]));
        }

        // Remove search history by user
        public function remove_search_history_by_user(){
            $this->json($this->Search->remove_search_history_by_user([
                'user_id' => $this->user_id,
                'product_id' => $this->product_id
            ]));
        }

	}

?>