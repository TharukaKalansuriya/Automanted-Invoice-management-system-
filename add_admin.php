<?php
// Start session with the same lifetime as in login.php
ini_set('session.gc_maxlifetime', 7200);
session_set_cookie_params(7200);
session_start();

// Check if user is logged in and session not expired
if (
    !isset($_SESSION['admin_id']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'super_admin' || // ensure role is admin
    !isset($_SESSION['expires']) ||
    time() > $_SESSION['expires']
) {
    // Destroy session for security
    session_unset();
    session_destroy();
    
    // Redirect to login page
    header('Location: index.php');
    exit();
}

// Optional: extend session expiration on activity
$_SESSION['expires'] = time() + 7200;

// Database connection (adjust these credentials)
$host = 'localhost';
$dbname = 'etrackbiz';
$username = 'root';
$password = '1234';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$message = '';
$edit_admin = null;

// Handle form submissions
if ($_POST) {
    $admin_id = $_POST['admin_id'] ?? null;
    $username = trim($_POST['username'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Basic validation
    $errors = [];
    
    if (empty($username)) {
        $errors[] = "Username is required";
    }
    
    if (empty($full_name)) {
        $errors[] = "Full name is required";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required";
    }
    
    if (empty($role)) {
        $errors[] = "Role is required";
    }
    
    if (empty($admin_id) && empty($password)) {
        $errors[] = "Password is required for new admin";
    }
    
    if (empty($errors)) {
        try {
            if ($admin_id) {
                // Update existing admin
                if (!empty($password)) {
                    $sql = "UPDATE admins SET username = ?, full_name = ?, email = ?, role = ?, password = ? WHERE admin_id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$username, $full_name, $email, $role, password_hash($password, PASSWORD_DEFAULT), $admin_id]);
                } else {
                    $sql = "UPDATE admins SET username = ?, full_name = ?, email = ?, role = ? WHERE admin_id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$username, $full_name, $email, $role, $admin_id]);
                }
                $message = "Admin updated successfully!";
            } else {
                // Add new admin
                $sql = "INSERT INTO admins (username, full_name, email, role, password) VALUES (?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$username, $full_name, $email, $role, password_hash($password, PASSWORD_DEFAULT)]);
                $message = "Admin added successfully!";
            }
        } catch(PDOException $e) {
            if ($e->getCode() == 23000) {
                $message = "Error: Username or email already exists!";
            } else {
                $message = "Error: " . $e->getMessage();
            }
        }
    } else {
        $message = "Error: " . implode(", ", $errors);
    }
}

// Handle edit request
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE admin_id = ?");
    $stmt->execute([$edit_id]);
    $edit_admin = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle delete request
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    
    // Prevent admin from deleting themselves
    if ($delete_id != $_SESSION['admin_id']) {
        try {
            $stmt = $pdo->prepare("DELETE FROM admins WHERE admin_id = ?");
            $stmt->execute([$delete_id]);
            $message = "Admin deleted successfully!";
        } catch(PDOException $e) {
            $message = "Error deleting admin: " . $e->getMessage();
        }
    } else {
        $message = "Error: You cannot delete your own account!";
    }
}

// Fetch all admins for display
$stmt = $pdo->query("SELECT admin_id, username, full_name, email, role FROM admins ORDER BY username");
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Management</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #100c42ff;
        }
        
        .container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        h1, h2 {
            color: #01b2e7ff;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 10px;
        }
        
        .message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            border: 1px solid;
        }
        
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }
        
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        
        input[type="text"], input[type="email"], input[type="password"], select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin-right: 10px;
        }
        
        button:hover {
            background-color: #45a049;
        }
        
        button.cancel {
            background-color: #6c757d;
        }
        
        button.cancel:hover {
            background-color: #5a6268;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        
        tr:hover {
            background-color: #f5f5f5;
        }
        
        .actions a {
            color: #007bff;
            text-decoration: none;
            margin-right: 10px;
        }
        
        .actions a:hover {
            text-decoration: underline;
        }
        
        .actions a.delete {
            color: #dc3545;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .user-info {
            color: #00ccffff;
            font-size: 17px;
        }
        .logout {
            color: #ff0019ff;
            text-decoration: none;
            font-weight: bold;
        }
        .back {
            color: #ff9100ff;
            text-decoration: none;
            font-weight: bold;
        }
    </style>
    
</head>
<body>
    <div class="header">
        <h1>Admin Management System</h1>
        <div class="user-info">
            Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?> | 
            <a class="back" href="super_admin.php">Back to Admin Dashboard</a> |
            <a class="logout" href="logout.php">Logout</a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="message <?php echo (strpos($message, 'Error') === 0) ? 'error' : 'success'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="container">
        <h2><?php echo $edit_admin ? 'Edit Admin' : 'Add New Admin'; ?></h2>
        
        <form method="POST" action="">
            <?php if ($edit_admin): ?>
                <input type="hidden" name="admin_id" value="<?php echo $edit_admin['admin_id']; ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label for="username">Username *</label>
                <input type="text" id="username" name="username" 
                       value="<?php echo htmlspecialchars($edit_admin['username'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="full_name">Full Name *</label>
                <input type="text" id="full_name" name="full_name" 
                       value="<?php echo htmlspecialchars($edit_admin['full_name'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" 
                       value="<?php echo htmlspecialchars($edit_admin['email'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="role">Role *</label>
                <select id="role" name="role" required>
                    <option value="">Select Role</option>
                    <option value="admin" <?php echo (isset($edit_admin['role']) && $edit_admin['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                    <option value="super_admin" <?php echo (isset($edit_admin['role']) && $edit_admin['role'] === 'super_admin') ? 'selected' : ''; ?>>Super Admin</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="password">Password <?php echo $edit_admin ? '(leave blank to keep current)' : '*'; ?></label>
                <input type="password" id="password" name="password" 
                       <?php echo !$edit_admin ? 'required' : ''; ?>>
            </div>
            
            <button type="submit">
                <?php echo $edit_admin ? 'Update Admin' : 'Add Admin'; ?>
            </button>
            
            <?php if ($edit_admin): ?>
                <a href="<?php echo $_SERVER['PHP_SELF']; ?>">
                    <button type="button" class="cancel">Cancel Edit</button>
                </a>
            <?php endif; ?>
        </form>
    </div>

    <div class="container">
        <h2>Current Admins</h2>
        
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($admins as $admin): ?>
                <tr>
                    <td><?php echo $admin['admin_id']; ?></td>
                    <td><?php echo htmlspecialchars($admin['username']); ?></td>
                    <td><?php echo htmlspecialchars($admin['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($admin['email']); ?></td>
                    <td><?php echo htmlspecialchars($admin['role']); ?></td>
                    <td class="actions">
                        <a href="?edit=<?php echo $admin['admin_id']; ?>">Edit</a>
                        <?php if ($admin['admin_id'] != $_SESSION['admin_id']): ?>
                            <a href="?delete=<?php echo $admin['admin_id']; ?>" 
                               class="delete" 
                               onclick="return confirm('Are you sure you want to delete this admin?')">Delete</a>
                        <?php else: ?>
                            <span style="color: #999;">(Current User)</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>