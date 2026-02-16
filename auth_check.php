<?php
/**
 * Authentication Guard
 * 
 * This file checks if a user is authenticated before allowing access to protected pages.
 * Include this file at the top of any page that requires authentication.
 * 
 * Usage: require_once 'auth_check.php';
 */

// Ensure session is started (in case config.php wasn't included first)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    // Store the requested page URL to redirect back after login (optional feature)
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    
    // Redirect to login page
    header("Location: login.php");
    exit();
}

// Optional: Check if session has expired (e.g., after 2 hours of inactivity)
// Uncomment the following lines to enable session timeout
/*
$session_timeout = 7200; // 2 hours in seconds
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_timeout)) {
    // Session expired
    session_unset();
    session_destroy();
    header("Location: login.php?timeout=1");
    exit();
}
$_SESSION['last_activity'] = time(); // Update last activity time
*/

// Optional: Regenerate session ID periodically for security
// Uncomment to enable
/*
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
} else if (time() - $_SESSION['created'] > 1800) {
    // Regenerate session ID every 30 minutes
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}
*/
