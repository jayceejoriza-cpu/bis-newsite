<?php
// Include configuration
require_once 'config.php';

// Log Activity
if (isset($_SESSION['username'])) {
    $log_user = $_SESSION['username'];
    $log_action = 'Logout';
    $log_desc = 'User logged out successfully';
    $stmt = $conn->prepare("INSERT INTO activity_logs (user, action, description) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $log_user, $log_action, $log_desc);
    $stmt->execute();
    $stmt->close();
}

// Destroy session
session_unset();
session_destroy();

// Redirect to login page
header("Location: login.php");
exit();
?>