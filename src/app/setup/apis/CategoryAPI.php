<?php
    class CategoryAPI extends Api {

        public Category $Category;
        public $id;
        public $request;
        public $lang_code;
        public $userId;
        public Authentication $Authentication;

        function __construct() {
            /* Login gerektiren sınıflarda bu alanın eklenmesi gerekiyor */
            $this->Authentication = $this->model("Authentication");
            if (!$this->Authentication->authenticateJWTToken()) {
                exit;
            }
            // JWT içinden aldığımız kullanıcı id
            $this->userId = $this->Authentication->userData['id'];

            $this->Category = $this->model('Category');
        }

        // Get all categories
        public function get_all_category() {
            $this->json($this->Category->get_all_category());
        }

        // Get one category
        public function get_category() {
            $this->json($this->Category->get_category(['id' => $this->id]));
        }

        /**
         * Create Category
         */
        public function create_category() {
            $return = $this->Category->created_category([
                'name' => $this->request->name,
                'info' => $this->request->info,
                'image' => $this->request->image,
                'status' => true,
                'created_date' => date('Y-m-d H:i:s'),
                'updated_date' => date('Y-m-d H:i:s')
            ]);

            $this->json($return);
        }

        // Update Category
        public function update_category() {
            $return = $this->Category->update_category([
                'name' => $this->request->name,
                'info' => $this->request->info,
                'status' => true,
                'image' => $this->request->image,
                'updated_date' => date('Y-m-d H:i:s')
            ],$this->id);

            $this->json($return);
        }

        // Delete Category
        public function delete_category() {
            $return = $this->Category->delete_category($this->id);
            $this->json($return);
        }

        public function get_category_with_language(){
            $this->json($this->Category->get_category_with_language($this->lang_code));
        }
    }
?>