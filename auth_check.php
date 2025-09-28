<?php
session_start();

// Session timeout (10 minutes = 600 seconds)
$timeout = 60;

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit;
}

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
    session_unset();
    session_destroy();
    header("Location: admin_login.php?session_expired=true");
    exit;
}

$_SESSION['last_activity'] = time(); // update time
?>
