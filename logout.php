<?php
session_start(); // Start the session to access session variables

// Unset all session variables
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie.
// Note: This will destroy the session, and not just the session data!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Clear all cookies by iterating through $_COOKIE array
if (isset($_COOKIE)) {
    foreach ($_COOKIE as $name => $value) {
        // Set cookie with expiration time in the past to delete it
        setcookie($name, '', time() - 3600, '/');
        // Also try with different path variations if your app uses them
        setcookie($name, '', time() - 3600, '');
        setcookie($name, '', time() - 3600, '/', $_SERVER['HTTP_HOST']);
        
        // Remove from $_COOKIE superglobal for current script execution
        unset($_COOKIE[$name]);
    }
}

// Destroy the session
session_destroy();

// Clear browser cache headers (optional but recommended)
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Redirect to index.php
header("Location: index.php");
exit;
?>