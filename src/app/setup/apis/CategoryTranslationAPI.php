<?php
	class CategoryTranslationAPI extends Api{

        public CategoryTranslation $CategoryTranslation;
        public $id; // QueryParam id
        public $category_id; // QueryParam category_id
        public $lang_code; // QueryParam lang_code
        public $user_id;
        public Authentication $Authentication;
		function __construct(){
            /* Login gerektiren sınıflarda bu alanın eklenmesi gerekiyor */
            $this->Authentication = $this->model("Authentication");
            if (!$this->Authentication->authenticateJWTToken()) {
                exit;
            }
            // JWT içinden aldığımız kullanıcı id
            $this->user_id = $this->Authentication->userData['id'];

            $this->CategoryTranslation = $this->model('CategoryTranslation');
		}

        // Create category translation
        public function create_category_translation(){
            $return = $this->CategoryTranslation->created_category_translation([
                'category_id' => $this->request->category_id,
                'lang_code' => $this->request->lang_code,
                'name' => $this->request->name,
                'description' => $this->request->description
            ]);

            # Return response
            $this->json($return);
        }

        // Get category translation
        public function get_category_translation(){
            $this->json($this->CategoryTranslation->get_category_translation([
                'category_id' => $this->category_id,
                'lang_code' => $this->lang_code
            ]));
        }

        // Get category translations
        public function get_category_translations(){
            $this->json($this->CategoryTranslation->get_category_translations());
        }

        // Update category translation
        public function update_category_translation(){
            $return = $this->CategoryTranslation->update_category_translation($this->id,[
                'category_id' => $this->request->category_id,
                'lang_code' => $this->request->lang_code,
                'name' => $this->request->name,
                'description' => $this->request->description
            ]);

            # Return response
            $this->json($return);
        }

	}

?>