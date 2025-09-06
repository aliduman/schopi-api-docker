<?php
	class LanguageAPI extends Api{

        public Language $Language;
        public $id; // QueryParam id
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

            $this->Language = $this->model('Language');
		}

        // Get all list
        public function get_all_language(){
            $this->json($this->Language->get_all_language());
        }

        // Get One product
        public function get_language(){
            // Get user id in jwt token
            $this->json($this->Language->get_language(['id' => $this->id]));
        }

        /**
         * Create product
         */
        public function create_language(){
            $lang_data = [
                'code'=> $this->request->code,
                'name' => $this->request->name,
                'native_name' => $this->request->native_name,
                'is_active' => $this->request->is_active
            ];
            $return = $this->Language->created_language($lang_data);

            # Return response
            $this->json($return);
        }

        // Update Language
        public function update_language(){
            $return = $this->Language->update_language($this->id,[
                'code'=> $this->request->code,
                'name' => $this->request->name,
                'native_name' => $this->request->native_name,
                'is_active' => $this->request->is_active
            ]);

            # Return response
            $this->json($return);
        }

        public function delete_language(){
            $this->json($this->Language->delete_language($this->id));
        }
	}

?>