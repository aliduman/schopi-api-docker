<?php

/**
 * Authentication class
 * - middleware
 */
class Authentication extends Model
{
    public $is_logged_in = false;
    public $authentication_data = null;
    public $authentication_handle = "email";
    public $authentication_password = "password";
    public $token = '';
    public $user_id = null;
    public $userData = null;

    public $table_name = 'users';

    /** Check middleware information */
    public function __construct()
    {
        $this->table_columns = 
        [
            'id',
            'name',
            'surname',
            'email',
            'password',
            'real_password',
            'profile_image',
            'reset_password',
            'reset_password_expires',
            'role',
            'account_type',
            'token',
            'player_id',
            'provider',
            'provider_id',
            'provider_id_token',
            'is_deleted',
            'created_date',
            'updated_date'
        ];
    }

    public function authenticateJWTToken(): bool
    {
        $JwtCtrl = new Jwt("Sdw1");

        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
        if ($authHeader === null) {
            // Hata mesajı döndürün veya hata kaydı yapın
            echo json_encode(['message' => 'Authorization header missing']);
            exit;
        }


        if (!preg_match("/^Bearer\s+(.*)$/", $_SERVER["HTTP_AUTHORIZATION"], $matches)) {
            http_response_code(400);
            echo json_encode(["message" => "incomplete authorization header"]);
            return false;
        }

        try {
            $this->userData = $JwtCtrl->decode($matches[1]);
        } catch (InvalidSignatureException) {

            http_response_code(401);
            echo json_encode(["message" => "invalid signature"]);
            return false;
        } catch (Exception $e) {

            http_response_code(400);
            echo json_encode(["message" => $e->getMessage()]);
            return false;
        }

        return true;
    }

    /**
     * Attempt login
     * @param string $handle
     * @param string $password
     */
    public function login($handle, $password)
    {
        # Find row using `handle`
        $sql = "SELECT * FROM `$this->table_name` WHERE `$this->authentication_handle` = :handle AND `is_deleted` = 0" ;
        $this->query($sql);
        $this->bind(":handle", $handle);
        $row = $this->resultSingle();
        /*$this->user_id = $row->id;*/

        # Check existence
        if (!$row) {
            return [
                "success" => false,
                "label" => $this->authentication_handle,
                "message" => $this->authentication_handle . " not found or deleted"
            ];
        }

        # Check password validity
        if ($row->{$this->authentication_password} && password_verify($password, $row->{$this->authentication_password})) {

            # Authenticate user
            unset($row->{$this->authentication_password});

            # Save additional information
            $user_data = $row;
            $user_data->logged_in = time();

            $payload = [
                "id" => $user_data->id,
                "name" => $user_data->name,
            ];
            $secret_key = "Sdw1";
            $JwtController = new Jwt($secret_key);

            $token =$JwtController->encode($payload);

            $data = $this->update(['token' => $token], ['id' => $row->id]);

            # Return authenticated user
            return [
                "id" => $data->id,
                "name" => $data->name,
                "surname" => $data->surname,
                "email" => $data->email,
                "profile_image" => $data->profile_image,
                "role" => $data->role,
                "account_type" => $data->account_type,
                "token" => $token,
                "success" => true,
                "ip" => $_SERVER['REMOTE_ADDR'],
                "user_agent" => $_SERVER['HTTP_USER_AGENT'],
                "login_time" => date('Y-m-d H:i:s')
            ];
        } else {
            return [
                "success" => false,
                "label" => $this->authentication_password,
                "message" => "Password is incorrect"
            ];
        }
    }

    /** Unauthenticate user, remove session */
    public function logout()
    {
        if ($this->is_logged_in) {
            $this->is_logged_in = false;
            return ["status" => true, "message" => "Successfully logged out"];
        } else {
            return ["status" => false, "message" => "Unauthorized access"];
        }
    }

    /**
     * Attempt Register
     * @param array $data
     *        $ data = [
     *            'column' => 'VALUE',
     *            ...
     *        ]
     * @param array $options Optional.
     *        $options = [
     *            'unique' => ['COLUMN_NAME', 'COLUMN_NAME', ...]
     *        ]
     */

    // Register user
    /**
     * @param array $data
     * @param array $options
     * @return array
     */
    public function register(array $data, array $options): array
    {

        # Check all options of unique column
        foreach ($options['unique'] as $option) {
            $this->query(" SELECT * FROM `$this->table_name` WHERE `$option` = :data ");
            $this->bind(":data", $data[$option]);
            $row = $this->resultSingle();
            if ($row) {
                return [
                    "success" => false,
                    "label" => $option,
                    "message" => "$option already registered"
                ];
            }
        }

        # Hash password
        $data[$this->authentication_password] = password_hash($data[$this->authentication_password], PASSWORD_DEFAULT);

        # Set create_date
        $data['created_date'] = date('Y-m-d H:i:s');
        error_log("Register Data: " . print_r($data, true));

        # All option passed, time to register
        $registered = $this->insert($data);

        # Prepare payload for JWT
        $payload = [
            "id"   => $registered->id,
            "name" => $data['name'],
            "exp"  => time() + (60 * 60)
        ];

        //$secret_key = "Sdw1";
        $JwtCtrl = new Jwt("Sdw1");
        $token = $JwtCtrl->encode($payload);

        // Save token to database
        $this->update(['token' => $token], ['id' => $registered->id]);

        # Return registered if success
        return [
            "success" => true,
            "data" => [
                "id" => $registered->id,
                "name" => $data['name'],
                "surname" => $data['surname'],
                "email" => $data['email'],
                "profile_image" => $data['profile_image'], // Eğer varsa
                "role" => $data['role'],
                "account_type" => $data['account_type'],
                "token" => $token,
                "is_deleted" => $data['is_deleted']
            ],
        "message" => "Registered and logged in successfully."
            /*"success" => true,
            "registered" => (array)$registered,
            "token" => $this->token,
            "message" => "Registered and logged in successfully."*/
        ];
    }

    /**
     * Authenticate user
     * @param array $user_data
     */
    public function auth(array $user_data)
    {

        # Save additional information
        $user_data->logged_in = time();
    }

    // Get one user with id
    public function get_user($id)
    {
        # Get user data
        $this->query(" SELECT * FROM `$this->table_name` WHERE id = :id ");
        $this->bind(":id", $id);
        $row = $this->resultSingle();
        if ($row) {
            return [
                "id" => $row->id,
                "provider_id" => $row->provider_id,
                "name" => $row->name,
                "surname" => $row->surname,
                "email" => $row->email,
                "profile_image" => $row->profile_image,
                "role" => $row->role,
                "account_type" => $row->account_type,
                "token" => $row->token,
                "player_id" => $row->player_id
            ];
        } else {
            return [
                "success" => false,
                "message" => "User not found"
            ];
        }
    }

    public function get_player_id($id)
    {
        $this->query("SELECT id, player_id FROM `$this->table_name` WHERE id = :id");
        $this->bind(":id", $id);
        $row = $this->resultSingle();

        if ($row) {
            return [
                "id" => $row->id,
                "player_id" => $row->player_id
            ];
        } else {
            return [
                "success" => false,
                "message" => "User not found"
            ];
        }
    }


    public function get_user_by_email($email) 
    {
        $this->query("SELECT * FROM `$this->table_name` WHERE email = :email");
        $this->bind(":email", $email);
        $row = $this->resultSingle();

        // Eğer kullanıcı bulunamazsa false döndür
        if (!$row) {
            return false;
        }
        return (array)$row;
    }

    // Get all users
    public function get_allUsers()
    {
        $sql = "SELECT * FROM `$this->table_name`";
        $this->query($sql);
        $rows = $this->resultSet();
        if ($rows) {
            return [
                "success" => true,
                "data" => $rows
            ];
        } else {
            return [
                "success" => false,
                "message" => "No record of any user."
            ];
        }
    }

    // Change Password for remember_password token
    public function change_password_by_token($token, $password)
    {
        $this->query(" SELECT * FROM `$this->table_name` WHERE reset_password = :token ");
        $this->bind(":token", $token);
        $row = $this->resultSingle();
        if ($row) {
            $new_password = password_hash($password, PASSWORD_DEFAULT);
            $this->update(['password' => $new_password, 'reset_password' => ''], ['id' => $row->id]);
            return [
                "success" => true,
                "message" => "Password changed"
            ];
        } else {
            return [
                "success" => false,
                "message" => "User not found"
            ];
        }
    }

    // Change Password
    public function change_password($id, $old_password, $password)
    {
        // Kullanıcıyı ID ile sorgula
        $this->query("SELECT * FROM `$this->table_name` WHERE id = :id");
        $this->bind(":id", $id);
        $row = $this->resultSingle();
    
        // Kullanıcı bulunamadıysa hata döndür
        if (!$row) {
            return [
                "success" => false,
                "message" => "User not found"
            ];
        }
    
        // Eski şifreyi doğrula
        if (!password_verify($old_password, $row->password)) {
            return [
                "success" => false,
                "message" => "Old password is incorrect"
            ];
        }
    
        // Yeni şifreyi hashle ve güncelle
        $new_password = password_hash($password, PASSWORD_DEFAULT);
        $this->update(['password' => $new_password], ['id' => $id]);
    
        return [
            "success" => true,
            "message" => "Password changed"
        ];
    }

    // Get user by token
    public function get_user_by_token($token)
    {
        $this->query(" SELECT * FROM `$this->table_name` WHERE token = :token ");
        $this->bind(":token", $token);
        $row = $this->resultSingle();
        if ($row) {
            return [
                "success" => true,
                "message" => "User found",
                "data" => $row
            ];
        } else {
            return [
                "success" => false,
                "message" => "User not found"
            ];
        }
    }

    public function update_verification_code($user_id, $code, $expiration, $email)
    {
        try {
            $this->query("UPDATE verification_codes SET code = :code, expiration = :expiration, email = :email WHERE user_id = :user_id");
            $this->bind(":code", $code);
            $this->bind(":expiration", date('Y-m-d H:i:s', $expiration));
            $this->bind(":user_id", $user_id);
            $this->bind(":email", $email);
            $this->execute();
            
            return (array)$this->get_verification_code($user_id);
        } catch (PDOException $e) {
            // Hata durumunda hata mesajını döndür
            return [
                "success" => false,
                "message" => $e->getMessage()
            ];
        }
    }

    // Create verification code
    public function create_verification_code($user_id, $code, $expiration, $email)
    {
        try {
            $this->query("INSERT INTO verification_codes (user_id, email, code, expiration) VALUES (:user_id, :email, :code, :expiration)");
            $this->bind(":user_id", $user_id);
            $this->bind(":code", $code);
            $this->bind(":expiration", date('Y-m-d H:i:s', $expiration));
            $this->bind(":email", $email);
            $this->execute();
            $row = $this->get_verification_code($user_id);

            return (array)$row;
        } catch (PDOException $e) {
            // Hata durumunda hata mesajını döndür
            return ["error" => $e->getMessage()];
        }
    }

    // Get verification code
    public function get_verification_code($user_id)
    {
        try {
            $this->query("SELECT * FROM verification_codes WHERE user_id = :user_id");
            $this->bind(":user_id", $user_id);
            $row = $this->resultSingle();

            if ($row) {
                $row->message = "Verification code found";
                return $row;
            } else {
                return false;
            }
        } catch (PDOException $e) {
            // Hata durumunda hata mesajını döndür
            return [
                "success" => false,
                "message" => $e->getMessage()
            ];
        }
    }

    // Password update
    public function update_password($user_id, $new_password)
    {
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
        try {
            $this->query("UPDATE $this->table_name SET password = :password WHERE id = :id");
            $this->bind(":password", $hashed_password);
            $this->bind(":id", $user_id);
            return $this->execute();
        } catch (PDOException $e) {
            // Hata durumunu burada loglayabilir ya da yönetebilirsiniz
            return false;
        }
    }

    // Guest user update and register to data
    public function update_guest_register_user($user_id, $name, $surname, $email, $password) {
        try {
            $this->query("UPDATE users SET name = :name, surname = :surname, email = :email, password = :password, role = 'user', account_type = 'registered' WHERE id = :id");
            $this->bind(":name", $name);
            $this->bind(":surname", $surname);
            $this->bind(":email", $email);
            $this->bind(":password", password_hash($password, PASSWORD_DEFAULT));
            $this->bind(":id", $user_id);
            $this->execute();

            $user_data = (array)$this->get_user($user_id);
            return $user_data;
        } catch (PDOException $e) {
            return false;
        }
    }

    // Update user
    public function update_user($name, $surname, $email, $player_id, $id){
        try{
            $this->query("UPDATE users SET name = :name, surname = :surname, email = :email, player_id = :player_id WHERE id = :id");
            $this->bind(":name", $name);
            $this->bind(":surname", $surname);
            $this->bind(":email", $email);
            $this->bind(":player_id", $player_id);
            $this->bind(":id",$id);
            $this->execute();

            $user_data = (array)$this->get_user($id);
            return $user_data;

        }catch (PDOException $e) {
            return false;
        }
    }

    public function update_one_signal_player_id($player_id, $id) {
        try {
            $this->query("UPDATE users SET player_id = :player_id WHERE id = :id");
            $this->bind("player_id", $player_id);
            $this->bind("id", $id);
            $this->execute();
    
            // get_player_id çağrılıyor
            $player_id_value = $this->get_player_id($id);
    
            return [
                "status" => true,
                "id" => $player_id_value['id'],
                "player_id" => $player_id_value['player_id']
            ];
        } catch (PDOException $e) {
            return [
                "status" => false,
                "message" => $e->getMessage()
            ];
        }
    }


    public function user_profile_image_upload($user_id, $image_path)
    {
        try {
            $this->query("UPDATE users SET profile_image = :profile_image WHERE id = :id");
            $this->bind(":profile_image", $image_path);
            $this->bind(":id", $user_id);
            return $this->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    // Soft Delete account
    public function deleteAccount($user_id) {
        // Kullanıcıyı kontrol et
        $sql = "SELECT * FROM `$this->table_name` WHERE `id` = :id AND `is_deleted` = 0";
        $this->query($sql);
        $this->bind(":id", $user_id); // id'yi doğru bağla
        $user = $this->resultSingle();
    
        if (!$user) {
            return [
                "success" => false,
                "message" => "User not found or already deleted."
            ];
        }
    
        // Soft delete işlemi
        $deleted_at = date('Y-m-d H:i:s');
    
        // Güncelleme sorgusu
        $sql = "UPDATE `$this->table_name` 
                SET `is_deleted` = :is_deleted, `updated_date` = :updated_date 
                WHERE `id` = :id";
    
        $this->query($sql);
        $this->bind(":is_deleted", 1); // Soft delete için 1
        $this->bind(":updated_date", $deleted_at); // updated_date'yi doğru bağla
        $this->bind(":id", $user_id); // id'yi doğru bağla
        $this->execute();
    
        return [
            "success" => true,
            "message" => "Account deleted. You can restore it within 30 days.",
            "updated_date" => $deleted_at
        ];
    }    
    
    // Restore account. You can get your account back within 30 days.
    public function restoreAccount($user_id) {
        // Kullanıcıyı kontrol et
        $sql = "SELECT * FROM `$this->table_name` WHERE `id` = :id AND `is_deleted` = 1";
        $this->query($sql);
        $this->bind(":id", $user_id);
        $user = $this->resultSingle();
    
        if (!$user) {
            return [
                "success" => false,
                "message" => "User not found or cannot be restored"
            ];
        }
    
        // Silme tarihinden itibaren 30 gün kontrolü
        if (empty($user->updated_date)) {
            return [
                "success" => false,
                "message" => "Invalid updated date."
            ];
        }
    
        $deleted_at = new DateTime($user->updated_date); // updated_date kullanılacak
        $now = new DateTime(); // Burada $now değişkenini tanımlıyoruz
        $diff = $now->diff($deleted_at); // Fark hesaplanıyor
        if ($diff->days > 30) {
            return [
                "success" => false,
                "message" => "Account can't be restored after 30 days."
            ];
        }
    
        // Kullanıcıyı geri yükle
        $updateData = [
            'is_deleted' => false,
            'updated_date' => null
        ];
        $this->update($updateData, ['id' => $user_id]); // Doğru parametreyi kullanıyoruz
    
        return [
            "success" => true,
            "message" => "Account successfully restored."
        ];
    } 
    
    // Social register or login func
    public function socialLoginOrRegister($socialData) {
        // Gerekli tüm verilerin sağlandığından emin olun
        if (!isset($socialData['email'], $socialData['provider'], $socialData['provider_id_token'])) {
            return [
                "success" => false,
                "message" => "Missing required parameters"
            ];
        }
    
        $email = $socialData['email'];
        $name = $socialData['name'];
        $surname = $socialData['surname'];
        $provider = $socialData['provider'];
        $provider_id = $socialData['provider_id'];
        $idToken = $socialData['provider_id_token'];
    
        // Kullanıcı zaten var mı kontrol et
        $sql = "SELECT * FROM users WHERE email = :email";
        $this->query($sql);
        $this->bind(":email", $email);
        $user = $this->resultSingle();
    
        if ($user) {
            // Kullanıcı varsa, giriş işlemi
            $payload = [
                "id"   => $user->id,
                "email" => $user->email,
                "name" => $user->name,
                "surname" => $user->surname,
            ];
            $JwtCtrl = new Jwt("Sdw1");
            $token = $JwtCtrl->encode($payload);
    
            // Token güncelle
            $this->update(['token' => $token], ['id' => $user->id]);
            return [
                "id"    => $user->id,
                "email" => $user->email,
                "name"  => $user->name,
                "surname" => $user->surname,
                "token" => $token,
                "provider" => $provider,
                "provider_id" => $provider_id,
                "provider_id_token" => $idToken,
                "created_date" => date("Y-m-d H:i:s"),
                "is_deleted" => "0",
                "role" => "user",
                "account_type" => "registered"
            ];
        } else {
            // Kullanıcı yoksa, yeni kullanıcı eklemek için INSERT sorgusu kullan
            $payload = [
                "id" => null, // Başlangıçta id null
            ];
            $JwtCtrl = new Jwt("Sdw1");
            $token = $JwtCtrl->encode($payload);
            
            // Yeni kullanıcı verisini oluştur
            $data = [
                "name"  => $name,
                "surname" => $surname,
                "email" => $email,
                "role" => "user",
                "account_type" => "registered",
                "token" => $token, // İlk token kullanıcı oluşturulmadan önce
                "provider" => $provider,
                "provider_id" => $provider_id,
                "provider_id_token" => $idToken,
                "is_deleted" => "0",
                "created_date" => date("Y-m-d H:i:s"),
            ];
            
            // Kullanıcıyı veritabanına ekle
            $createSocialUserData = $this->insert($data);
            $registeredId = $createSocialUserData->id; // Veritabanına eklenen kullanıcının id'si
            
            if ($createSocialUserData) {
                // Kullanıcı başarıyla eklendikten sonra id'yi payload'a ekle ve token'ı güncelle
                $payload['id'] = $registeredId;
                $newToken = $JwtCtrl->encode($payload); // Yeni token ile yeniden encode et
                $this->update(['token' => $newToken], ['id' => $registeredId]); // Token'ı güncelle
            
                // Sonuçları döndür
                return [
                    "success" => true,
                    "message" => "User registered successfully",
                    "data" => [
                        "id" => $registeredId,
                        "email" => $email,
                        "name" => $name,
                        "surname" => $surname,
                        "role" => "user",
                        "token" => $newToken, // Yeni token ile döndür
                        "provider" => $provider,
                        "provider_id"=> $provider_id,
                        "provider_id_token" => $idToken,
                        "account_type" => "registered",
                    ]
                ];
            } else {
                return [
                    "success" => false,
                    "message" => "Error occurred while registering user"
                ];
            }
        }
    }
    
}
    
?>