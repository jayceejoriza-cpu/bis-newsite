<?php
/**
 * Permission Helper
 * 
 * Loads the current logged-in user's merged permissions from all their assigned roles.
 * Admin users (role = 'Admin') bypass all permission checks.
 * 
 * Usage: require_once 'permissions.php';
 * Then use: hasPermission('perm_office_view')
 *           isAdmin()
 */

if (!isset($conn)) {
    require_once __DIR__ . '/config.php';
}

$_currentUserId = $_SESSION['user_id'] ?? 0;
$_userPermissions = [];
$_isAdminUser = false;

if ($_currentUserId > 0) {
    // Check if user is Admin (bypasses all permission checks)
    $adminStmt = $conn->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
    if ($adminStmt) {
        $adminStmt->bind_param('i', $_currentUserId);
        $adminStmt->execute();
        $adminResult = $adminStmt->get_result()->fetch_assoc();
        $adminStmt->close();
        if ($adminResult && strtolower($adminResult['role']) === 'admin') {
            $_isAdminUser = true;
        }
    }

    if (!$_isAdminUser) {
        // Fetch all role permissions for this user (merge all assigned roles)
        $permStmt = $conn->prepare("
            SELECT r.permissions
            FROM user_roles ur
            JOIN roles r ON ur.role_id = r.id
            WHERE ur.user_id = ?
        ");
        if ($permStmt) {
            $permStmt->bind_param('i', $_currentUserId);
            $permStmt->execute();
            $permResult = $permStmt->get_result();
            
            $moduleViews = [
                'perm_resident_' => 'perm_resident_view',
                'perm_household_' => 'perm_household_view',
                'perm_blotter_' => 'perm_blotter_view',
                'perm_officials_' => 'perm_officials_view',
                'perm_req_' => 'perm_req_view',
                'perm_office_' => 'perm_office_view',
                'perm_roles_' => 'perm_roles_view',
                'perm_cert_' => 'perm_cert_view',
                'perm_settings_logs_' => 'perm_settings_logs_view',
                'perm_events_' => 'perm_events_view',
                'perm_reports_' => 'perm_reports_view'
            ];
            
            while ($permRow = $permResult->fetch_assoc()) {
                if (!empty($permRow['permissions'])) {
                    $rolePerms = json_decode($permRow['permissions'], true);
                    if (is_array($rolePerms)) {
                        foreach ($rolePerms as $perm => $value) {
                            // A permission is granted if ANY assigned role grants it
                            if ($value === true) {
                                $_userPermissions[$perm] = true;
                                
                                // Auto-grant 'view' permission if another permission in the same module is granted
                                foreach ($moduleViews as $prefix => $viewPerm) {
                                    if (strpos($perm, $prefix) === 0 && $perm !== $viewPerm) {
                                        $_userPermissions[$viewPerm] = true;
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $permStmt->close();
        }
    }
}

/**
 * Check if the current user has a specific permission.
 * Admin users always return true.
 */
function hasPermission(string $perm): bool {
    global $_isAdminUser, $_userPermissions;
    if ($_isAdminUser) return true;
    return isset($_userPermissions[$perm]) && $_userPermissions[$perm] === true;
}

/**
 * Check if the current user is an Admin (bypasses all permission checks).
 */
function isAdmin(): bool {
    global $_isAdminUser;
    return $_isAdminUser;
}

/**
 * Redirect to dashboard with an access-denied message if user lacks permission.
 */
function requirePermission(string $perm, string $redirectTo = 'index.php'): void {
    if (!hasPermission($perm)) {
        $_SESSION['access_denied'] = 'You do not have permission to access that page.';
        header("Location: {$redirectTo}");
        exit();
    }
}
?>
