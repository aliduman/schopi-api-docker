<?php
	/**
	 * Testing AuthAPI
	 */
	class AuthAPI extends API{
        public Authentication $Authentication;
        public $fileManager;
        public UploadAPI $UploadAPI;
        public $id;// Request parameter
        private $userId; // Token user id;
        private $player_id;


		function __construct(){
			$this->Authentication = $this->model("Authentication");

            $uploadDirectory = getcwd() . '/images/';
            $destination = $uploadDirectory;
            $this->fileManager = new File($destination);
		}

        public function guarded() {
             /* Login gerektiren sınıflarda bu alanın eklenmesi gerekiyor */
             $this->Authentication = $this->model("Authentication");
             if (!$this->Authentication->authenticateJWTToken()) {
                 exit;
             }
             // JWT içinden aldığımız kullanıcı id
             $this->userId = $this->Authentication->userData['id'];

             return true;
        }

		/* Login  User Function */
		public function login(){
			
			new Validate([
				[
					# The value to be checked
					"value" => $this->request("email"),
		
					# The key to this item when it is converted as an object
					"key"	=> "email",
		
					# The "key" on object
					"label"	=> "Email",
		
					# Several checks to be performed, separated by pipe "|"
					"checks" => "required|min:3|max:50"
				],
				[
					"value" => $this->request("password"),
					"key"	=> "password",
					"label"	=> "Password",
					"checks" => "required|min:2|max:150"
				]
			], true);
		
			$return = $this->Authentication->login($this->request->email, $this->request->password);

			$this->json($return);
		}

		/* Register User Function */
		public function register(){
            new Validate([
                [
                    # The value to be checked
                    "value" => $this->request("email"),

                    # The key to this item when it is converted as an object
                    "key"	=> "email",

                    # The "key" on object
                    "label"	=> "Email",

                    # Several checks to be performed, separated by pipe "|"
                    "checks" => "required|min:3|max:50"
                ],
                [
                    "value" => $this->request("password"),
                    "key"	=> "password",
                    "label"	=> "Password",
                    "checks" => "required|min:2|max:150"
                ],
                [
                    "value" => $this->request("name"),
                    "key"	=> "name",
                    "label"	=> "Name",
                    "checks" => "required|min:2|max:150"
                ],
                [
                    "value" => $this->request("surname"),
                    "key"	=> "surname",
                    "label"	=> "Surname",
                    "checks" => "required|min:2|max:150"
                ]
            ], true);

            // Email ile zaten bir misafir kullanıcı var mı kontrol et.
            $guest_user = $this->Authentication->get_user_by_email($this->request("email"));
            if ($guest_user && $guest_user['role'] == 'guest') {
                $updated_data = [
                    "email" => $this->request("email"),
                    "password" => password_hash($this->request("password"), PASSWORD_DEFAULT),
                    "name" => $this->request("name"),
                    "surname" => $this->request("surname"),
                    "role" =>'user',
                    "account_type" => 'registered',
                    "profile_image" => 'null',
                    "is_deleted" => ($this->request("is_deleted") === "true") ? 1 : 0,
                ];

                $this->Authentication->update($updated_data, ["id" => $guest_user['id']]);

                $this->json([
                    "status"  => true,
                    "message" => "Guest user upgraded to full user.",
                    "data   "    => $updated_data
                ]);
            } else {
                $return = $this->Authentication->register([
                    "email"	=> $this->request->email,
                    "password"	=> $this->request->password,
                    "name"	=> $this->request->name,
                    "surname"	=> $this->request->surname,
                    "role" => $this->request->role,
                    "account_type" => $this->request->account_type ?? "registered",
                    "profile_image" => $this->request->profile_image ?? "null",
                    "is_deleted" => ($this->request("is_deleted") === "true") ? 1 : 0,
                ], [
                    "unique"	=> ['email']
                ]);
    
                # Return response
                $this->json([
                    "status"	=> $return['success'],
                    "message" 	=> $return['message'],
                    "data"      => $return['data'] ?? []
                ]);
            }
		}

        # Guest User Save
        public function generate_guest_user() {
            // Benzersiz bir e-posta adresi oluştur
            $guest_email = "guest" . rand(1000, 9999) . "@guest.com";
        
            // E-posta ile zaten bir misafir kullanıcı var mı kontrol et
            $guest_user = $this->Authentication->get_user_by_email($guest_email);
        
            if ($guest_user && $guest_user['role'] == 'guest') {
                $this->json([
                    "status"  => false,
                    "message" => "A guest user already exists with that email.",
                ]);
            } else {
                // Misafir kullanıcı kaydet
                $return = $this->Authentication->register([
                    "name"     => "Guest",         // İsim
                    "surname"  => "User" . rand(1, 9999), // Soyadı
                    "email"    => $guest_email,    // E-posta
                    "profile_image" => "null",
                    "role" => "Guest",
                    "account_type" => "Guest user",
                    "password" => "password",   // Şifre
                    "token" => "",
                    "is_deleted" => "0"
                ], [
                    "unique" => ['email'] // E-posta benzersiz olmalı
                ]);
        
                // Kayıt başarılı ise misafir kullanıcının verilerini dön
                if ($return['success']) {
                    // Yeni oluşturulan kullanıcıyı direkt register fonksiyonundan alıyoruz
                    $new_guest_user = $this->Authentication->get_user_by_email($guest_email);
                    $payload = [
                        "id" => $new_guest_user['id'],
                        "name" => $new_guest_user['name'],
                    ];
                    $secret_key = "Sdw1";
                    $JwtController = new Jwt($secret_key);

                    $token = $JwtController->encode($payload);

                    $updated_user = $this->Authentication->update(['token' => $token], ['id' => $new_guest_user['id']]);

                    if ($updated_user) {
                        // Yanıt verisini başarı durumu ve kullanıcı bilgileriyle döndür
                        $this->json([
                            "status" => true,
                            "id"            => $updated_user->id,
                            "name"          => $updated_user->name,
                            "surname"       => $updated_user->surname,
                            "email"         => $updated_user->email,
                            "profile_image" => $updated_user->profile_image,
                            "role"          => $updated_user->role,
                            "account_type"  => $updated_user->account_type,
                            "token"         => $updated_user->token,
                        ]);
                    } else {
                        // Kullanıcı bilgileri alınamazsa hata döndür
                        $this->json([
                            "status"  => false,
                            "message" => "Failed to retrieve newly created guest user.",
                        ]);
                    }
                } else {
                    // Kayıt başarısızsa hata mesajını döndür
                    $this->json([
                        "status"  => false,
                        "message" => $return['message']
                    ]);
                }
            }
        }        

        public function register_guest_user() {
            // Request verilerinden user_id, name, surname, ve email'i çek
            $user_id = $this->request("id");
            $name = $this->request("name");
            $surname = $this->request("surname");
            $email = $this->request("email");
            $password = $this->request("password");
            
            // Gerekli parametrelerle güncelleme fonksiyonunu çağır
            $user = $this->Authentication->update_guest_register_user($user_id, $name, $surname, $email, $password);
            return $this->json($user);
        }
        

		/* Logout User Function */
		public function logout(){
			$this->json($this->Authentication->logout());
		}

		/* Check User Function */
        public function user(){
            // Check user login
            if ($this->guarded()) {
                $user = $this->Authentication->get_user($this->id);
                $this->json($user);
            } else {
                $this->json([
                    "status" => false,
                    "message" => "User not login"
                ]);
            }
        }

		// Get all users
		public function users(){
			// Get all users
			if ($this->guarded()) {
				$users = $this->Authentication->get_allUsers();
				$this->json([
					"status" => true,
					"users" => $users
				]);
			} else {
				$this->json([
					"status" => false,
					"message" => "User not login"
				]);
			}
		}
		

		/* Check User Role Function */
        public function check_user_role()
        {
            // Check user login
            if ($this->guarded()) {
                $user = $this->Authentication->get_user($this->userId);
                $userRole = $user['role'];

                $this->json([
                    "status" => true,
                    "user" => $user,
                    "role" => $userRole
                ]);
            } else {
                $this->json([
                    "status" => false,
                    "message" => "User not login"
                ]);
            }
        }

        // Remember password
        public function forgot_password()
        {
            $email = $this->request->email;
            $user = $this->Authentication->get_user_by_email($email);
            if ($user) {
                $this->json([
                    "status" => true,
                    "message" => "User found",
                    "user" => $user,
                ]);
            } else {
                $this->json([
                    "status" => false,
                    "message" => "User not found"
                ]);
            }
        }

        // Change Password for remember_password token
        public function change_password_by_token()
        {
            $token = $this->request->token;
            $password = $this->request->password;
            $user = $this->Authentication->change_password_by_token($token, $password);
            if ($user) {
                $this->json([
                    "status" => true,
                    "message" => "Password changed",
                    "user" => $user
                ]);
            } else {
                $this->json([
                    "status" => false,
                    "message" => "Password not changed"
                ]);
            }
        }

        // Change Password
        public function change_password()
        {
            // Check user login
            if (!$this->guarded()) {
                $this->json([
                    "status" => false,
                    "message" => "User not login"
                ]);
            }

            $id = $this->request->id;
            $old_password = $this->request->old_password;
            $password = $this->request->password;
            //var_dump($id, $old_password, $password);

            $user = $this->Authentication->change_password($id,$old_password,$password);
            if ($user) {
                $this->json([
                    "status" => true,
                    "message" => "Password changed",
                    "user" => $user
                ]);
            } else {
                $this->json([
                    "status" => false,
                    "message" => "Password not changed"
                ]);
            }
        }

        public function send_verification_code() {
            $email = $this->request->email;
            if (empty($email)) {
                $this->json([
                    "status" => false,
                    "message" => "E-posta adresi boş."
                ]);
                return; // Fonksiyonu sonlandır
            }

            // Kullanıcıyı eposta adresine göre bul
            $userResponse = $this->Authentication->get_user_by_email($email);
            if (!$userResponse) {
                $this->json([
                    "status" => false,
                    "message" => "Kullanıcı bulunamadı."
                ]);
                return;
            }

            $user = $userResponse; // Kullanıcı verilerini al
            //user array in 0 ıncı elemenaını al
            $verification_code = rand(100000, 999999);
            $expires_at = time() + 600; // 600 saniye = 10 dk.

            //Chek verificaiton code
            $isUserVerified = $this->Authentication->get_verification_code($user['id']);
            if (!$isUserVerified) {
                $updatedUserData = $this->Authentication->create_verification_code($user['id'], $verification_code, $expires_at, $user['email']);
            }else{
                // Kod geçerlilik süresini veritabanında sakla
                $updatedUserData = $this->Authentication->update_verification_code($user['id'], $verification_code, $expires_at, $user['email']);
            }

            // Kullanıcıya eposta gönder
            $subject = "Şifre Sıfırlama Doğrulama Kodu";
            $message = "Şifre sıfırlama işlemi için doğrulama kodunuz: $verification_code. Bu kod 10 dakika boyunca geçerlidir.";
            $this->sendMailGun($email, $subject, $message);

            $this->json($updatedUserData);

            /*if ($userResponse) {
                $updatedUserData = null;

            } else {
                $this->json([
                    "status" => false,
                    "message" => "Kullanıcı bulunamadı."
                ]);
            }*/
        }

        public function sendMailGun($to, $email_subject, $email_body)
        {
            /*
            curl -s --user 'api:YOUR_API_KEY' \
            https://api.mailgun.net/v3/YOUR_DOMAIN_NAME/messages \
            -F from='Excited User <postmaster@YOUR_DOMAIN_NAME>' \
            -F to=recipient@example.com \
            -F subject="Hello there!" \
            -F text='This will be the text-only version' \
            --form-string html='<html><body><p>This is the HTML version</p></body></html>'*/

            $apiKey = $_ENV['MAILGUN_API_KEY'] ?? "your-mailgun-api-key";
            $domain = "sandbox4afb7dcf14794c85b652b51aa53a70c4.mailgun.org";

            $ch = curl_init();
            $email_from = "Excited User <nd__86@hotmail.com>";
            curl_setopt($ch, CURLOPT_URL,"https://api.mailgun.net/v3/".$domain."/messages");
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('from' => $email_from, 'to' => $to, 'subject' => $email_subject, 'text' => $email_body, 'html' => $email_body)));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERPWD, 'api:'.$apiKey);
            curl_setopt($ch, CURLOPT_POST, true);

            // Receive server response ...
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $server_output = curl_exec($ch);

            curl_close($ch);

            // Further processing ...
            if ($server_output == "OK") {
                return true;
            } else {
                return false;
            }
        }

        // Validate verification code
        public function validate_verification_code() {
            $email = $this->request->email;
            $code = $this->request->code;

            // E-posta adresi veya doğrulama kodu boşsa hata mesajı döndür
            if (empty($email) || empty($code)) {
                $this->json([
                    "status" => false,
                    "message" => "E-posta adresi veya doğrulama kodu boş."
                ]);
                return;
            }

            // Kullanıcıyı e-posta adresine göre bul
            $userResponse = $this->Authentication->get_user_by_email($email);

            // Kullanıcı bulunamazsa hata mesajı döndür
            if ($userResponse === false) {
                $this->json([
                    "status" => false,
                    "message" => "Kullanıcı bulunamadı."
                ]);
                return;
            }

            $user = $userResponse; // Kullanıcı verisini al
            // Kullanıcının doğrulama kodunu al
            $verificationData = $this->Authentication->get_verification_code($user['id']);

            // Doğrulama kodu kontrolü
            if ($verificationData) {
                if (isset($verificationData->code) && isset($verificationData->expiration)) {
                    // Doğrulama kodunu kontrol et
                    if ($verificationData->code === $code) {
                        // Geçerli süreyi kontrol et
                        $currentTime = time();
                        if ($currentTime < strtotime($verificationData->expiration)) {
                            $this->json([
                                "status" => true,
                                "message" => "Doğrulama kodu geçerli."
                            ]);
                        } else {
                            $this->json([
                                "status" => false,
                                "message" => "Doğrulama kodu süresi dolmuş."
                            ]);
                        }
                    } else {
                        // Geçersiz doğrulama kodu durumunda hata mesajı döndür
                        $this->json([
                            "status" => false,
                            "message" => "Geçersiz doğrulama kodu."
                        ]);
                    }
                } else {
                    $this->json([
                        "status" => false,
                        "message" => "Doğrulama kodu veya süresi bulunamadı."
                    ]);
                }
            } else {
                // Eğer verificationData false dönüyorsa
                $this->json([
                    "status" => false,
                    "message" => "Doğrulama verisi bulunamadı."
                ]);
            }
        }

        // Reset password
        public function reset_password() {
            // Gerekli parametreleri al
            $email = $this->request->email;
            $new_password = $this->request->new_password;

            // Email ve yeni şifre doğrulama
            if (empty($email) || empty($new_password)) {
                $this->json([
                    "status" => false,
                    "message" => "Email veya yeni şifre boş olamaz."
                ]);
                return;
            }

            // Kullanıcıyı email adresine göre bul.
            $userResponse = $this->Authentication->get_user_by_email($email); // Doğru değişken adı
            if ($userResponse === false) {
                $this->json([
                    "status" => false,
                    "message" => "Kullanıcı bulunamadı."
                ]);
                return;
            }

            $user = $userResponse; // Kullanıcı verisini al

            // Şifreyi hash'le
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

            // Kullanıcının şifresini güncelle
            if ($this->Authentication->update_password($user['id'], $new_password)) {
                // Başarılıysa mesajı döndür.
                $this->json([
                    "status" => true,
                    "message" => "Şifre başarıyla güncellendi."
                ]);
            } else {
                $this->json([
                    "status" => false,
                    "message" => "Şifre güncellenirken bir hata oluştu."
                ]);
            }
        }

        // Update user
        public function update_user(){
            $this->guarded();

            $updated_data = $this->Authentication->update_user(
                $this->request->name,
                $this->request->surname,
                $this->request->email,
                $this->request->player_id,
                $this->request->id
            );
            // Kullanıcının şifresini güncelle
            if ($updated_data) {
                // Başarılıysa mesajı döndür.
                $this->json($updated_data);
            }else{
                $this->json([
                    "data" => $updated_data,
                    "status" => false,
                    "message" => "Güncelleme yapılamadı."
                ]);
            }
        }

        public function update_one_signal_player_id() {
            $this->guarded();

            $updated_data = $this->Authentication->update_one_signal_player_id(
                $this->request->player_id,
                $this->request->id
            );

            if ($updated_data) {
                $this->json($updated_data);
            } else {
                $this->json( [
                    "data" => $updated_data,
                    "status" => false,
                    "message" => "Güncelleme yapılamadı."
                ]);
            }
        }

        function generateRandomFileName($fileName)
        {
            $ext = md5(uniqid(rand(), true));
            return $ext;
        }

        function prodOrLocalCheck()
        {
            $host = $_SERVER['HTTP_HOST'];

            // Yerel ortamı tespit etmek için "localhost" stringini kontrol ediyoruz
            if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
                // Yerel ortam için ayarlar
                $filePath = 'http://localhost:8888/schopi-php-nap-api/images/';
            } else {
                // Prodüksiyon ortamı için ayarlar
                $filePath = 'https://schopi-api-cd92fb2da65b.herokuapp.com/images/';
            }

            return $filePath;

        }

        public function user_profile_image_upload(){
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {

                if (empty($_FILES['file']['name'])) {
                    echo json_encode([
                        'message' => 'No file uploaded',
                        'status'  => false
                    ]);
                    return;
                }

                $fileName = $this->generateRandomFileName($_FILES['file']['name']);
                $result = $this->fileManager->handle($_FILES['file'], $fileName, true);

                if ($result === true) {
                    $this->guarded();
                    $user_id = $this->request->id;
                    $profile_image = $this->prodOrLocalCheck() . $this->fileManager->getFileName();

                    //Update user profile image
                    $updated_data = $this->Authentication->user_profile_image_upload($user_id, $profile_image);
                    if ($updated_data) {
                        $this->json([
                            "message" => "Profil resmi güncellendi.",
                            "imageUrl" => $profile_image,
                        ]);
                    } else {
                        $this->json([
                            "status" => false,
                            "message" => "Profil resmi güncellenemedi."
                        ]);
                    }
                } else {
                    echo json_encode([
                        'message' => $result,
                        'status' => false
                    ]);
                }
            } else {
                echo json_encode([
                    'message' => 'Invalid request or no file upload',
                    'status'  => false
                ]);
            }
        }

         /**
         * Kullanıcı hesabını sil
         */
        public function delete_account(){
            $this->guarded(); // Kullanıcı kimlik doğrulaması
    
            $result = $this->Authentication->deleteAccount($this->userId);
            $this->json($result);
        }
    
        /**
         * Kullanıcı hesabını geri yükle
         */
        public function restore_account(){

            // Post paramatresinden email adresini al.
            $email = $_POST['email'];

            // E-posta ile kullanıcıyı al
            $user = $this->Authentication->get_user_by_email($email);

            if (!$user) {
                // Kullanıcı bulunamazsa hata mesajı döndür
                $this->json([
                    "success" => false,
                    "message" => "User not found"
                ]);
                return;
            }

            // Kullanıcı bulunduğunda hesabı geri yükle
            $result = $this->Authentication->restoreAccount($user['id']);
            $this->json($result);
        }

        public function social_auth() {
            // Sosyal verileri almak
            $socialData = [
                "email" => $this->request("email"),
                "provider_id_token" => $this->request("provider_id_token"),
                "provider" => $this->request("provider"),
                "provider_id" => $this->request("provider_id"),
                "name" => $this->request("name"),
                "surname" => $this->request("surname"),
                "role" => $this->request("role"),
                "account_type" => $this->request("account_type"),
                "created_date" => $this->request("created_date"),
                "is_deleted" => $this->request("is_deleted"),
            ];
        
            // Gerekli parametreleri kontrol et
            if (empty($socialData['email']) || empty($socialData['provider_id_token']) || empty($socialData['provider'])) {
                // Hatalı istek
                http_response_code(400); // Bad Request
                $this->json([
                    "success" => false,
                    "message" => "Required fields are missing"
                ]);
                return;
            }
        
            // Giriş veya kayıt işlemini dene
            try {
                // Sosyal oturum açma veya kayıt işlemi
                $response = $this->Authentication->socialLoginOrRegister($socialData);
        
                // Başarısız olduysa logla ve hata yanıtı döndür
                /*if (!$response['success']) {
                    error_log("Login or registration failed. Data: " . print_r($socialData, true));
                    error_log("Error message: " . $response['message']);
                    http_response_code(400); // Bad Request
                } else {
                    // Başarılı olduysa, başarılı yanıt döndür
                    http_response_code(200); // OK
                }*/
        
                // Yanıtı döndür
                $this->json($response);
        
            } catch (Exception $e) {
                // Beklenmeyen hatalar için loglama ve hata yanıtı döndür
                error_log("Exception in social_auth: " . $e->getMessage());
                http_response_code(500); // Internal Server Error
                $this->json([
                    "success" => false,
                    "message" => "An unexpected error occurred: " . $e->getMessage()
                ]);
            }
        }
	}

?>