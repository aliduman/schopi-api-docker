<?php
	class UnitsAPI extends Api{

        public Units $Units;
        public $id; // QueryParam id
        public $unit_id; // QueryParam product_id
        public $country_code; // QueryParam lang_code
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

            $this->Units = $this->model('Units');
		}

        // Get unit by country code
        public function get_units_by_country_code(){
            $this->json($this->Units->get_units_by_country_code($this->country_code));
        }

	}

?>