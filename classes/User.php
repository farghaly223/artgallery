<?php
abstract class User {
    protected $id;
    protected $username;
    protected $email;
    protected $user_type;
    protected $profile_picture;
    protected $created_at;
    protected $is_banned;
    protected $db;
    
    public function __construct($id = null) {
        $this->db = Database::getInstance();
        
        if ($id) {
            $this->id = (int)$id;
            $this->loadUserData();
        }
    }
    
    protected function loadUserData() {
        $userData = $this->db->selectOne(
            "SELECT * FROM users WHERE user_id = :id",
            ['id' => $this->id]
        );
        
        if ($userData) {
            $this->username = $userData['username'];
            $this->email = $userData['email'];
            $this->user_type = $userData['user_type'];
            $this->profile_picture = $userData['profile_picture'];
            $this->created_at = $userData['created_at'];
            $this->is_banned = $userData['is_banned'];
        }
    }
    
    public static function login($username, $password) {
        $db = Database::getInstance();
        $userData = $db->selectOne(
            "SELECT * FROM users WHERE username = :username OR email = :email",
            ['username' => $username, 'email' => $username]
        );
        
        if ($userData && password_verify($password, $userData['password'])) {
            if ($userData['is_banned']) {
                return ['success' => false, 'message' => 'Your account has been banned.'];
            }
            
            $_SESSION['user_id'] = $userData['user_id'];
            $_SESSION['username'] = $userData['username'];
            $_SESSION['user_type'] = $userData['user_type'];
            
            return ['success' => true, 'user_type' => $userData['user_type'], 'user_id' => $userData['user_id']];
        }
        
        return ['success' => false, 'message' => 'Invalid username or password.'];
    }
    
    public static function register($username, $email, $password, $user_type) {
        $db = Database::getInstance();
        
        // Check if username or email already exists
        $existingUser = $db->selectOne(
            "SELECT * FROM users WHERE username = :username OR email = :email",
            ['username' => $username, 'email' => $email]
        );
        
        if ($existingUser) {
            if ($existingUser['username'] == $username) {
                return ['success' => false, 'message' => 'Username already exists.'];
            } else {
                return ['success' => false, 'message' => 'Email already exists.'];
            }
        }
        
        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert the new user
        $userId = $db->insert('users', [
            'username' => $username,
            'email' => $email,
            'password' => $hashedPassword,
            'user_type' => $user_type
        ]);
        
        if ($userId) {
            // Create profile based on user type
            if ($user_type == 'artist') {
                $db->insert('artist_profiles', [
                    'user_id' => $userId,
                    'bio' => ''
                ]);
            } else if ($user_type == 'advisor') {
                $db->insert('advisor_profiles', [
                    'user_id' => $userId,
                    'specialization' => '',
                    'experience_years' => 0
                ]);
            }
            
            return ['success' => true, 'user_id' => $userId];
        }
        
        return ['success' => false, 'message' => 'Registration failed.'];
    }
    
    public static function logout() {
        session_unset();
        session_destroy();
    }
    
    public function getId() {
        return $this->id;
    }
    
    public function getUsername() {
        return $this->username;
    }
    
    public function getEmail() {
        return $this->email;
    }
    
    public function getUserType() {
        return $this->user_type;
    }
    
    public function getProfilePicture() {
        return $this->profile_picture;
    }
    
    public function isBanned() {
        return $this->is_banned == 1;
    }
    
    public function updateProfile($data) {
        $this->db->update('users', $data, 'user_id = :id', ['id' => $this->id]);
        $this->loadUserData();
    }
    
    // Abstract methods to be implemented by child classes
    abstract public function getDashboardData();
} 