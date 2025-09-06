<?php
    class FavoritesAPI extends Api {

        public Favorites $Favorites;
        public $id;
        public $product_id;
        public $user_id;
        public Authentication $Authentication;
        private mixed $userId;

        function __construct() {
            /* Login gerektiren sınıflarda bu alanın eklenmesi gerekiyor */
            $this->Authentication = $this->model("Authentication");
            if (!$this->Authentication->authenticateJWTToken()) {
                exit;
            }
            // JWT içinden aldığımız kullanıcı id
            $this->userId = $this->Authentication->userData['id'];

            $this->Favorites = $this->model('Favorites');;
        }

        // Get all categories
        public function get_all_favorites(): void
        {
            $this->json($this->Favorites->get_all_favorites());
        }

        // Get one category
        public function get_favorites(): void
        {
            $this->json($this->Favorites->get_favorites(['id' => $this->id]));
        }

        /**
         * Create Category
         */
        public function create_favorites(): void
        {
            $return = $this->Favorites->created_favorites([
                'user_id' => $this->request->user_id,
                'product_id' => $this->request->product_id,
                'created_date' => $this->request->created_date,
                'updated_date' => $this->request->updated_date,
            ]);

            $this->json([
                "status"  => $return['success'],
                "message" => $return['message'],
                "created_favorites" => $return['created_favorites']
            ]);
        }

        // Update Category
        public function update_favorites() {
            $return = $this->Favorites->update_favorites([
                'name' => $this->request->name,
                'description' => $this->request->description,
                'status' => $this->request->status,
                'image' => $this->request->image,
                'created_date' => $this->request->created_date,
                'updated_date' => $this->request->updated_date
            ],$this->id);

            $this->json([
                "status" => $return['success'],
                "message" => $return['message'],
                "updated_favorites" => $return['updated_favorites']
            ]);
        }

        // Delete Category
        public function delete_favorites() {
            $return = $this->Favorites->delete_favorites($this->id);
            $this->json([
                "status" => $return['success'],
                "message" => $return['message'],
                "deleted_favorites" => $return['deleted_favorites']
            ]);
        }

        public function get_favorite_product_by_user() {
            $return = $this->Favorites->get_favorites_by_user_id($this->user_id);
            $this->json([
                "status" => $return['success'],
                "message" => $return['message'],
                "favorite_products" => $return['favorites_products'],
                "user_id" => $this->user_id
            ]);
        }
    }
?>
