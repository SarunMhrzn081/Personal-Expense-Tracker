<?php
require_once 'config.php';

class Auth {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // Register new user
    public function register($name, $email, $password) {
        // Check if email already exists
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            return ["success" => false, "message" => "Email already exists"];
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $avatar = strtoupper(substr($name, 0, 1));
        
        // Insert user
        $stmt = $this->pdo->prepare("INSERT INTO users (name, email, password, avatar) VALUES (?, ?, ?, ?)");
        
        if ($stmt->execute([$name, $email, $hashedPassword, $avatar])) {
            $userId = $this->pdo->lastInsertId();
            $this->loginUser($userId, $name, $email, $avatar);
            return ["success" => true, "message" => "Registration successful"];
        }
        
        return ["success" => false, "message" => "Registration failed"];
    }
    
    // Login user
    public function login($email, $password) {
        $stmt = $this->pdo->prepare("SELECT id, name, email, password, avatar FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            $this->loginUser($user['id'], $user['name'], $user['email'], $user['avatar']);
            return ["success" => true, "message" => "Login successful"];
        }
        
        return ["success" => false, "message" => "Invalid email or password"];
    }
    
    // Logout user
    public function logout() {
        session_destroy();
        header("Location: login.php");
        exit();
    }
    
    // Set session after login
    private function loginUser($userId, $name, $email, $avatar) {
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_avatar'] = $avatar;
    }
    
    // Get current user data
    public function getCurrentUser() {
        if (!isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email'],
            'avatar' => $_SESSION['user_avatar']
        ];
    }
}

$auth = new Auth($pdo);
?>