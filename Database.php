<?php
class Database {
    private $host;
    private $username;
    private $password;
    private $database;
    private $connection;
    
    public function __construct($host = 'localhost', 
    $username = 'root',
    $password = '',
    $database = '') {
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->database = $database;
        
        $this->connect();
    }
    
    private function connect() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->database};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function prepare($sql) {
        return $this->connection->prepare($sql);
    }
    
    public function execute($sql, $params = []) {
        $stmt = $this->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    public function fetch($sql, $params = []) {
        $stmt = $this->execute($sql, $params);
        return $stmt->fetch();
    }
    
    public function fetchAll($sql, $params = []) {
        $stmt = $this->execute($sql, $params);
        return $stmt->fetchAll();
    }
    
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
    
    public function rowCount($sql, $params = []) {
        $stmt = $this->execute($sql, $params);
        return $stmt->rowCount();
    }
    
    public function close() {
        $this->connection = null;
    }
}

class AdminAuth {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
        
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Set session timeout to 2 hours
       // ini_set('session.gc_maxlifetime', 7200); // 2 hours in seconds
    }
    
    public function login($username, $password, $rememberMe = false) {
        try {
            // Clean and validate input
            $username = trim($username);
            
            if (empty($username) || empty($password)) {
                return ['success' => false, 'message' => 'Username and password are required'];
            }
            
            // Get admin from database
            $sql = "SELECT admin_id, username, password_hash, full_name, email, role FROM admins WHERE username = ? LIMIT 1";
            $admin = $this->db->fetch($sql, [$username]);
            
            if (!$admin) {
                return ['success' => false, 'message' => 'Invalid username or password'];
            }
            
            // Verify password
            if (!password_verify($password, $admin['password_hash'])) {
                return ['success' => false, 'message' => 'Invalid username or password'];
            }
            
            // Create session
            $this->createSession($admin);
            
            // Handle remember me
            if ($rememberMe) {
                $this->createRememberMeCookie($admin['admin_id']);
            }
            
            return [
                'success' => true, 
                'message' => 'Login successful',
                'admin' => [
                    'id' => $admin['admin_id'],
                    'username' => $admin['username'],
                    'full_name' => $admin['full_name'],
                    'email' => $admin['email'],
                    'role' => $admin['role']
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred during login'];
        }
    }
    
    private function createSession($admin) {
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $admin['admin_id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_full_name'] = $admin['full_name'];
        $_SESSION['admin_email'] = $admin['email'];
        $_SESSION['admin_role'] = $admin['role'];
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
    }
    
    private function createRememberMeCookie($adminId) {
        // Generate a secure random token
        $token = bin2hex(random_bytes(32));
        $expiry = time() + (7 * 24 * 60 * 60); // 7 days
        
        // Store token in database (you might want to create a separate table for this)
        $hashedToken = hash('sha256', $token);
        
        // For simplicity, storing in a separate table (you should create this table)
        try {
            $sql = "INSERT INTO admin_remember_tokens (admin_id, token_hash, expires_at) VALUES (?, ?, ?) 
                    ON DUPLICATE KEY UPDATE token_hash = VALUES(token_hash), expires_at = VALUES(expires_at)";
            $this->db->execute($sql, [$adminId, $hashedToken, date('Y-m-d H:i:s', $expiry)]);
        } catch (Exception $e) {
            // If table doesn't exist, just log the error
            error_log("Remember me token storage failed: " . $e->getMessage());
        }
        
        // Set the cookie
        setcookie('remember_admin', $adminId . ':' . $token, $expiry, '/', '', true, true);
    }
    
    public function checkRememberMe() {
        if (!isset($_COOKIE['remember_admin'])) {
            return false;
        }
        
        $cookieData = explode(':', $_COOKIE['remember_admin'], 2);
        if (count($cookieData) !== 2) {
            return false;
        }
        
        $adminId = $cookieData[0];
        $token = $cookieData[1];
        $hashedToken = hash('sha256', $token);
        
        try {
            // Check if token exists and is valid
            $sql = "SELECT a.admin_id, a.username, a.full_name, a.email, a.role 
                    FROM admins a 
                    JOIN admin_remember_tokens rt ON a.admin_id = rt.admin_id 
                    WHERE a.admin_id = ? AND rt.token_hash = ? AND rt.expires_at > NOW()";
            $admin = $this->db->fetch($sql, [$adminId, $hashedToken]);
            
            if ($admin) {
                $this->createSession($admin);
                return true;
            }
        } catch (Exception $e) {
            error_log("Remember me check failed: " . $e->getMessage());
        }
        
        // Clear invalid cookie
        setcookie('remember_admin', '', time() - 3600, '/');
        return false;
    }
    
    public function isLoggedIn() {
        // Check if session exists and is valid
        if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
            return false;
        }
        
        // Check session timeout (2 hours)
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 7200)) {
            $this->logout();
            return false;
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    public function logout() {
        // Clear remember me cookie if it exists
        if (isset($_COOKIE['remember_admin'])) {
            $cookieData = explode(':', $_COOKIE['remember_admin'], 2);
            if (count($cookieData) === 2) {
                $adminId = $cookieData[0];
                $token = $cookieData[1];
                $hashedToken = hash('sha256', $token);
                
                // Remove token from database
                try {
                    $sql = "DELETE FROM admin_remember_tokens WHERE admin_id = ? AND token_hash = ?";
                    $this->db->execute($sql, [$adminId, $hashedToken]);
                } catch (Exception $e) {
                    error_log("Token cleanup failed: " . $e->getMessage());
                }
            }
            
            setcookie('remember_admin', '', time() - 3600, '/');
        }
        
        // Clear session
        session_destroy();
        session_start();
    }
    
    public function getAdminData() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['admin_id'],
            'username' => $_SESSION['admin_username'],
            'full_name' => $_SESSION['admin_full_name'],
            'email' => $_SESSION['admin_email'],
            'role' => $_SESSION['admin_role']
        ];
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn() && !$this->checkRememberMe()) {
            header('Location: index.php');
            exit();
        }
    }
}
?>
