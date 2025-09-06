<?php
	class ProductAPI extends Api{

        public Product $Product;
        public $id; // QueryParam id
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

            $this->Product = $this->model('Product');
		}

        // Get all list
        public function get_all_product(){
            $this->json($this->Product->get_all_product());
        }

        // Get One product
        public function get_product(){
            // Get user id in jwt token
            $this->json($this->Product->get_product(['id' => $this->id]));
        }

        /**
         * Create product
         */
        public function create_product(){
            $return = $this->Product->created_product([
                'category_id'=> $this->request->category_id,
                'name' => $this->request->name,
                'image' => $this->request->image,
                'price' => $this->request->price,
                'unit'  => $this->request->unit,
                'info' => $this->request->info,
                'owner_id' => $this->user_id ?? 0,
            ]);

            # Return response
            $this->json($return);
        }

        // Update Product
        public function update_product(){
            $return = $this->Product->update_product($this->id,[
                'category_id'=> $this->request->category_id,
                'name' => $this->request->name,
                'image' => $this->request->image,
                'price' => $this->request->price,
                'unit'  => $this->request->unit,
                'info' => $this->request->info,
                'updated_date' => date('Y-m-d H:i:s'),
                'owner_id' => $this->user_id ?? 0,
            ]);

            # Return response
            $this->json($return);
        }

        public function delete_product(){
            $this->json($this->Product->delete_product($this->id));
        }

        //Product with category
        public function get_product_with_category(){
            $this->json($this->Product->get_product_with_category());
        }

        // Products with translated
        public function get_product_with_language()
        {
            $this->json($this->Product->get_product_with_language($this->lang_code));
        }
	}

?>