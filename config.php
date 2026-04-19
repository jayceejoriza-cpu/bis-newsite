<?php
/**
 * Barangay Management System - Configuration File
 * 
 * This file contains configuration settings for the dashboard template
 */

// Error Reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start Session
session_start();

// Timezone
date_default_timezone_set('Asia/Manila');

// Site Configuration
define('SITE_NAME', 'Barangay Management System');
define('SITE_VERSION', '1.0.0');
define('SITE_YEAR', date('Y'));

// Barangay Information (Customize these)
define('BARANGAY_NAME', 'Barangay Name');
define('BARANGAY_LOGO', 'assets/images/logo.png');

// Database Configuration (for future use)
define('DB_HOST', 'localhost');
define('DB_NAME', 'bmis');
define('DB_USER', 'root');
define('DB_PASS', '');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// Paths
define('BASE_URL', 'http://localhost/bis-newsite/');
define('COMPONENTS_PATH', __DIR__ . '/components/');

// Dashboard Statistics - Fetch from database
$dashboard_stats = [
    'total_residents' => 0,
    'total_households' => 0,
];

// Fetch total residents (active only)
$result = $conn->query("SELECT COUNT(*) as count FROM residents WHERE activity_status = 'Alive'");
if ($result && $row = $result->fetch_assoc()) {
    $dashboard_stats['total_residents'] = (int)$row['count'];
}

// Fetch total households
$result = $conn->query("SELECT COUNT(*) as count FROM households");
if ($result && $row = $result->fetch_assoc()) {
    $dashboard_stats['total_households'] = (int)$row['count'];
}

// Menu Items Configuration
$menu_items = [
    [
        'title' => 'Dashboard',
        'icon' => 'fa-th-large',
        'url' => 'index.php',
        'active' => true
    ],
    [
        'section' => 'User Management'
    ],
    [
        'title' => 'Users',
        'icon' => 'fa-users',
        'url' => 'official-user.php',
        'has_submenu' => true,
        'permission' => 'perm_office_view'
    ],
    [
        'title' => 'Roles',
        'icon' => 'fa-user-shield',
        'url' => 'roles.php',
        'permission' => 'perm_roles_view'
    ],
    [
        'title' => 'Resident Records',
        'icon' => 'fa-address-book',
        'url' => 'residents.php',
        'permission' => 'perm_resident_view'
    ],
    [
        'title' => 'Community Households',
        'icon' => 'fa-home',
        'url' => 'households.php',
        'permission' => 'perm_household_view'
    ],
    [
        'title' => 'Certificate Issuance',
        'icon' => 'fa-certificate',
        'url' => 'certificates.php',
        'permission' => 'perm_cert_view'
    ],
    [
        'title' => 'Service Requests',
        'icon' => 'fa-file-alt',
        'url' => 'requests.php',
        'permission' => 'perm_req_view'
    ],
    [
        'title' => 'Blotter Records',
        'icon' => 'fa-chart-bar',
        'url' => 'blotter.php',
        'permission' => 'perm_blotter_view'
    ],
    [
        'title' => 'Barangay Events',
        'icon' => 'fa-calendar-alt',
        'url' => 'events.php',
        'permission' => 'perm_events_view'
    ],
    [
        'title' => 'Barangay Officials',
        'icon' => 'fa-user-tie',
        'url' => 'officials.php',
        'permission' => 'perm_officials_view'
    ],
    [
        'title' => 'Statistical Reports',
        'icon' => 'fa-file-invoice',
        'url' => 'reports.php',
        'permission' => 'perm_reports_view'
    ],
    [
        'title' => 'Settings',
        'icon' => 'fa-cog',
        'url' => 'settings.php',
        'has_submenu' => true,
        'permission' => 'perm_settings_logs_view'
    ]
];

// Helper Functions
function getCurrentPage() {
    return basename($_SERVER['PHP_SELF']);
}

function isActivePage($url) {
    return getCurrentPage() === $url ? 'active' : '';
}

function formatNumber($number) {
    return number_format($number);
}

function getGreeting() {
    $hour = date('H');
    if ($hour < 12) {
        return 'Good Morning';
    } elseif ($hour < 18) {
        return 'Good Afternoon';
    } else {
        return 'Good Evening';
    }
}
?>
