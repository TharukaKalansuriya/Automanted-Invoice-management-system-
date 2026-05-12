<?php
require_once 'Database.php';

// Start session with 2-hour lifetime
ini_set('session.gc_maxlifetime', 7200);
session_set_cookie_params(7200);
session_start();

// Check if already logged in and redirect based on role
if (isset($_SESSION['admin_id']) && isset($_SESSION['expires']) && time() < $_SESSION['expires']) {
    $role = $_SESSION['role'] ?? 'admin';
    if ($role === 'super_admin') {
        header('Location: super_admin.php');
    } else {
        header('Location: admin.php');
    }
    exit();
}

// Check remember me cookie
if (isset($_COOKIE['remember_admin']) && !isset($_SESSION['admin_id'])) {
    $database = new Database();
    $db = $database->getConnection();
    
    $admin_id = base64_decode($_COOKIE['remember_admin']);
    $stmt = $db->prepare("SELECT admin_id, username, full_name, email, role FROM admins WHERE admin_id = ?");
    $stmt->execute([$admin_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $_SESSION['admin_id'] = $user['admin_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['expires'] = time() + 7200; // 2 hours
        
        // Redirect based on role
        if ($user['role'] === 'super_admin') {
            header('Location: super_admin.php');
        } else {
            header('Location: admin.php');
        }
        exit();
    }
}

$error = '';

/**
 * Function to verify password against both hashed and plain text passwords
 * @param string $inputPassword - The password entered by user
 * @param string $storedPassword - The password stored in database
 * @return bool - True if password matches, false otherwise
 */
function verifyPassword($inputPassword, $storedPassword) {
    // First, try to verify as a hashed password
    if (password_verify($inputPassword, $storedPassword)) {
        return true;
    }
    
    // If hash verification fails, check if it's a plain text match
    if ($inputPassword === $storedPassword) {
        return true;
    }
    
    // Additional hash formats can be checked here if needed
    // For example, MD5 (not recommended for new systems)
    if (md5($inputPassword) === $storedPassword) {
        return true;
    }
    
    // SHA1 check (also not recommended for new systems)
    if (sha1($inputPassword) === $storedPassword) {
        return true;
    }
    
    return false;
}

/**
 * Function to upgrade plain text password to hash
 * @param PDO $db - Database connection
 * @param int $admin_id - Admin ID
 * @param string $plainPassword - Plain text password to hash
 */
function upgradePasswordToHash($db, $admin_id, $plainPassword) {
    try {
        $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE admins SET password = ? WHERE admin_id = ?");
        $stmt->execute([$hashedPassword, $admin_id]);
    } catch (PDOException $e) {
        // Log error but don't interrupt login process
        error_log("Password upgrade failed for admin_id: " . $admin_id);
    }
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $rememberMe = isset($_POST['remember_me']);
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            // Get user data including password
            $stmt = $db->prepare("SELECT admin_id, username, full_name, email, role, password FROM admins WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && verifyPassword($password, $user['password'])) {
                // Check if password needs to be upgraded to hash
                if ($password === $user['password'] || md5($password) === $user['password'] || sha1($password) === $user['password']) {
                    // Password is stored as plain text, MD5, or SHA1 - upgrade it
                    upgradePasswordToHash($db, $user['admin_id'], $password);
                }
                
                // Set session
                $_SESSION['admin_id'] = $user['admin_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['expires'] = time() + 7200; // 2 hours
                
                // Set remember me cookie for 2 days if checked
                if ($rememberMe) {
                    $token = base64_encode($user['admin_id']);
                    setcookie('remember_admin', $token, time() + (2 * 24 * 60 * 60), '/', '', false, true);
                }
                
                // Redirect based on role
                if ($user['role'] === 'super_admin') {
                    header('Location: super_admin.php');
                } else {
                    header('Location: admin.php');
                }
                exit();
            } else {
                $error = 'Invalid email or password.';
            }
        } catch (PDOException $e) {
            $error = 'Database error occurred.';
            error_log("Login database error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - E Track Biz</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .bg-pattern {
            background-image: url('images/bgimg.jpg');
            background-size: cover;
            background-position: center;
        }
        .glass-effect {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
    </style>
</head>
<body class="min-h-screen bg-pattern">
    <div class="absolute inset-0 bg-black/40"></div>
    
    <div class="relative z-10 min-h-screen flex items-center justify-center px-4">
        <div class="w-full max-w-md">
            
            <!-- Logo -->
            <div class="text-center glass-effect rounded-2xl  mb-8">
                <img src="images/logo.png" alt="Logo" class="w-auto h-20 mx-auto mb-4">
                <p class="text-white/70">Admin Login</p>
            </div>

            <!-- Login Form -->
            <div class="glass-effect rounded-2xl p-8 shadow-2xl">
                
                <?php if ($error): ?>
                    <div class="bg-red-500/20 border border-red-500/30 text-red-200 p-3 rounded-lg mb-4">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="space-y-6">
                    <!-- Email -->
                    <div>
                        <label class="block text-white/90 font-medium mb-2">Email</label>
                        <div class="relative">
                            <i class="fas fa-envelope absolute left-3 top-3.5 text-white/50"></i>
                            <input 
                                type="email" 
                                name="email"
                                value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                class="w-full pl-10 pr-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-white/50 focus:outline-none focus:border-blue-400"
                                placeholder="Enter email address"
                                required
                            >
                        </div>
                    </div>

                    <!-- Password -->
                    <div>
                        <label class="block text-white/90 font-medium mb-2">Password</label>
                        <div class="relative">
                            <i class="fas fa-lock absolute left-3 top-3.5 text-white/50"></i>
                            <input 
                                type="password" 
                                name="password"
                                class="w-full pl-10 pr-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-white/50 focus:outline-none focus:border-blue-400"
                                placeholder="Enter password"
                                required
                            >
                        </div>
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center">
                        <input type="checkbox" name="remember_me" id="remember_me" class="w-4 h-4 text-blue-500 bg-white/10 border-white/20 rounded">
                        <label for="remember_me" class="ml-2 text-white/80 text-sm">Remember me for 2 days</label>
                    </div>

                    <!-- Login Button -->
                    <button 
                        type="submit" 
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-xl transition-colors"
                    >
                        Sign In
                    </button>
                </form>

                <!-- Session Info -->
                <div class="mt-4 p-3 bg-blue-500/10 border border-blue-500/20 rounded-lg">
                    <p class="text-blue-200 text-xs text-center">
                        <i class="fas fa-info-circle mr-1"></i>
                        Session expires in 2 hours
                    </p>
                </div>
                
                <!-- Security Notice -->
                <div class="mt-4 p-3 bg-green-500/10 border border-green-500/20 rounded-lg">
                    <p class="text-green-200 text-xs text-center">
                        <i class="fas fa-shield-alt mr-1"></i>
                        Enhanced password security enabled
                    </p>
                </div>
            </div>

            <div class="text-center mt-6 text-white/50 text-sm">
                <p>&copy; 2025 ETrack Biz. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>