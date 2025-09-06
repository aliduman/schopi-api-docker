<?php

class AuthSession {
        public $is_logged_in = false;
        public $authentication_data = null;
        public $authentication_session = "api_authentication";
        public function __construct()
        {
            if (isset($_SESSION[$this->authentication_session])) {
                $this->authentication_data = $_SESSION[$this->authentication_session];
                $this->is_logged_in = true;
            }
        }
}