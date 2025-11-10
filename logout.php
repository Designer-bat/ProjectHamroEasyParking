<?php
session_start();
session_unset();  // Remove all session variables
session_destroy(); // Destroy the session completely

// Optional: Delete cookies if you used "remember me"
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 600, '/');
}

header("Location: admin_login.php");
exit;
?>
