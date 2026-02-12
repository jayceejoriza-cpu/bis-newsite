<?php
/**
 * Barangay Management System - Configuration File
 * 
 * This file contains configuration settings for the dashboard template
 */

// Error Reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
define('DB_NAME', 'barangay_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Paths
define('BASE_URL', 'http://localhost/bis-newsite/');
define('COMPONENTS_PATH', __DIR__ . '/components/');

// Dashboard Statistics (These would normally come from database)
$dashboard_stats = [
    'total_residents' => 16798,
    'total_households' => 1,
    'pending_requests' => 2,
];

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
        'url' => 'users.php',
        'has_submenu' => true
    ],
    [
        'title' => 'Roles',
        'icon' => 'fa-user-shield',
        'url' => 'roles.php'
    ],
    [
        'title' => 'Resident Records',
        'icon' => 'fa-address-book',
        'url' => 'residents.php'
    ],
    [
        'title' => 'Community Households',
        'icon' => 'fa-home',
        'url' => 'households.php'
    ],
    [
        'title' => 'Certificate Issuance',
        'icon' => 'fa-certificate',
        'url' => 'certificates.php'
    ],
    [
        'title' => 'Service Requests',
        'icon' => 'fa-file-alt',
        'url' => 'requests.php'
    ],
    [
        'title' => 'Blotter Records',
        'icon' => 'fa-chart-bar',
        'url' => 'blotter.php'
    ],
    [
        'title' => 'Incident Reports',
        'icon' => 'fa-exclamation-circle',
        'url' => 'incidents.php'
    ],
    [
        'title' => 'Barangay Officials',
        'icon' => 'fa-user-tie',
        'url' => 'officials.php'
    ],
    [
        'title' => 'Reports',
        'icon' => 'fa-file-invoice',
        'url' => 'reports.php'
    ],
    [
        'title' => 'Settings',
        'icon' => 'fa-cog',
        'url' => 'settings.php',
        'has_submenu' => true
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
