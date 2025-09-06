<?php
	class ProductTranslationAPI extends Api{

        public ProductTranslation $ProductTranslation;
        public $id; // QueryParam id
        public $product_id; // QueryParam product_id
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

            $this->ProductTranslation = $this->model('ProductTranslation');
		}

        // Create product translation
        public function create_product_translation(){
            $return = $this->ProductTranslation->created_product_translation([
                'product_id' => $this->request->product_id,
                'lang_code' => $this->request->lang_code,
                'name' => $this->request->name,
                'description' => $this->request->description
            ]);

            # Return response
            $this->json($return);
        }

        // Get product translation
        public function get_product_translation(){
            $this->json($this->ProductTranslation->get_product_translation([
                'product_id' => $this->product_id,
                'lang_code' => $this->lang_code
            ]));
        }

        // Get product translations
        public function get_product_translations(){
            $this->json($this->ProductTranslation->get_product_translations());
        }

        // Update product translation
        public function update_product_translation(){
            $return = $this->ProductTranslation->update_product_translation($this->id,[
                'product_id' => $this->request->product_id,
                'lang_code' => $this->request->lang_code,
                'name' => $this->request->name,
                'description' => $this->request->description
            ]);

            # Return response
            $this->json($return);
        }

	}

?>